<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The second sign-in step. Once the admin has registered a passkey, a
 * password alone is not enough: every new session must also be confirmed
 * with the passkey (or a one-time recovery code).
 */
class RequirePasskey
{
    public const SESSION_KEY = 'openrevise.passkey_confirmed';

    public function handle(Request $request, Closure $next): Response
    {
        $admin = $request->user();

        if ($admin && $admin->hasPasskeys() && $request->session()->get(self::SESSION_KEY) !== $admin->getKey()) {
            $request->session()->put('url.intended', $request->fullUrl());

            return redirect()->route('admin.passkey.challenge');
        }

        return $next($request);
    }
}
