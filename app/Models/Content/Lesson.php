<?php

namespace App\Models\Content;

use App\Content\LessonFiles;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\TracksPathChanges;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends ContentModel
{
    use HasSlug, TracksPathChanges;

    protected $casts = [
        'status' => Status::class,
        'tier' => Tier::class,
        'course' => Course::class,
        'published_at' => 'datetime',
    ];

    /**
     * Board slugs appended to a generated slug, for lessons written for some
     * of a subject's boards only (e.g. required-practical-microscopy-aqa).
     *
     * @var list<string>
     */
    public array $slugBoardSuffix = [];

    protected static function booted(): void
    {
        static::saving(function (Lesson $lesson) {
            if ($lesson->status === Status::Published && ! $lesson->published_at) {
                $lesson->published_at = now();
            }
        });

        static::deleted(fn (Lesson $lesson) => app(LessonFiles::class)->delete($lesson));
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function boards(): BelongsToMany
    {
        return $this->belongsToMany(Board::class)->orderBy('position');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class)->orderBy('position')->orderBy('id');
    }

    public function scopeVisible(Builder $query): void
    {
        $query->where('status', Status::Published);
    }

    public function scopeForBoard(Builder $query, Board|int $board): void
    {
        $id = $board instanceof Board ? $board->id : $board;
        $query->whereHas('boards', fn (Builder $q) => $q->where('boards.id', $id));
    }

    public function slugSource(): string
    {
        return trim($this->title.' '.implode(' ', $this->slugBoardSuffix));
    }

    public function slugScope(Builder $query): void
    {
        $query->where('chapter_id', $this->chapter_id);
    }

    public function reservedSlugs(): array
    {
        return ['exam-questions'];
    }

    public function path(): string
    {
        return $this->chapter->path().$this->slug.'/';
    }

    public function questionsPath(): string
    {
        return $this->path().'exam-questions/';
    }

    protected function originalPath(): ?string
    {
        $chapter = Chapter::find($this->getOriginal('chapter_id'));

        return $chapter ? $chapter->path().$this->getOriginal('slug').'/' : null;
    }

    protected function pathAttributes(): array
    {
        return ['slug', 'chapter_id'];
    }

    public function body(): string
    {
        return app(LessonFiles::class)->read($this);
    }

    public function isPublished(): bool
    {
        return $this->status === Status::Published;
    }

    /** @return list<string> */
    public function boardSlugs(): array
    {
        return $this->boards->pluck('slug')->all();
    }
}
