<?php

namespace App\Filament\Widgets;

use Illuminate\Support\Collection;

class TopLessons extends RankedList
{
    protected static ?int $sort = 3;

    protected function heading(): string
    {
        return 'Top lessons';
    }

    protected function rows(): Collection
    {
        return $this->reports()->topLessons(10);
    }
}
