<?php

use App\Http\Controllers\Api\DocsController;
use App\Http\Controllers\Api\ImageController;
use App\Http\Controllers\Api\LessonController;
use App\Http\Controllers\Api\MockExamController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\TopicController;
use App\Http\Controllers\Api\TreeController;
use Illuminate\Support\Facades\Route;

/*
| api.openrevise.com - lets Claude create and list content with an API key.
| Every call is logged in the Admin DB with the key that made it.
*/

Route::get('/', [DocsController::class, 'html'])->name('api.docs');
Route::get('/v1/docs', [DocsController::class, 'markdown'])->name('api.docs.markdown');
Route::get('/robots.txt', fn () => response("User-agent: *\nDisallow: /\n", 200, ['Content-Type' => 'text/plain']));

Route::prefix('v1')->group(function () {
    Route::middleware('api.key:read')->group(function () {
        Route::get('tree', [TreeController::class, 'tree']);
        Route::get('boards', [TreeController::class, 'boards']);
        Route::get('lessons/{id}', [LessonController::class, 'show'])->whereNumber('id');
        Route::get('images', [ImageController::class, 'index']);
        Route::post('lessons/check', [LessonController::class, 'check']);
    });

    Route::middleware('api.key:write')->group(function () {
        Route::post('topics', [TopicController::class, 'storeTopic']);
        Route::post('chapters', [TopicController::class, 'storeChapter']);
        Route::post('lessons', [LessonController::class, 'store']);
        Route::post('lessons/bulk', [LessonController::class, 'bulk']);
        Route::match(['put', 'patch'], 'lessons/{id}', [LessonController::class, 'update'])->whereNumber('id');
        Route::post('images', [ImageController::class, 'store']);
        Route::post('questions', [QuestionController::class, 'store']);
        Route::post('mock-exams', [MockExamController::class, 'store']);
    });
});

Route::fallback(fn () => response()->json([
    'error' => 'Not found. The API reference is at '.api_url('/').' (or '.api_url('/v1/docs').' as Markdown).',
], 404));
