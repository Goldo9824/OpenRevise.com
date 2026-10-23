<?php

namespace App\Models\Content;

use App\Models\Concerns\HasSlug;
use App\Models\Concerns\TracksPathChanges;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MockExam extends ContentModel
{
    use HasSlug, TracksPathChanges;

    protected $casts = [
        'status' => Status::class,
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (MockExam $exam) {
            if ($exam->status === Status::Published && ! $exam->published_at) {
                $exam->published_at = now();
            }
        });
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function paperTemplate(): BelongsTo
    {
        return $this->belongsTo(PaperTemplate::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(MockExamQuestion::class)->orderBy('position')->orderBy('id');
    }

    public function scopeVisible(Builder $query): void
    {
        $query->where('status', Status::Published);
    }

    /** aqa-paper-1, aqa-paper-1-higher, then -2, -3 for further sets. */
    public function slugSource(): string
    {
        $board = $this->board ?? Board::find($this->board_id);
        $template = $this->paperTemplate ?? PaperTemplate::find($this->paper_template_id);

        return trim(($board?->slug ?? '').' '.($template?->name ?? $this->title));
    }

    public function slugScope(Builder $query): void
    {
        $query->where('subject_id', $this->subject_id);
    }

    public function path(): string
    {
        return $this->subject->path().'mock-exams/'.$this->slug.'/';
    }

    protected function originalPath(): ?string
    {
        $subject = Subject::find($this->getOriginal('subject_id'));

        return $subject ? $subject->path().'mock-exams/'.$this->getOriginal('slug').'/' : null;
    }

    protected function pathAttributes(): array
    {
        return ['slug', 'subject_id'];
    }

    public function totalMarks(): int
    {
        return (int) $this->questions->sum('marks');
    }
}
