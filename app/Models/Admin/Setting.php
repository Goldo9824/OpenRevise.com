<?php

namespace App\Models\Admin;

use Illuminate\Support\Facades\Cache;
use Throwable;

/** Site settings the admin can change without touching the server. */
class Setting extends AdminModel
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            $all = Cache::remember('openrevise.settings', 300, fn () => static::query()->pluck('value', 'key')->all());
        } catch (Throwable) {
            return $default;
        }

        return array_key_exists($key, $all) && $all[$key] !== null && $all[$key] !== '' ? $all[$key] : $default;
    }

    public static function put(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('openrevise.settings');
    }
}
