<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Clean slugs: lowercase words joined by hyphens, taken from the title, with
 * no IDs. A slug that is taken, or reserved, gets -2, -3 and so on.
 */
trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::saving(function ($model) {
            $slug = Str::slug((string) ($model->slug ?: $model->slugSource()));
            $model->slug = $model->uniqueSlug($slug !== '' ? $slug : 'untitled');
        });
    }

    abstract public function slugSource(): string;

    public function slugScope(Builder $query): void
    {
        //
    }

    /** @return list<string> */
    public function reservedSlugs(): array
    {
        return [];
    }

    public function uniqueSlug(string $slug): string
    {
        $slug = Str::limit($slug, 150, '');
        $reserved = $this->reservedSlugs();
        $candidate = $slug;
        $n = 2;

        while (in_array($candidate, $reserved, true) || $this->slugTaken($candidate)) {
            $candidate = $slug.'-'.$n++;
        }

        return $candidate;
    }

    protected function slugTaken(string $slug): bool
    {
        $query = static::query()->where('slug', $slug);
        $this->slugScope($query);

        if ($this->exists) {
            $query->whereKeyNot($this->getKey());
        }

        return $query->exists();
    }
}
