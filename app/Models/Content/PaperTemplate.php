<?php

namespace App\Models\Content;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The shape of a real paper for one board and subject: time allowed, total
 * marks, front-cover instructions and sections. A mock exam picks one, so
 * nobody has to type the time and marks again.
 */
class PaperTemplate extends ContentModel
{
    use HasSlug;

    protected $casts = ['sections' => 'array'];

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function mockExams(): HasMany
    {
        return $this->hasMany(MockExam::class);
    }

    public function slugSource(): string
    {
        return $this->name;
    }

    public function slugScope(Builder $query): void
    {
        $query->where('board_id', $this->board_id)->where('subject_id', $this->subject_id);
    }

    public function durationLabel(): string
    {
        $h = intdiv($this->duration_minutes, 60);
        $m = $this->duration_minutes % 60;

        return trim(($h ? $h.' hour'.($h > 1 ? 's' : '') : '').' '.($m ? $m.' minutes' : ''));
    }

    public function tierLabel(): ?string
    {
        return match ($this->tier) {
            'foundation' => 'Foundation tier',
            'higher' => 'Higher tier',
            default => null,
        };
    }

    /** @return list<string> */
    public function instructionLines(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $this->instructions))));
    }

    /** @return list<string> */
    public function informationLines(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $this->information))));
    }
}
