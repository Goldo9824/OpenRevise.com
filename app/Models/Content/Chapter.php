<?php

namespace App\Models\Content;

use App\Models\Concerns\HasSlug;
use App\Models\Concerns\TracksPathChanges;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chapter extends ContentModel
{
    use HasSlug, TracksPathChanges;

    protected $casts = ['status' => Status::class];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('position')->orderBy('id');
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
        $query->where('topic_id', $this->topic_id);
    }

    public function path(): string
    {
        return $this->topic->path().$this->slug.'/';
    }

    protected function originalPath(): ?string
    {
        $topic = Topic::find($this->getOriginal('topic_id'));

        return $topic ? $topic->path().$this->getOriginal('slug').'/' : null;
    }

    protected function pathAttributes(): array
    {
        return ['slug', 'topic_id'];
    }
}
