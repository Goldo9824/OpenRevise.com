<?php

namespace App\Http\Controllers\App;

use Illuminate\Database\Eloquent\Model;

/**
 * Drafts are hidden from students. The admin panel links to a draft with a
 * ?preview= token so it can be checked before it goes live.
 */
class Preview
{
    public static function token(Model $model): string
    {
        return substr(hash_hmac('sha256', class_basename($model).':'.$model->getKey(), (string) config('app.key')), 0, 24);
    }

    public static function url(Model $model): string
    {
        return app_url($model->path()).'?preview='.static::token($model);
    }

    public static function allows(Model $model): bool
    {
        $token = request()->query('preview');

        return is_string($token) && hash_equals(static::token($model), $token);
    }
}
