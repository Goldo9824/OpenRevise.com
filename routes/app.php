<?php

use App\Http\Controllers\App\BrowseController;
use App\Http\Controllers\App\FallbackController;
use App\Http\Controllers\App\HomeController;
use App\Http\Controllers\App\LessonController;
use App\Http\Controllers\App\MockExamController;
use App\Http\Controllers\App\ReportController;
use App\Http\Controllers\App\SearchController;
use App\Http\Controllers\App\SitemapController;
use App\Http\Controllers\CollectController;
use Illuminate\Support\Facades\Route;

/*
| app.openrevise.com - the revision app. Public, no login, rendered on the
| server so search engines can read every page.
|
|   /gcse/biology/                                   subject
|   /gcse/biology/aqa/                               board page
|   /gcse/biology/cell-biology/                      topic
|   /gcse/biology/cell-biology/cell-structure/       chapter
|   /gcse/biology/cell-biology/cell-structure/x/     lesson
|   .../x/exam-questions/                            the lesson's questions
|   /gcse/biology/mock-exams/aqa-paper-1/            mock exam
*/

$level = config('openrevise.level');

Route::get('/', [HomeController::class, 'index'])->name('app.home');
Route::get('/sitemap.xml', [SitemapController::class, 'app']);
Route::get('/robots.txt', [SitemapController::class, 'robots']);
Route::post('/_t', CollectController::class);
Route::post('/_report', [ReportController::class, 'store']);
Route::get('/_search', [SearchController::class, 'json']);
Route::get('/search', [SearchController::class, 'page']);
Route::get('/saved', [HomeController::class, 'saved']);
Route::get("/$level", [HomeController::class, 'subjects']);

Route::prefix($level)->group(function () {
    Route::get('{subject}', [BrowseController::class, 'subject']);
    Route::get('{subject}/mock-exams', [MockExamController::class, 'index']);
    Route::get('{subject}/mock-exams/{exam}', [MockExamController::class, 'show']);
    Route::get('{subject}/{segment}', [BrowseController::class, 'segment']);
    Route::get('{subject}/{topic}/{chapter}', [BrowseController::class, 'chapter']);
    Route::get('{subject}/{topic}/{chapter}/{lesson}', [LessonController::class, 'show']);
    Route::get('{subject}/{topic}/{chapter}/{lesson}/exam-questions', [LessonController::class, 'questions']);
});

Route::fallback(FallbackController::class);
