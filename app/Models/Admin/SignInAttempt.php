<?php

namespace App\Models\Admin;

class SignInAttempt extends AdminModel
{
    public const UPDATED_AT = null;

    protected $casts = ['succeeded' => 'boolean'];

    public static function log(?string $email, string $method, bool $succeeded): void
    {
        $request = request();

        static::create([
            'email' => $email,
            'method' => $method,
            'succeeded' => $succeeded,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 300),
        ]);
    }
}
