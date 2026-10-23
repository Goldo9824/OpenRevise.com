<?php

namespace App\Content;

use Illuminate\Support\Facades\Cache;

/** Bumped whenever an image changes, so cached lesson pages pick it up. */
class ImageVersion
{
    public static function get(): int
    {
        return (int) Cache::get('openrevise.image-version', 0);
    }

    public static function bump(): void
    {
        Cache::forever('openrevise.image-version', static::get() + 1);
    }
}
