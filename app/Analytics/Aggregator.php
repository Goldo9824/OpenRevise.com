<?php

namespace App\Analytics;

/**
 * Turns raw page views into the totals the reports use. The same code rolls
 * up finished days and summarises today on the fly.
 */
class Aggregator
{
    public const SESSION_GAP = 1800; // 30 minutes without a view starts a new visit.

    public const DIMENSIONS = ['source', 'device', 'browser', 'os', 'country', 'subject', 'board', 'page_type'];

    /** @var array<string, array<string, mixed>> */
    protected array $pages = [];

    /** @var array<string, array<string, array{views: int, visitors: array<string, true>}>> */
    protected array $breakdowns = [];

    /** @var array<string, list<int>> visitor => view timestamps */
    protected array $visits = [];

    /** @var array<string, array<string, true>> step => visitors */
    protected array $funnel = ['home' => [], 'lesson' => [], 'questions' => []];

    protected int $views = 0;

    protected int $duration = 0;

    protected int $durationSamples = 0;

    /** @param object|array<string, mixed> $row A page_views row. */
    public function add(object|array $row): void
    {
        $row = (object) $row;
        $this->views++;
        $visitor = $row->visitor;
        $this->visits[$visitor][] = strtotime((string) $row->created_at);

        $key = $row->site.'|'.$row->path;
        $page = $this->pages[$key] ??= [
            'site' => $row->site, 'path' => $row->path, 'page_type' => $row->page_type,
            'subject' => $row->subject, 'lesson_id' => $row->lesson_id,
            'views' => 0, 'visitors' => [], 'duration_ms' => 0, 'duration_samples' => 0,
        ];
        $page['views']++;
        $page['visitors'][$visitor] = true;

        if ($row->duration_ms !== null) {
            $page['duration_ms'] += (int) $row->duration_ms;
            $page['duration_samples']++;
            $this->duration += (int) $row->duration_ms;
            $this->durationSamples++;
        }
        $this->pages[$key] = $page;

        foreach (self::DIMENSIONS as $dimension) {
            $value = $row->{$dimension} ?? null;
            if ($value === null || $value === '' || ($dimension === 'source' && $value === 'Internal')) {
                continue;
            }
            $this->breakdowns[$dimension][$value]['views'] = ($this->breakdowns[$dimension][$value]['views'] ?? 0) + 1;
            $this->breakdowns[$dimension][$value]['visitors'][$visitor] = true;
        }

        $step = match ($row->page_type) {
            'home', 'landing' => 'home',
            'lesson' => 'lesson',
            'questions' => 'questions',
            default => null,
        };
        if ($step) {
            $this->funnel[$step][$visitor] = true;
        }
    }

    /** @return array<string, int> */
    public function totals(): array
    {
        $sessions = 0;
        $bounces = 0;

        foreach ($this->visits as $times) {
            sort($times);
            $length = 0;
            $previous = null;
            foreach ($times as $t) {
                if ($previous !== null && $t - $previous > self::SESSION_GAP) {
                    $sessions++;
                    $bounces += $length === 1 ? 1 : 0;
                    $length = 0;
                }
                $length++;
                $previous = $t;
            }
            $sessions++;
            $bounces += $length === 1 ? 1 : 0;
        }

        // The funnel follows the same visitors from step to step.
        $home = $this->funnel['home'];
        $lesson = array_intersect_key($this->funnel['lesson'], $home);
        $questions = array_intersect_key($this->funnel['questions'], $lesson);

        return [
            'views' => $this->views,
            'visitors' => count($this->visits),
            'sessions' => $sessions,
            'bounces' => $bounces,
            'duration_ms' => $this->duration,
            'duration_samples' => $this->durationSamples,
            'funnel_home' => count($home),
            'funnel_lesson' => count($lesson),
            'funnel_questions' => count($questions),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function pages(): array
    {
        return array_values(array_map(fn ($p) => ['visitors' => count($p['visitors'])] + $p, $this->pages));
    }

    /** @return list<array{dimension: string, value: string, views: int, visitors: int}> */
    public function breakdowns(): array
    {
        $rows = [];
        foreach ($this->breakdowns as $dimension => $values) {
            foreach ($values as $value => $data) {
                $rows[] = ['dimension' => $dimension, 'value' => (string) $value, 'views' => $data['views'], 'visitors' => count($data['visitors'])];
            }
        }

        return $rows;
    }
}
