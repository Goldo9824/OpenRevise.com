<?php

namespace App\Models\Content;

/** Who a lesson is for, by exam tier. A lesson with no tag is for everyone. */
enum Tier: string
{
    case All = 'all';
    case Higher = 'higher';

    public function label(): string
    {
        return match ($this) {
            self::All => 'All tiers',
            self::Higher => 'Higher tier only',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $t) => [$t->value => $t->label()])->all();
    }
}
