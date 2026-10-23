<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Content\Board;
use App\Models\Content\Lesson;
use App\Models\Content\Status;
use App\Models\Content\Subject;
use App\Support\Seo;
use App\Support\SubjectOutline;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HomeController extends Controller
{
    /** Pick an exam board and a subject. */
    public function index(): View
    {
        $subjects = Subject::query()->visible()->ordered()->with('boards')->get();
        $boards = Board::query()->ordered()->get();

        // Published lessons per subject, for the covers.
        $counts = Lesson::query()
            ->where('lessons.status', Status::Published)
            ->join('chapters', 'chapters.id', '=', 'lessons.chapter_id')
            ->join('topics', 'topics.id', '=', 'chapters.topic_id')
            ->groupBy('topics.subject_id')
            ->select('topics.subject_id', DB::raw('count(*) as total'))
            ->pluck('total', 'subject_id');

        return view('app.home', [
            'subjects' => $subjects,
            'boards' => $boards,
            'counts' => $counts,
            'seo' => [
                'title' => 'GCSE revision notes, exam questions and mock exams | OpenRevise',
                'description' => 'Free GCSE revision for AQA, Edexcel and OCR: notes for every topic, exam questions with mark schemes after every lesson, and mock exams. No sign-up.',
                'canonical' => app_url('/'),
                'jsonLd' => [Seo::website()],
            ],
        ]);
    }

    /** /gcse/: every subject with its topics. */
    public function subjects(): View
    {
        $subjects = Subject::query()->visible()->ordered()->with('boards')->get();

        return view('app.subjects', [
            'subjects' => $subjects,
            'outlines' => $subjects->mapWithKeys(fn (Subject $s) => [$s->id => SubjectOutline::for($s)]),
            'seo' => [
                'title' => Seo::title('GCSE subjects'),
                'description' => 'Every GCSE subject on OpenRevise, with its topics: free notes, exam questions and mock exams for AQA, Edexcel and OCR.',
                'canonical' => app_url('/gcse/'),
                'jsonLd' => [Seo::breadcrumbs([['Home', app_url('/')], ['Subjects', app_url('/gcse/')]])],
            ],
        ]);
    }

    /** /saved/: lessons saved with the bookmark button (kept in the browser). */
    public function saved(): View
    {
        return view('app.saved', [
            'seo' => [
                'title' => Seo::title('Saved lessons'),
                'description' => 'Lessons you saved on this device.',
                'canonical' => app_url('/saved/'),
                'robots' => 'noindex, follow',
            ],
        ]);
    }
}
