<?php

namespace App\Filament\Widgets\Concerns;

use App\Analytics\Reports;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

/** Widgets read the dashboard's period filter. */
trait UsesRange
{
    use InteractsWithPageFilters;

    protected function range(): string
    {
        return $this->pageFilters['range'] ?? '30d';
    }

    protected function reports(): Reports
    {
        return new Reports($this->range());
    }

    protected function rangeLabel(): string
    {
        return Reports::RANGES[$this->range()] ?? 'Last 30 days';
    }
}
