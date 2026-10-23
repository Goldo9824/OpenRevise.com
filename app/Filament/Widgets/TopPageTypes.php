<?php

namespace App\Filament\Widgets;

use Illuminate\Support\Collection;

class TopPageTypes extends RankedList
{
    protected static ?int $sort = 10;

    protected function heading(): string
    {
        return 'Kinds of page';
    }

    protected function rows(): Collection
    {
        return $this->reports()->breakdown('page_type')->map(fn ($r) => ['label' => ucfirst(str_replace('-', ' ', $r['label']))] + $r);
    }
}
