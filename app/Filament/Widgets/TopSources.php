<?php

namespace App\Filament\Widgets;

use Illuminate\Support\Collection;

class TopSources extends RankedList
{
    protected static ?int $sort = 4;

    protected function heading(): string
    {
        return 'Where visitors come from';
    }

    protected function rows(): Collection
    {
        return $this->reports()->breakdown('source');
    }
}
