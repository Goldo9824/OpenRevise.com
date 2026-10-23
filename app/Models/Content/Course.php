<?php

namespace App\Models\Content;

/** Who a lesson is for, by science course. A lesson with no tag is for everyone. */
enum Course: string
{
    case All = 'all';
    case Separate = 'separate';

    public function label(): string
    {
        return match ($this) {
            self::All => 'All courses',
            self::Separate => 'Separate Science only',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
