<?php

namespace App\Http\Middleware;

use App\Models\Admin\ApiKey;
use App\Support\Actor;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Checks the API key sent as "Authorization: Bearer or_..." (or X-Api-Key)
 * and that it carries the ability the route needs.
 */
class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next, string $ability = 'read'): Response
    {
        $plain = $request->bearerToken() ?: $request->header('X-Api-Key');

        if (! $plain) {
            return $this->deny('Missing API key. Send it as "Authorization: Bearer <key>".', 401);
        }

        $key = ApiKey::findByPlainKey($plain);

        if (! $key || ! $key->isUsable()) {
            return $this->deny('This API key is not valid, has expired or was revoked.', 401);
        }

        $request->attributes->set('api_key', $key);

        if (! $key->can($ability)) {
            return $this->deny("This key does not have the \"$ability\" right.", 403);
        }

        $limiter = 'api-key:'.$key->id;
        $limit = config('openrevise.api.rate_limit');

        if (RateLimiter::tooManyAttempts($limiter, $limit)) {
            return response()->json([
                'error' => 'Too many requests. Wait a minute and try again.',
                'retry_after' => RateLimiter::availableIn($limiter),
            ], 429);
        }

        RateLimiter::hit($limiter, 60);

        if (! $key->last_used_at || $key->last_used_at->lt(now()->subMinute())) {
            $key->forceFill(['last_used_at' => now(), 'last_used_ip' => $request->ip()])->saveQuietly();
        }

        Actor::set('API key: '.$key->name);

        return $next($request);
    }

    protected function deny(string $message, int $status): Response
    {
        return response()->json(['error' => $message], $status);
    }
}
