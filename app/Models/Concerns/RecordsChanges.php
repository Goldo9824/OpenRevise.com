<?php

namespace App\Models\Concerns;

use App\Models\Admin\ContentChange;
use App\Support\Actor;
use Illuminate\Support\Str;
use Throwable;

/**
 * Writes a line to the change history (Admin DB) every time a piece of
 * content is created, edited or deleted, with who did it.
 */
trait RecordsChanges
{
    public static function bootRecordsChanges(): void
    {
        static::created(fn ($model) => $model->recordChange('created'));
        static::updated(fn ($model) => $model->recordChange('updated'));
        static::deleted(fn ($model) => $model->recordChange('deleted'));
    }

    protected function recordChange(string $event): void
    {
        $changes = null;

        if ($event === 'updated') {
            $changes = collect($this->getChanges())
                ->except(['updated_at', 'created_at'])
                ->map(fn ($new, $key) => ['from' => $this->stringify($this->getOriginal($key)), 'to' => $this->stringify($new)])
                ->all();

            if (! $changes) {
                return;
            }
        }

        try {
            ContentChange::create([
                'subject_type' => Str::kebab(class_basename($this)),
                'subject_id' => $this->getKey(),
                'subject_label' => Str::limit($this->changeLabel(), 190),
                'event' => $event,
                'changes' => $changes,
                'causer' => Actor::current(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function changeLabel(): string
    {
        return (string) ($this->title ?? $this->name ?? class_basename($this).' '.$this->getKey());
    }

    private function stringify(mixed $value): mixed
    {
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i');
        }

        return is_string($value) ? Str::limit($value, 300) : $value;
    }
}
