<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamQuestion extends ContentModel
{
    protected $casts = ['options' => 'array'];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function changeLabel(): string
    {
        return 'Question '.$this->id.' of lesson '.$this->lesson_id;
    }
}
