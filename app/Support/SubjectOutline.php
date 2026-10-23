<?php

namespace App\Support;

use App\Models\Content\Lesson;
use App\Models\Content\Subject;
use App\Models\Content\Topic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Everything published in one subject, in order: what the sidebar lists,
 * and where lesson numbers such as "1.2" come from (topic 1, lesson 2).
 * Loaded once per request.
 */
class SubjectOutline
{
    /** @var array<int, self> */
    protected static array $loaded = [];

    /** @var Collection<int, Topic> */
    public Collection $topics;

    /** @var Collection<int, Lesson> In reading order, across the whole subject. */
    public Collection $lessons;

    /** @var array<int, int> topic id => number */
    protected array $topicNumbers = [];

    /** @var array<int, string> lesson id => "1.2" */
    protected array $lessonNumbers = [];

    public int $mockExams = 0;

    public function __construct(public Subject $subject)
    {
        $visibleLessons = fn ($q) => $q->visible()->with('boards')->withCount('questions');

        $this->topics = $subject->topics()
            ->visible()
            ->whereHas('chapters', fn (Builder $q) => $q->visible()->whereHas('lessons', fn (Builder $l) => $l->visible()))
            ->with(['chapters' => fn ($q) => $q->visible()->whereHas('lessons', fn (Builder $l) => $l->visible())->with(['lessons' => $visibleLessons])])
            ->get();

        $this->lessons = collect();

        foreach ($this->topics as $t => $topic) {
            $topic->setRelation('subject', $subject);
            $this->topicNumbers[$topic->id] = $t + 1;
            $n = 0;

            foreach ($topic->chapters as $chapter) {
                $chapter->setRelation('topic', $topic);

                foreach ($chapter->lessons as $lesson) {
                    $lesson->setRelation('chapter', $chapter);
                    $this->lessonNumbers[$lesson->id] = ($t + 1).'.'.(++$n);
                    $this->lessons->push($lesson);
                }
            }
        }

        $this->mockExams = $subject->mockExams()->visible()->count();
    }

    public static function for(Subject $subject): self
    {
        return static::$loaded[$subject->id] ??= new self($subject);
    }

    public static function flush(): void
    {
        static::$loaded = [];
    }

    public function topicNumber(Topic|int $topic): ?int
    {
        return $this->topicNumbers[$topic instanceof Topic ? $topic->id : $topic] ?? null;
    }

    public function lessonNumber(Lesson|int $lesson): ?string
    {
        return $this->lessonNumbers[$lesson instanceof Lesson ? $lesson->id : $lesson] ?? null;
    }

    public function lessonCount(): int
    {
        return $this->lessons->count();
    }

    /** @return array{0: ?Lesson, 1: ?Lesson} */
    public function neighbours(Lesson $lesson): array
    {
        $index = $this->lessons->search(fn (Lesson $l) => $l->id === $lesson->id);

        if ($index === false) {
            return [null, null];
        }

        return [$this->lessons->get($index - 1), $this->lessons->get($index + 1)];
    }
}
