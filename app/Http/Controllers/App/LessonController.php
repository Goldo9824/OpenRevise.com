<?php

namespace App\Http\Controllers\App;

use App\Content\LessonFiles;
use App\Content\LessonParser;
use App\Http\Controllers\Controller;
use App\Models\Content\Lesson;
use App\Models\Content\Status;
use App\Models\Content\Subject;
use App\Support\Seo;
use App\Support\SubjectOutline;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LessonController extends Controller
{
    public function __construct(protected LessonFiles $files) {}

    /** The notes: key terms, diagrams and the explanation. */
    public function show(string $subject, string $topic, string $chapter, string $lesson): View
    {
        $lesson = $this->find($subject, $topic, $chapter, $lesson);
        $doc = $this->files->render($lesson);

        $questions = $lesson->questions()->get(['id', 'marks']);
        $outline = SubjectOutline::for($lesson->chapter->topic->subject);
        [$previous, $next] = $outline->neighbours($lesson);

        $url = main_url($lesson->path());
        $description = $lesson->summary ?: Str::limit(trim(strip_tags($doc->html)), 155);

        return view('app.lesson', [
            'lesson' => $lesson,
            'chapter' => $lesson->chapter,
            'topic' => $lesson->chapter->topic,
            'subject' => $lesson->chapter->topic->subject,
            'doc' => $doc,
            'questionCount' => $questions->count(),
            'questionMarks' => (int) $questions->sum('marks'),
            'previous' => $previous,
            'next' => $next,
            'outline' => $outline,
            'seo' => [
                'title' => Seo::title($doc->title ?? $lesson->title, $lesson->chapter->topic->subject->fullName()),
                'description' => $description,
                'canonical' => $url,
                'robots' => $lesson->isPublished() ? 'index, follow' : 'noindex, nofollow',
                'ogType' => 'article',
                'jsonLd' => [
                    $this->breadcrumbs($lesson),
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'LearningResource',
                        'name' => $lesson->title,
                        'description' => $description,
                        'url' => $url,
                        'inLanguage' => 'en-GB',
                        'learningResourceType' => 'Revision notes',
                        'educationalLevel' => 'GCSE',
                        'isAccessibleForFree' => true,
                        'about' => $lesson->chapter->topic->subject->name,
                        'teaches' => array_map(fn ($t) => $t['term'], $doc->keyTerms) ?: null,
                        'datePublished' => $lesson->published_at?->toIso8601String(),
                        'dateModified' => $lesson->updated_at?->toIso8601String(),
                        'publisher' => Seo::organisation(),
                    ],
                ],
            ],
        ]);
    }

    /** The lesson's batch of exam questions, on a page of its own. */
    public function questions(string $subject, string $topic, string $chapter, string $lesson): View
    {
        $lesson = $this->find($subject, $topic, $chapter, $lesson);
        $questions = $lesson->questions()->with('board')->get();

        abort_if($questions->isEmpty(), 404);

        $parser = app(LessonParser::class);
        $rendered = $questions->map(fn ($q) => [
            'model' => $q,
            'question' => $parser->parse($q->question)->html,
            'mark_scheme' => $parser->parse($q->mark_scheme)->html,
            'model_answer' => $parser->parse($q->model_answer)->html,
        ]);

        $url = main_url($lesson->questionsPath());
        $subjectModel = $lesson->chapter->topic->subject;

        return view('app.questions', [
            'outline' => SubjectOutline::for($subjectModel),
            'lesson' => $lesson,
            'chapter' => $lesson->chapter,
            'topic' => $lesson->chapter->topic,
            'subject' => $subjectModel,
            'questions' => $rendered,
            'seo' => [
                'title' => Seo::title('Exam questions: '.$lesson->title, $subjectModel->fullName()),
                'description' => $questions->count().' exam-style questions on '.Str::lower($lesson->title).' ('.$questions->sum('marks').' marks), with mark schemes and model answers. Free '.$subjectModel->fullName().' revision.',
                'canonical' => $url,
                'robots' => $lesson->isPublished() ? 'index, follow' : 'noindex, nofollow',
                'jsonLd' => [
                    Seo::breadcrumbs([
                        ['Subjects', main_url('/revise/')],
                        [$subjectModel->fullName(), main_url($subjectModel->path())],
                        [$lesson->chapter->topic->title, main_url($lesson->chapter->topic->path())],
                        [$lesson->chapter->title, main_url($lesson->chapter->path())],
                        [$lesson->title, main_url($lesson->path())],
                        ['Exam questions', $url],
                    ]),
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'Quiz',
                        'name' => 'Exam questions: '.$lesson->title,
                        'url' => $url,
                        'inLanguage' => 'en-GB',
                        'educationalLevel' => 'GCSE',
                        'about' => ['@type' => 'Thing', 'name' => $lesson->title],
                        'hasPart' => $questions->map(fn ($q) => [
                            '@type' => 'Question',
                            'eduQuestionType' => $q->options ? 'Multiple choice' : 'Short answer',
                            'text' => Str::limit(strip_tags($parser->parse($q->question)->html), 500),
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => Str::limit(strip_tags($parser->parse($q->model_answer)->html), 500),
                            ],
                        ])->values()->all(),
                    ],
                ],
            ],
        ]);
    }

    protected function find(string $subject, string $topic, string $chapter, string $lesson): Lesson
    {
        $subjectModel = Subject::where('slug', $subject)->firstOrFail();
        $topicModel = $subjectModel->topics()->where('slug', $topic)->firstOrFail();
        $chapterModel = $topicModel->chapters()->where('slug', $chapter)->firstOrFail();
        $lessonModel = $chapterModel->lessons()->where('slug', $lesson)->with('boards')->firstOrFail();

        $topicModel->setRelation('subject', $subjectModel);
        $chapterModel->setRelation('topic', $topicModel);
        $lessonModel->setRelation('chapter', $chapterModel);

        $live = $lessonModel->isPublished()
            && $chapterModel->status === Status::Published
            && $topicModel->status === Status::Published
            && $subjectModel->status === Status::Published;

        abort_unless($live || Preview::allows($lessonModel), 404);

        if (! $live) {
            request()->attributes->set('no_cache', true);
        }

        return $lessonModel;
    }

    protected function breadcrumbs(Lesson $lesson): array
    {
        $chapter = $lesson->chapter;
        $topic = $chapter->topic;
        $subject = $topic->subject;

        return Seo::breadcrumbs([
            ['Subjects', main_url('/revise/')],
            [$subject->fullName(), main_url($subject->path())],
            [$topic->title, main_url($topic->path())],
            [$chapter->title, main_url($chapter->path())],
            [$lesson->title, main_url($lesson->path())],
        ]);
    }
}
