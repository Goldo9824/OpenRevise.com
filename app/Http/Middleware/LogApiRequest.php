<?php

namespace App\Http\Middleware;

use App\Models\Admin\ApiRequest;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/** Every API call is written to the Admin DB with the key that made it. */
class LogApiRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('api_started', microtime(true));

        $response = $next($request);

        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        if ($request->isMethod('GET') && in_array($request->path(), ['/', 'robots.txt', 'v1/docs', 'favicon.ico'], true)) {
            return;
        }

        $error = null;
        if ($response->getStatusCode() >= 400 && $response instanceof JsonResponse) {
            $data = $response->getData(true);
            $error = Str::limit(json_encode($data['errors'] ?? $data['error'] ?? $data['message'] ?? null, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 2000);
        }

        try {
            ApiRequest::create([
                'api_key_id' => $request->attributes->get('api_key')?->id,
                'method' => $request->method(),
                'path' => Str::limit('/'.ltrim($request->path(), '/'), 290),
                'status' => $response->getStatusCode(),
                'summary' => Str::limit((string) $request->attributes->get('api_summary'), 290) ?: null,
                'error' => $error,
                'ip' => $request->ip(),
                'duration_ms' => (int) round((microtime(true) - $request->attributes->get('api_started', microtime(true))) * 1000),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
