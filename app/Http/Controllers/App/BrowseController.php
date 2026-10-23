<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Content\Board;
use App\Models\Content\Status;
use App\Models\Content\Subject;
use App\Support\Seo;
use App\Support\SubjectOutline;
use Illuminate\Support\Str;
use Illuminate\View\View;

/** Subject, board, topic and chapter pages: the way down to a lesson. */
class BrowseController extends Controller
{
    public function subject(string $subject): View
    {
        $subject = $this->subjectBySlug($subject);
        $outline = SubjectOutline::for($subject);
        $url = app_url($subject->path());

        return view('app.subject', [
            'subject' => $subject,
            'outline' => $outline,
            'seo' => [
                'title' => Seo::title($subject->fullName().' revision'),
                'description' => $subject->description
                    ? Str::limit(strip_tags($subject->description), 155)
                    : "Free {$subject->fullName()} revision notes, exam questions and mock exams for ".$subject->boards->pluck('name')->join(', ', ' and ').'.',
                'canonical' => $url,
                'jsonLd' => [Seo::breadcrumbs([['Subjects', app_url('/gcse/')], [$subject->fullName(), $url]])],
            ],
        ]);
    }

    /** /gcse/biology/aqa/ is a board page; /gcse/biology/cell-biology/ is a topic. */
    public function segment(string $subject, string $segment): View
    {
        $subject = $this->subjectBySlug($subject);

        $board = $subject->boards->firstWhere('slug', $segment);

        return $board ? $this->board($subject, $board) : $this->topic($subject, $segment);
    }

    protected function board(Subject $subject, Board $board): View
    {
        $outline = SubjectOutline::for($subject);
        $topics = $outline->topics->filter(
            fn ($t) => $t->chapters->flatMap->lessons->contains(fn ($l) => $l->boards->contains('id', $board->id))
        );

        $mocks = $subject->mockExams()->visible()->where('board_id', $board->id)->with('paperTemplate')->orderBy('position')->get();
        $url = app_url($subject->path().$board->slug.'/');

        return view('app.board', [
            'subject' => $subject,
            'board' => $board,
            'specCode' => $board->pivot?->spec_code,
            'outline' => $outline,
            'topics' => $topics,
            'mocks' => $mocks,
            'seo' => [
                'title' => Seo::title("{$board->name} {$subject->fullName()} revision"),
                'description' => "Every {$board->name} {$subject->fullName()} topic: free revision notes and exam questions with mark schemes, written for the {$board->name} specification.",
                'canonical' => $url,
                'jsonLd' => [Seo::breadcrumbs([
                    ['Subjects', app_url('/gcse/')],
                    [$subject->fullName(), app_url($subject->path())],
                    [$board->name, $url],
                ])],
            ],
        ]);
    }

    protected function topic(Subject $subject, string $slug): View
    {
        $outline = SubjectOutline::for($subject);
        $topic = $outline->topics->firstWhere('slug', $slug);

        if (! $topic) {
            // Not published (or empty): only reachable with a preview link.
            $topic = $subject->topics()->where('slug', $slug)->firstOrFail();
            abort_unless(Preview::allows($topic), 404);
            $topic->setRelation('subject', $subject);
            $topic->load(['chapters.lessons' => fn ($l) => $l->with('boards')->withCount('questions')]);
        }

        $url = app_url($topic->path());

        return view('app.topic', [
            'subject' => $subject,
            'topic' => $topic,
            'outline' => $outline,
            'seo' => [
                'title' => Seo::title($topic->title, $subject->fullName()),
                'description' => $topic->description
                    ? Str::limit(strip_tags($topic->description), 155)
                    : "{$topic->title}: {$subject->fullName()} revision notes and exam questions, lesson by lesson.",
                'canonical' => $url,
                'jsonLd' => [Seo::breadcrumbs([
                    ['Subjects', app_url('/gcse/')],
                    [$subject->fullName(), app_url($subject->path())],
                    [$topic->title, $url],
                ])],
            ],
        ]);
    }

    public function chapter(string $subject, string $topic, string $chapter): View
    {
        $subject = $this->subjectBySlug($subject);
        $topic = $subject->topics()->where('slug', $topic)->firstOrFail();
        $topic->setRelation('subject', $subject);
        $chapter = $topic->chapters()->where('slug', $chapter)->firstOrFail();
        $chapter->setRelation('topic', $topic);

        abort_unless(($topic->status === Status::Published && $chapter->status === Status::Published) || Preview::allows($chapter), 404);

        $lessons = $chapter->lessons()->visible()->with('boards')->withCount('questions')->withSum('questions', 'marks')->get();
        $chapter->setRelation('lessons', $lessons);

        abort_if($lessons->isEmpty() && ! Preview::allows($chapter), 404);

        $url = app_url($chapter->path());

        return view('app.chapter', [
            'subject' => $subject,
            'topic' => $topic,
            'chapter' => $chapter,
            'lessons' => $lessons,
            'outline' => SubjectOutline::for($subject),
            'seo' => [
                'title' => Seo::title($chapter->title, $topic->title, $subject->fullName()),
                'description' => $chapter->description
                    ? Str::limit(strip_tags($chapter->description), 155)
                    : "{$chapter->title} ({$topic->title}): {$lessons->count()} short {$subject->fullName()} lessons with exam questions and mark schemes.",
                'canonical' => $url,
                'jsonLd' => [Seo::breadcrumbs([
                    ['Subjects', app_url('/gcse/')],
                    [$subject->fullName(), app_url($subject->path())],
                    [$topic->title, app_url($topic->path())],
                    [$chapter->title, $url],
                ])],
            ],
        ]);
    }

    protected function subjectBySlug(string $slug): Subject
    {
        $subject = Subject::query()->where('slug', $slug)->with('boards')->firstOrFail();
        abort_unless($subject->status === Status::Published || Preview::allows($subject), 404);

        return $subject;
    }
}
