<?php

namespace App\Filament\Widgets;

use Illuminate\Support\Collection;

class TopCountries extends RankedList
{
    protected static ?int $sort = 7;

    protected function heading(): string
    {
        return 'Countries';
    }

    protected function rows(): Collection
    {
        return $this->reports()->breakdown('country')->map(fn ($r) => ['label' => (\Locale::getDisplayRegion('-'.$r['label'], 'en') ?: $r['label'])] + $r);
    }
}
