<?php

namespace App\Filament\Widgets;

use Illuminate\Support\Collection;

class TopDevices extends RankedList
{
    protected static ?int $sort = 9;

    protected function heading(): string
    {
        return 'Devices';
    }

    protected function rows(): Collection
    {
        return $this->reports()->breakdown('device');
    }
}
