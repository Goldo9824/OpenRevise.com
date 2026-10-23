<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\UsesRange;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

/** A ranked list with bars, like the "top pages" tables in Google Analytics. */
abstract class RankedList extends Widget
{
    use UsesRange;

    protected string $view = 'filament.widgets.ranked-list';

    protected int|string|array $columnSpan = ['md' => 1, 'xl' => 2];

    abstract protected function heading(): string;

    /** @return Collection<int, array{label: string, url?: ?string, views: int, visitors: int, avg_seconds?: ?int}> */
    abstract protected function rows(): Collection;

    protected function valueLabel(): string
    {
        return 'Visitors';
    }

    protected function value(array $row): int
    {
        return $row['visitors'];
    }

    protected function getViewData(): array
    {
        $rows = $this->rows();

        return [
            'heading' => $this->heading(),
            'range' => $this->rangeLabel(),
            'rows' => $rows,
            'max' => max(1, (int) $rows->map(fn ($r) => $this->value($r))->max()),
            'valueLabel' => $this->valueLabel(),
            'value' => fn ($r) => $this->value($r),
        ];
    }
}
