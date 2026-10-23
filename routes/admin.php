<?php

use App\Http\Controllers\Admin\PasskeyController;
use Illuminate\Support\Facades\Route;

/*
| admin.openrevise.com - the Filament panel registers its own pages. These
| are the extra routes around it: the passkey sign-in step and robots.txt.
*/

Route::get('/robots.txt', fn () => response("User-agent: *\nDisallow: /\n", 200, ['Content-Type' => 'text/plain']));

Route::middleware('auth')->prefix('passkey')->name('admin.passkey.')->group(function () {
    Route::get('/', [PasskeyController::class, 'challenge'])->name('challenge');
    Route::get('options', [PasskeyController::class, 'options'])->name('options');
    Route::post('verify', [PasskeyController::class, 'verify'])->name('verify');
    Route::post('recovery', [PasskeyController::class, 'recovery'])->name('recovery');
    Route::post('sign-out', [PasskeyController::class, 'signOut'])->name('sign-out');
});
