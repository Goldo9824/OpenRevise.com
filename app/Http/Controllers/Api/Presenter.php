<?php

namespace App\Http\Controllers\Api;

use App\Models\Content\Chapter;
use App\Models\Content\ExamQuestion;
use App\Models\Content\Image;
use App\Models\Content\Lesson;
use App\Models\Content\MockExam;
use App\Models\Content\MockExamQuestion;
use App\Models\Content\PaperTemplate;
use App\Models\Content\Topic;

/** How content looks in API responses. */
class Presenter
{
    public static function topic(Topic $topic): array
    {
        return [
            'id' => $topic->id,
            'subject_id' => $topic->subject_id,
            'title' => $topic->title,
            'slug' => $topic->slug,
            'status' => $topic->status->value,
            'position' => $topic->position,
            'url' => app_url($topic->path()),
        ];
    }

    public static function chapter(Chapter $chapter): array
    {
        return [
            'id' => $chapter->id,
            'topic_id' => $chapter->topic_id,
            'title' => $chapter->title,
            'slug' => $chapter->slug,
            'status' => $chapter->status->value,
            'position' => $chapter->position,
            'url' => app_url($chapter->path()),
        ];
    }

    public static function lesson(Lesson $lesson, bool $withBody = false): array
    {
        $data = [
            'id' => $lesson->id,
            'chapter_id' => $lesson->chapter_id,
            'title' => $lesson->title,
            'slug' => $lesson->slug,
            'summary' => $lesson->summary,
            'boards' => $lesson->boards->pluck('slug')->all(),
            'tier' => $lesson->tier->value,
            'course' => $lesson->course->value,
            'status' => $lesson->status->value,
            'position' => $lesson->position,
            'word_count' => $lesson->word_count,
            'questions' => $lesson->questions_count ?? $lesson->questions()->count(),
            'url' => app_url($lesson->path()),
            'updated_at' => $lesson->updated_at?->toIso8601String(),
        ];

        if ($withBody) {
            $data['body'] = $lesson->body();
        }

        return $data;
    }

    public static function question(ExamQuestion|MockExamQuestion $q): array
    {
        return array_filter([
            'id' => $q->id,
            'number' => $q->number ?? null,
            'section' => $q->section ?? null,
            'type' => $q->type,
            'question' => $q->question,
            'options' => $q->options,
            'answer' => $q->answer,
            'marks' => $q->marks,
            'mark_scheme' => $q->mark_scheme,
            'model_answer' => $q->model_answer,
            'board' => $q instanceof ExamQuestion ? $q->board?->slug : null,
            'position' => $q->position,
        ], fn ($v) => $v !== null);
    }

    public static function template(PaperTemplate $t): array
    {
        return [
            'id' => $t->id,
            'slug' => $t->slug,
            'name' => $t->name,
            'subject' => $t->subject?->slug,
            'tier' => $t->tier,
            'duration_minutes' => $t->duration_minutes,
            'total_marks' => $t->total_marks,
            'sections' => $t->sections ?? [],
        ];
    }

    public static function mockExam(MockExam $exam): array
    {
        return [
            'id' => $exam->id,
            'title' => $exam->title,
            'slug' => $exam->slug,
            'subject' => $exam->subject->slug,
            'board' => $exam->board->slug,
            'paper_template' => $exam->paperTemplate->slug,
            'duration_minutes' => $exam->paperTemplate->duration_minutes,
            'total_marks' => $exam->paperTemplate->total_marks,
            'question_marks' => $exam->totalMarks(),
            'questions' => $exam->questions->count(),
            'status' => $exam->status->value,
            'url' => app_url($exam->path()),
        ];
    }

    public static function image(Image $image): array
    {
        return [
            'id' => $image->id,
            'name' => $image->name,
            'tag' => $image->tag(),
            'url' => $image->url(),
            'mime' => $image->mime,
            'width' => $image->width,
            'height' => $image->height,
            'size_bytes' => $image->size_bytes,
        ];
    }
}
