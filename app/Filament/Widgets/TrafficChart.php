<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\UsesRange;
use Filament\Widgets\ChartWidget;

class TrafficChart extends ChartWidget
{
    use UsesRange;

    protected static ?int $sort = 2;

    protected ?string $heading = 'Visitors and page views';

    protected ?string $maxHeight = '280px';

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $daily = $this->reports()->daily();

        return [
            'datasets' => [
                [
                    'label' => 'Page views',
                    'data' => $daily['views'],
                    'borderColor' => '#9cc7b8',
                    'backgroundColor' => 'rgba(156, 199, 184, .15)',
                    'fill' => true,
                    'tension' => .3,
                    'pointRadius' => 0,
                ],
                [
                    'label' => 'Visitors',
                    'data' => $daily['visitors'],
                    'borderColor' => '#0f6b56',
                    'backgroundColor' => 'rgba(15, 107, 86, .12)',
                    'fill' => true,
                    'tension' => .3,
                    'pointRadius' => 0,
                ],
            ],
            'labels' => $daily['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
