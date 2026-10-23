<?php

namespace App\Content;

use App\Models\Content\Board;
use App\Models\Content\Chapter;
use App\Models\Content\Lesson;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Creating and editing lessons, shared by the API and the admin panel: the
 * details go into the Content DB and the body into its text file.
 */
class LessonService
{
    public function __construct(protected LessonFiles $files) {}

    /**
     * @param array{chapter_id: int, title: string, body?: string, boards?: list<int>, slug?: ?string,
     *     summary?: ?string, tier?: string, course?: string, status?: string, position?: ?int} $data
     */
    public function create(array $data): Lesson
    {
        return DB::connection('content')->transaction(function () use ($data) {
            $chapter = Chapter::with('topic.subject.boards')->findOrFail($data['chapter_id']);
            $boardIds = array_values(array_unique(array_map('intval', $data['boards'] ?? [])));

            $lesson = new Lesson(Arr::only($data, ['chapter_id', 'title', 'summary', 'tier', 'course', 'status']));
            $lesson->slug = $data['slug'] ?? null;
            $lesson->position = $data['position'] ?? ((int) Lesson::where('chapter_id', $chapter->id)->max('position') + 1);

            if (! $lesson->slug) {
                $lesson->slugBoardSuffix = $this->boardSuffix($chapter, $boardIds);
            }

            $lesson->save();
            $lesson->boards()->sync($boardIds);

            $this->saveBody($lesson, $data['body'] ?? '');

            return $lesson->fresh(['boards', 'chapter.topic.subject']);
        });
    }

    /** @param array<string, mixed> $data Only the keys present are changed. */
    public function update(Lesson $lesson, array $data): Lesson
    {
        return DB::connection('content')->transaction(function () use ($lesson, $data) {
            $lesson->fill(Arr::only($data, ['chapter_id', 'title', 'summary', 'tier', 'course', 'status', 'position']));

            if (array_key_exists('slug', $data) && $data['slug']) {
                $lesson->slug = $data['slug'];
            }

            $lesson->save();

            if (array_key_exists('boards', $data)) {
                $lesson->boards()->sync(array_map('intval', $data['boards']));
            }

            if (array_key_exists('body', $data)) {
                $this->saveBody($lesson, (string) $data['body']);
            }

            return $lesson->fresh(['boards', 'chapter.topic.subject']);
        });
    }

    public function saveBody(Lesson $lesson, string $body): void
    {
        $this->files->write($lesson, $body);

        $doc = app(LessonParser::class)->parse($body);
        $lesson->forceFill(['word_count' => $doc->wordCount])->saveQuietly();
    }

    /**
     * A lesson written for one or two of the boards that teach the subject
     * gets their names on the end of its slug, so each board's version of
     * "Required practical: microscopy" has its own address.
     *
     * @param  list<int>  $boardIds
     * @return list<string>
     */
    public function boardSuffix(Chapter $chapter, array $boardIds): array
    {
        if ($boardIds === []) {
            return [];
        }

        $subjectBoards = $chapter->topic->subject->boards->pluck('id')->all() ?: Board::pluck('id')->all();

        if (array_diff($subjectBoards, $boardIds) === []) {
            return [];
        }

        return Board::whereIn('id', $boardIds)->orderBy('position')->pluck('slug')->all();
    }
}
