<?php

namespace App\Providers;

use App\Models\Admin\SignInAttempt;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Every admin sign-in, good or bad, goes into the admin logs.
        Event::listen(Login::class, function (Login $event) {
            rescue(function () use ($event) {
                SignInAttempt::log($event->user->email ?? null, 'password', true);
                $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
            }, report: false);
        });

        Event::listen(Failed::class, function (Failed $event) {
            rescue(fn () => SignInAttempt::log($event->credentials['email'] ?? null, 'password', false), report: false);
        });
    }
}
