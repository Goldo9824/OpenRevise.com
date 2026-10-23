<?php

namespace App\Analytics;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The analytics database is capped at 1 GB, so raw page views are kept for
 * 90 days. Each finished day is rolled up into daily totals first, then the
 * raw rows older than 90 days are deleted.
 *
 * Runs from cron (php artisan openrevise:analytics-rollup), and also catches
 * up by itself when the admin opens the dashboard, in case cron is missing.
 */
class Rollup
{
    public function catchUp(int $maxDays = 60): int
    {
        $db = DB::connection('analytics');
        $today = CarbonImmutable::today();

        $first = $db->table('page_views')->where('created_at', '<', $today)->min('created_at');
        if (! $first) {
            $this->prune();

            return 0;
        }

        $done = $db->table('daily_totals')->pluck('date')->map(fn ($d) => substr((string) $d, 0, 10))->flip();
        $day = CarbonImmutable::parse($first)->startOfDay();
        $rolled = 0;

        while ($day->lt($today) && $rolled < $maxDays) {
            if (! isset($done[$day->toDateString()])) {
                $this->rollDay($day);
                $rolled++;
            }
            $day = $day->addDay();
        }

        $this->prune();

        return $rolled;
    }

    /** At most once every ten minutes, for the dashboard. */
    public function catchUpQuietly(): void
    {
        Cache::remember('analytics.rollup', 600, function () {
            $this->catchUp();

            return true;
        });
    }

    public function rollDay(CarbonImmutable $day): void
    {
        $db = DB::connection('analytics');
        $aggregator = new Aggregator;

        $db->table('page_views')
            ->where('created_at', '>=', $day)
            ->where('created_at', '<', $day->addDay())
            ->orderBy('id')
            ->lazyById(2000)
            ->each(fn ($row) => $aggregator->add($row));

        $date = $day->toDateString();

        $db->transaction(function () use ($db, $date, $aggregator) {
            $db->table('daily_pages')->where('date', $date)->delete();
            $db->table('daily_breakdowns')->where('date', $date)->delete();
            $db->table('daily_totals')->where('date', $date)->delete();

            $db->table('daily_totals')->insert(['date' => $date] + $aggregator->totals());

            foreach (array_chunk($aggregator->pages(), 500) as $chunk) {
                $db->table('daily_pages')->insert(array_map(fn ($p) => [
                    'date' => $date,
                    'site' => $p['site'],
                    'path' => $p['path'],
                    'page_type' => $p['page_type'],
                    'subject' => $p['subject'],
                    'lesson_id' => $p['lesson_id'],
                    'views' => $p['views'],
                    'visitors' => $p['visitors'],
                    'duration_ms' => $p['duration_ms'],
                    'duration_samples' => $p['duration_samples'],
                ], $chunk));
            }

            foreach (array_chunk($aggregator->breakdowns(), 500) as $chunk) {
                $db->table('daily_breakdowns')->insert(array_map(fn ($b) => ['date' => $date] + $b, $chunk));
            }
        });
    }

    public function prune(): int
    {
        $cutoff = CarbonImmutable::today()->subDays(config('openrevise.analytics.raw_days'));
        $db = DB::connection('analytics');

        // Only delete days that are safely rolled up.
        $rolled = $db->table('daily_totals')->where('date', '<', $cutoff->toDateString())->exists()
            || ! $db->table('page_views')->where('created_at', '<', $cutoff)->exists();

        return $rolled ? $db->table('page_views')->where('created_at', '<', $cutoff)->delete() : 0;
    }
}
