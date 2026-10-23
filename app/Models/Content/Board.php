<?php

namespace App\Models\Content;

use App\Support\Navigation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Board extends ContentModel
{
    protected static function booted(): void
    {
        static::saved(fn () => Navigation::forget());
        static::deleted(fn () => Navigation::forget());
        static::saved(fn () => static::$slugCache = null);
        static::deleted(fn () => static::$slugCache = null);
    }

    /** @var Collection<int, string>|null */
    protected static ?Collection $slugCache = null;

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class)->withPivot('spec_code', 'spec_url');
    }

    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class);
    }

    public function paperTemplates(): HasMany
    {
        return $this->hasMany(PaperTemplate::class)->orderBy('position');
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('name');
    }

    /**
     * Board slugs are reserved words in app URLs: /gcse/biology/aqa/ is a
     * board page, so no topic may take that slug.
     *
     * @return Collection<int, string>
     */
    public static function slugs(): Collection
    {
        return static::$slugCache ??= static::query()->pluck('slug');
    }
}
