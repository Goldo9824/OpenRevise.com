<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * For openrevise.com (the presentation pages and the app): no session, no
 * cookies, a trailing slash on every page address, and sensible headers.
 */
class PublicPage
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($redirect = $this->addTrailingSlash($request)) {
            return $redirect;
        }

        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), interest-cohort=()');

        if ($request->isMethod('GET') && $response->getStatusCode() === 200) {
            $private = $request->query->has('preview') || $request->attributes->get('no_cache');
            $response->headers->set('Cache-Control', $private ? 'no-store' : 'public, max-age=120');
        }

        return $response;
    }

    /** /gcse/biology becomes /gcse/biology/ so every page has one address. */
    protected function addTrailingSlash(Request $request): ?Response
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return null;
        }

        $path = $request->getPathInfo();

        if ($path === '/' || str_ends_with($path, '/') || str_contains(basename($path), '.') || str_starts_with($path, '/_')) {
            return null;
        }

        $query = $request->getQueryString();

        return redirect()->to($request->getSchemeAndHttpHost().$path.'/'.($query ? '?'.$query : ''), 301);
    }
}
