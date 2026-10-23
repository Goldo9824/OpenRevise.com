<?php

namespace App\Support;

use App\Models\Content\Board;
use App\Models\Content\Subject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Small cached lists used in headers and footers on every page. The cache
 * holds plain arrays: it never unserializes objects.
 */
class Navigation
{
    /** @return Collection<int, Board> */
    public static function boards(): Collection
    {
        return static::safely(fn () => Board::hydrate(
            Cache::remember('nav.boards', 600, fn () => Board::query()->ordered()->get()->map->getAttributes()->all())
        ));
    }

    /** @return Collection<int, Subject> */
    public static function footerSubjects(): Collection
    {
        return static::safely(fn () => Subject::hydrate(
            Cache::remember('nav.subjects', 600, fn () => Subject::query()->visible()->ordered()->limit(8)->get()->map->getAttributes()->all())
        ));
    }

    public static function forget(): void
    {
        Cache::forget('nav.boards');
        Cache::forget('nav.subjects');
    }

    /** The presentation pages must still load if the content database is down. */
    protected static function safely(callable $callback): Collection
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            report($e);

            return collect();
        }
    }
}
