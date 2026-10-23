<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

/**
 * Who is changing content right now, for the change history: the admin who
 * is signed in, or the API key making the request.
 */
class Actor
{
    protected static ?string $override = null;

    public static function set(?string $actor): void
    {
        static::$override = $actor;
    }

    public static function current(): string
    {
        if (static::$override) {
            return static::$override;
        }

        $admin = Auth::guard('web')->user();

        return $admin ? 'Admin: '.$admin->name : 'System';
    }
}
