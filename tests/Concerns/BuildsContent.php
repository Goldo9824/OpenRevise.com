<?php

namespace Tests\Concerns;

use App\Content\LessonService;
use App\Models\Content\Board;
use App\Models\Content\Chapter;
use App\Models\Content\Lesson;
use App\Models\Content\Subject;
use App\Models\Content\Topic;

trait BuildsContent
{
    protected Subject $subject;

    protected Topic $topic;

    protected Chapter $chapter;

    protected function buildTree(): void
    {
        $aqa = Board::create(['name' => 'AQA', 'slug' => 'aqa', 'position' => 1]);
        $edexcel = Board::create(['name' => 'Edexcel', 'slug' => 'edexcel', 'position' => 2]);

        $this->subject = Subject::create(['name' => 'Biology', 'status' => 'published']);
        $this->subject->boards()->attach([$aqa->id => ['spec_code' => '8461'], $edexcel->id => ['spec_code' => '1BI0']]);

        $this->topic = Topic::create(['subject_id' => $this->subject->id, 'title' => 'Cell biology']);
        $this->chapter = Chapter::create(['topic_id' => $this->topic->id, 'title' => 'Cell structure']);
    }

    protected function makeLesson(array $overrides = []): Lesson
    {
        return app(LessonService::class)->create($overrides + [
            'chapter_id' => $this->chapter->id,
            'title' => 'Eukaryotic and prokaryotic cells',
            'summary' => 'The two kinds of cell.',
            'boards' => Board::pluck('id')->all(),
            'status' => 'published',
            'tier' => 'all',
            'course' => 'all',
            'body' => "## Two kinds\n\n[KEYTERM] Nucleus: holds the DNA.\n\nCells are small.",
        ]);
    }
}
