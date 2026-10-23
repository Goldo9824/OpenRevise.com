<?php

namespace App\Http\Controllers\App;

use App\Content\LessonParser;
use App\Http\Controllers\Controller;
use App\Models\Content\MockExam;
use App\Models\Content\Status;
use App\Models\Content\Subject;
use App\Support\Seo;
use Illuminate\View\View;

class MockExamController extends Controller
{
    /** Mock exams for one subject, grouped by board. */
    public function index(string $subject): View
    {
        $subject = Subject::where('slug', $subject)->visible()->with('boards')->firstOrFail();

        $exams = $subject->mockExams()->visible()
            ->with(['board', 'paperTemplate', 'topic'])
            ->withCount('questions')
            ->orderBy('position')
            ->get()
            ->groupBy(fn (MockExam $e) => $e->board->name);

        $url = app_url($subject->path().'mock-exams/');

        return view('app.mock-index', [
            'subject' => $subject,
            'groups' => $exams,
            'seo' => [
                'title' => Seo::title($subject->fullName().' mock exams'),
                'description' => "Free {$subject->fullName()} mock exams for ".$subject->boards->pluck('name')->join(', ', ' and ').': timed papers on screen, with mark schemes to mark your own answers.',
                'canonical' => $url,
                'jsonLd' => [Seo::breadcrumbs([
                    ['Subjects', app_url('/')],
                    [$subject->fullName(), app_url($subject->path())],
                    ['Mock exams', $url],
                ])],
            ],
        ]);
    }

    /** One paper: the cover, then the questions, timed or untimed. */
    public function show(string $subject, string $exam): View
    {
        $subject = Subject::where('slug', $subject)->firstOrFail();
        $exam = $subject->mockExams()->where('slug', $exam)->with(['board', 'paperTemplate', 'topic', 'questions'])->firstOrFail();
        $exam->setRelation('subject', $subject);

        $live = $exam->status === Status::Published && $subject->status === Status::Published;
        abort_unless($live || Preview::allows($exam), 404);

        if (! $live) {
            request()->attributes->set('no_cache', true);
        }

        $parser = app(LessonParser::class);
        $number = 0;
        $questions = $exam->questions->map(function ($q) use ($parser, &$number) {
            $number++;

            return [
                'model' => $q,
                'number' => $q->number ?: str_pad((string) $number, 2, '0', STR_PAD_LEFT),
                'question' => $parser->parse($q->question)->html,
                'mark_scheme' => $parser->parse($q->mark_scheme)->html,
                'model_answer' => $parser->parse($q->model_answer)->html,
            ];
        });

        $url = app_url($exam->path());

        return view('app.mock', [
            'subject' => $subject,
            'exam' => $exam,
            'template' => $exam->paperTemplate,
            'questions' => $questions,
            'sections' => $questions->groupBy(fn ($q) => $q['model']->section ?? ''),
            'seo' => [
                'title' => Seo::title($exam->title.' mock exam'),
                'description' => "{$exam->title}: a free {$exam->paperTemplate->durationLabel()} mock exam out of {$exam->paperTemplate->total_marks} marks. Sit it on screen, timed or untimed, then mark it with the mark scheme.",
                'canonical' => $url,
                'robots' => $live ? 'index, follow' : 'noindex, nofollow',
                'jsonLd' => [Seo::breadcrumbs([
                    ['Subjects', app_url('/')],
                    [$subject->fullName(), app_url($subject->path())],
                    ['Mock exams', app_url($subject->path().'mock-exams/')],
                    [$exam->title, $url],
                ])],
            ],
        ]);
    }
}
