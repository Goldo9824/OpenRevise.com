<?php

use App\Http\Controllers\CollectController;
use App\Http\Controllers\Main\PageController;
use Illuminate\Support\Facades\Route;

/*
| openrevise.com - the presentation pages. The revision app is served on the
| same domain (routes/app.php), along with the sitemap of both.
*/

Route::get('/', [PageController::class, 'home'])->name('main.home');
Route::get('/about', [PageController::class, 'about']);
Route::get('/privacy', [PageController::class, 'privacy']);
Route::get('/terms', [PageController::class, 'terms']);
Route::get('/contact', [PageController::class, 'contact']);
Route::get('/robots.txt', [PageController::class, 'robots']);
Route::post('/_t', CollectController::class);

Route::fallback(fn () => abort(404));
