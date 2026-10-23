<?php

namespace App\Analytics;

use App\Models\Content\Lesson;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * What the admin dashboard shows. Finished days come from the daily totals;
 * today is worked out from the raw page views as it happens.
 */
class Reports
{
    public const RANGES = [
        'today' => 'Today',
        '7d' => 'Last 7 days',
        '30d' => 'Last 30 days',
        '90d' => 'Last 90 days',
        '12m' => 'Last 12 months',
    ];

    protected CarbonImmutable $from;

    protected CarbonImmutable $today;

    protected ?Aggregator $todayAggregate = null;

    public function __construct(string $range = '30d')
    {
        $this->today = CarbonImmutable::today();
        $this->from = match ($range) {
            'today' => $this->today,
            '7d' => $this->today->subDays(6),
            '90d' => $this->today->subDays(89),
            '12m' => $this->today->subYear()->addDay(),
            default => $this->today->subDays(29),
        };

        app(Rollup::class)->catchUpQuietly();
    }

    protected function db(): \Illuminate\Database\Connection
    {
        return DB::connection('analytics');
    }

    protected function todaySoFar(): Aggregator
    {
        if ($this->todayAggregate) {
            return $this->todayAggregate;
        }

        $aggregator = new Aggregator;
        $this->db()->table('page_views')->where('created_at', '>=', $this->today)->orderBy('id')
            ->lazyById(2000)->each(fn ($row) => $aggregator->add($row));

        return $this->todayAggregate = $aggregator;
    }

    /** @return array<string, int> */
    public function totals(): array
    {
        $sum = $this->db()->table('daily_totals')
            ->where('date', '>=', $this->from->toDateString())
            ->where('date', '<', $this->today->toDateString())
            ->selectRaw('sum(views) views, sum(visitors) visitors, sum(sessions) sessions, sum(bounces) bounces, sum(duration_ms) duration_ms, sum(duration_samples) duration_samples, sum(funnel_home) funnel_home, sum(funnel_lesson) funnel_lesson, sum(funnel_questions) funnel_questions')
            ->first();

        $totals = array_map('intval', (array) $sum);
        foreach ($this->todaySoFar()->totals() as $key => $value) {
            $totals[$key] = ($totals[$key] ?? 0) + $value;
        }

        return $totals;
    }

    /** @return array{labels: list<string>, views: list<int>, visitors: list<int>} */
    public function daily(): array
    {
        $rows = $this->db()->table('daily_totals')
            ->where('date', '>=', $this->from->toDateString())
            ->get()->keyBy(fn ($r) => substr((string) $r->date, 0, 10));

        $today = $this->todaySoFar()->totals();
        $byMonth = $this->from->diffInDays($this->today) > 120;
        $series = [];

        for ($day = $this->from; $day->lte($this->today); $day = $day->addDay()) {
            $key = $byMonth ? $day->format('M Y') : $day->format('j M');
            $row = $day->equalTo($this->today) ? (object) $today : ($rows[$day->toDateString()] ?? null);
            $series[$key]['views'] = ($series[$key]['views'] ?? 0) + (int) ($row->views ?? 0);
            $series[$key]['visitors'] = ($series[$key]['visitors'] ?? 0) + (int) ($row->visitors ?? 0);
        }

        return [
            'labels' => array_keys($series),
            'views' => array_column($series, 'views'),
            'visitors' => array_column($series, 'visitors'),
        ];
    }

    /** @return Collection<int, array{label: string, views: int, visitors: int}> */
    public function breakdown(string $dimension, int $limit = 8): Collection
    {
        $rows = $this->db()->table('daily_breakdowns')
            ->where('dimension', $dimension)
            ->where('date', '>=', $this->from->toDateString())
            ->where('date', '<', $this->today->toDateString())
            ->groupBy('value')
            ->selectRaw('value, sum(views) views, sum(visitors) visitors')
            ->get()
            ->mapWithKeys(fn ($r) => [$r->value => ['views' => (int) $r->views, 'visitors' => (int) $r->visitors]])
            ->all();

        foreach ($this->todaySoFar()->breakdowns() as $b) {
            if ($b['dimension'] === $dimension) {
                $rows[$b['value']]['views'] = ($rows[$b['value']]['views'] ?? 0) + $b['views'];
                $rows[$b['value']]['visitors'] = ($rows[$b['value']]['visitors'] ?? 0) + $b['visitors'];
            }
        }

        return collect($rows)
            ->map(fn ($v, $label) => ['label' => (string) $label] + $v)
            ->sortByDesc('visitors')
            ->take($limit)
            ->values();
    }

    /** @return Collection<int, array{label: string, url: ?string, views: int, visitors: int, avg_seconds: ?int}> */
    public function topLessons(int $limit = 10): Collection
    {
        $rows = $this->db()->table('daily_pages')
            ->whereNotNull('lesson_id')
            ->where('page_type', 'lesson')
            ->where('date', '>=', $this->from->toDateString())
            ->where('date', '<', $this->today->toDateString())
            ->groupBy('lesson_id')
            ->selectRaw('lesson_id, sum(views) views, sum(visitors) visitors, sum(duration_ms) duration_ms, sum(duration_samples) duration_samples')
            ->get()
            ->keyBy('lesson_id')
            ->map(fn ($r) => (array) $r)
            ->all();

        foreach ($this->todaySoFar()->pages() as $p) {
            if ($p['page_type'] !== 'lesson' || ! $p['lesson_id']) {
                continue;
            }
            $id = $p['lesson_id'];
            $rows[$id] ??= ['lesson_id' => $id, 'views' => 0, 'visitors' => 0, 'duration_ms' => 0, 'duration_samples' => 0];
            foreach (['views', 'visitors', 'duration_ms', 'duration_samples'] as $k) {
                $rows[$id][$k] += $p[$k];
            }
        }

        $top = collect($rows)->sortByDesc('visitors')->take($limit);

        // The lessons live in another database: look their titles up by ID.
        $lessons = Lesson::with('chapter.topic.subject')->whereIn('id', $top->keys())->get()->keyBy('id');

        return $top->map(fn ($r) => [
            'label' => $lessons[$r['lesson_id']]->title ?? 'Deleted lesson #'.$r['lesson_id'],
            'url' => isset($lessons[$r['lesson_id']]) ? app_url($lessons[$r['lesson_id']]->path()) : null,
            'views' => (int) $r['views'],
            'visitors' => (int) $r['visitors'],
            'avg_seconds' => $r['duration_samples'] ? (int) round($r['duration_ms'] / $r['duration_samples'] / 1000) : null,
        ])->values();
    }

    /** Visitors seen in the last few minutes. */
    public static function live(): int
    {
        return (int) DB::connection('analytics')->table('page_views')
            ->where('created_at', '>=', now()->subMinutes(config('openrevise.analytics.live_minutes')))
            ->distinct()->count('visitor');
    }

    /** @return Collection<int, array{label: string, views: int}> */
    public static function livePages(int $limit = 5): Collection
    {
        return DB::connection('analytics')->table('page_views')
            ->where('created_at', '>=', now()->subMinutes(config('openrevise.analytics.live_minutes')))
            ->groupBy('path')->selectRaw('path, count(*) views')->orderByDesc('views')->limit($limit)->get()
            ->map(fn ($r) => ['label' => $r->path, 'views' => (int) $r->views]);
    }
}
