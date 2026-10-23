<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MockExamQuestion extends ContentModel
{
    protected $casts = ['options' => 'array'];

    public function mockExam(): BelongsTo
    {
        return $this->belongsTo(MockExam::class);
    }

    public function changeLabel(): string
    {
        return 'Question '.($this->number ?: $this->id).' of mock exam '.$this->mock_exam_id;
    }
}
