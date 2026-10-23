<?php

namespace App\Models\Content;

use App\Models\Concerns\HasSlug;
use App\Models\Concerns\TracksPathChanges;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Topic extends ContentModel
{
    use HasSlug, TracksPathChanges;

    protected $casts = ['status' => Status::class];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class)->orderBy('position')->orderBy('id');
    }

    public function scopeVisible(Builder $query): void
    {
        $query->where('status', Status::Published);
    }

    public function slugSource(): string
    {
        return $this->title;
    }

    public function slugScope(Builder $query): void
    {
        $query->where('subject_id', $this->subject_id);
    }

    /** Topics share the URL segment with board pages and the mock exam list. */
    public function reservedSlugs(): array
    {
        return [...Board::slugs()->all(), 'mock-exams', 'exam-questions'];
    }

    public function path(): string
    {
        return $this->subject->path().$this->slug.'/';
    }

    protected function originalPath(): ?string
    {
        $subject = Subject::find($this->getOriginal('subject_id'));

        return $subject ? $subject->path().$this->getOriginal('slug').'/' : null;
    }

    protected function pathAttributes(): array
    {
        return ['slug', 'subject_id'];
    }
}
