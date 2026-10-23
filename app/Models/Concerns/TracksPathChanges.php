<?php

namespace App\Models\Concerns;

use App\Models\Content\SlugRedirect;

/**
 * Keeps old addresses alive: when a slug changes, or a page moves to a new
 * parent, its old path is stored and answered with a 301.
 */
trait TracksPathChanges
{
    public static function bootTracksPathChanges(): void
    {
        static::updating(function ($model) {
            if (! $model->isDirty($model->pathAttributes())) {
                return;
            }

            $old = $model->originalPath();

            if ($old) {
                SlugRedirect::query()->where('old_path', $old)->delete();
                SlugRedirect::create([
                    'old_path' => $old,
                    'target_type' => array_search(static::class, SlugRedirect::TARGETS, true),
                    'target_id' => $model->getKey(),
                ]);
            }
        });

        static::deleted(function ($model) {
            SlugRedirect::query()
                ->where('target_type', array_search(static::class, SlugRedirect::TARGETS, true))
                ->where('target_id', $model->getKey())
                ->delete();
        });
    }

    abstract public function path(): string;

    abstract protected function originalPath(): ?string;

    /** @return list<string> */
    abstract protected function pathAttributes(): array;
}
