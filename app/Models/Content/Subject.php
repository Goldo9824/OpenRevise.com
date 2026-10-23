<?php

namespace App\Models\Content;

use App\Models\Concerns\HasSlug;
use App\Models\Concerns\TracksPathChanges;
use App\Support\Navigation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends ContentModel
{
    use HasSlug, TracksPathChanges;

    protected $casts = ['status' => Status::class];

    protected static function booted(): void
    {
        static::saved(fn () => Navigation::forget());
        static::deleted(fn () => Navigation::forget());
    }

    public function boards(): BelongsToMany
    {
        return $this->belongsToMany(Board::class)->withPivot('spec_code', 'spec_url')->orderBy('position');
    }

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class)->orderBy('position')->orderBy('id');
    }

    public function mockExams(): HasMany
    {
        return $this->hasMany(MockExam::class);
    }

    public function paperTemplates(): HasMany
    {
        return $this->hasMany(PaperTemplate::class);
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('name');
    }

    public function scopeVisible(Builder $query): void
    {
        $query->where('status', Status::Published);
    }

    public function slugSource(): string
    {
        return $this->name;
    }

    public function path(): string
    {
        return '/'.config('openrevise.level').'/'.$this->slug.'/';
    }

    protected function originalPath(): ?string
    {
        return '/'.config('openrevise.level').'/'.$this->getOriginal('slug').'/';
    }

    protected function pathAttributes(): array
    {
        return ['slug'];
    }

    /** "GCSE Biology", used in titles and breadcrumbs. */
    public function fullName(): string
    {
        return strtoupper(config('openrevise.level')).' '.$this->name;
    }

    /** The colour of the subject's exercise-book cover. */
    public function coverColour(): string
    {
        $palette = ['#2f6b4f', '#24508f', '#a83a32', '#5b3f8c', '#b8641b', '#1f6f78', '#8a6d12', '#4d5560', '#9b3d68'];

        return $this->colour ?: $palette[crc32($this->slug) % count($palette)];
    }

    public const ICONS = [
        'book-open' => 'Book', 'leaf' => 'Leaf', 'flask' => 'Flask', 'atom' => 'Atom', 'sigma' => 'Sigma',
        'briefcase' => 'Briefcase', 'monitor' => 'Computer', 'globe' => 'Globe', 'landmark' => 'Building',
        'languages' => 'Languages', 'palette' => 'Palette', 'music' => 'Music',
    ];

    /** The icon shown next to the subject in the sidebar and on its card. */
    public function iconName(): string
    {
        if ($this->icon && isset(self::ICONS[$this->icon])) {
            return $this->icon;
        }

        return match (true) {
            str_contains($this->slug, 'computer') => 'monitor',
            str_contains($this->slug, 'bio') => 'leaf',
            str_contains($this->slug, 'chem'), str_contains($this->slug, 'science') => 'flask',
            str_contains($this->slug, 'phys') => 'atom',
            str_contains($this->slug, 'math') => 'sigma',
            str_contains($this->slug, 'business') || str_contains($this->slug, 'econ') => 'briefcase',
            str_contains($this->slug, 'geog') => 'globe',
            str_contains($this->slug, 'hist') => 'landmark',
            default => 'book-open',
        };
    }
}
