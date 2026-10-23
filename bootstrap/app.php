<?php

use App\Http\Middleware\AuthenticateApiKey;
use App\Http\Middleware\LogApiRequest;
use App\Http\Middleware\NoIndex;
use App\Http\Middleware\PublicPage;
use App\Models\Content\SlugRedirect;
use App\Support\ErrorRecorder;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/*
| One codebase, four sites. Each subdomain folder on the server holds a small
| index.php that loads this core; Laravel then picks the routes that match
| the hostname of the request.
*/

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        using: function () {
            $domains = config('openrevise.domains');

            Route::domain($domains['api'])
                ->middleware('api')
                ->group(base_path('routes/api.php'));

            Route::domain($domains['app'])
                ->middleware('public')
                ->group(base_path('routes/app.php'));

            Route::domain($domains['main'])
                ->middleware('public')
                ->group(base_path('routes/main.php'));

            // www.openrevise.com goes to the bare domain.
            Route::domain('www.'.$domains['main'])->group(function () {
                Route::any('{any?}', fn (Request $request) => redirect()->away(
                    main_url($request->getRequestUri()), 301
                ))->where('any', '.*');
            });

            // The admin panel (Filament) registers its own pages on its domain.
            Route::domain($domains['admin'])
                ->middleware(['web', NoIndex::class])
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Public pages carry no session and set no cookies.
        $middleware->group('public', [
            PublicPage::class,
            SubstituteBindings::class,
        ]);

        $middleware->api(append: [
            LogApiRequest::class,
        ]);

        $middleware->alias([
            'api.key' => AuthenticateApiKey::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('filament.admin.auth.login'));

        // LWS sits behind a proxy for HTTPS.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->getHost() === config('openrevise.domains.api') || $request->expectsJson(),
        );

        // An address that used to exist (a slug changed, a lesson moved) is
        // answered with a permanent redirect instead of a 404.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->isMethod('GET') && $request->getHost() === config('openrevise.domains.app')
                && ($new = SlugRedirect::resolve($request->getPathInfo()))) {
                return redirect()->to(app_url($new), 301);
            }
        });

        $exceptions->report(function (Throwable $e) {
            ErrorRecorder::record($e);
        });
    })->create();
