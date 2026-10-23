<?php

namespace App\Filament\Widgets;

use Illuminate\Support\Collection;

class TopBrowsers extends RankedList
{
    protected static ?int $sort = 8;

    protected function heading(): string
    {
        return 'Browsers';
    }

    protected function rows(): Collection
    {
        return $this->reports()->breakdown('browser');
    }
}
