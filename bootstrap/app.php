<?php

use App\Http\Middleware\AuthenticateApiKey;
use App\Http\Middleware\LogApiRequest;
use App\Http\Middleware\PublicPage;
use App\Support\ErrorRecorder;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

            // The admin panel registers its own routes (Filament), on its domain.
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Public pages carry no session and set no cookies.
        $middleware->group('public', [
            PublicPage::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        $middleware->api(append: [
            LogApiRequest::class,
        ]);

        $middleware->alias([
            'api.key' => AuthenticateApiKey::class,
        ]);

        // LWS sits behind a proxy for HTTPS.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->getHost() === config('openrevise.domains.api') || $request->expectsJson(),
        );

        $exceptions->report(function (Throwable $e) {
            ErrorRecorder::record($e);
        });
    })->create();
