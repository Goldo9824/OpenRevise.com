<?php

namespace App\Filament\Widgets;

use App\Analytics\Reports;
use App\Filament\Widgets\Concerns\UsesRange;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TrafficStats extends StatsOverviewWidget
{
    use UsesRange;

    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $reports = $this->reports();
        $t = $reports->totals();
        $daily = $reports->daily();

        $avg = $t['duration_samples'] ? (int) round($t['duration_ms'] / $t['duration_samples'] / 1000) : null;
        $bounce = $t['sessions'] ? round($t['bounces'] / $t['sessions'] * 100) : null;

        return [
            Stat::make('Live now', Reports::live())
                ->description('Visitors in the last '.config('openrevise.analytics.live_minutes').' minutes')
                ->color('success'),
            Stat::make('Visitors', number_format($t['visitors']))
                ->description($this->rangeLabel())
                ->chart($daily['visitors'])
                ->color('primary'),
            Stat::make('Page views', number_format($t['views']))
                ->description(($t['sessions'] ? round($t['views'] / $t['sessions'], 1) : 0).' pages per visit')
                ->chart($daily['views']),
            Stat::make('Time on page', $avg === null ? '-' : gmdate($avg >= 3600 ? 'G:i:s' : 'i:s', $avg))
                ->description('Average, minutes:seconds'),
            Stat::make('Bounce rate', $bounce === null ? '-' : $bounce.'%')
                ->description('Visits that saw one page'),
        ];
    }

    protected function getColumns(): int
    {
        return 5;
    }
}
