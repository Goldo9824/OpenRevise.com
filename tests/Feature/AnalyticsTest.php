<?php

namespace Tests\Feature;

use App\Analytics\Reports;
use App\Analytics\Rollup;
use App\Models\Analytics\PageView;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    protected function track(array $data, string $ua = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Safari/604.1')
    {
        return $this->call('POST', $this->mainUrl('/_t'), [], [], [], ['HTTP_USER_AGENT' => $ua, 'CONTENT_TYPE' => 'text/plain'], json_encode($data));
    }

    public function test_a_page_view_is_counted_without_cookies(): void
    {
        $response = $this->track(['k' => 'abcd1234abcd1234', 'p' => '/gcse/biology/', 'r' => 'https://www.google.co.uk/', 'w' => 390, 'z' => 'Europe/London', 't' => 'subject', 's' => 'biology']);

        $response->assertNoContent();
        $this->assertSame([], $response->headers->getCookies());

        $view = PageView::first();
        $this->assertSame('Google', $view->source);
        $this->assertSame('Mobile', $view->device);
        $this->assertSame('GB', $view->country);
        $this->assertSame(16, strlen($view->visitor));

        $this->track(['k' => 'abcd1234abcd1234', 'd' => 42000]);
        $this->assertSame(42000, (int) PageView::first()->duration_ms);
    }

    public function test_bots_are_ignored(): void
    {
        $this->track(['k' => 'abcd1234abcd1234', 'p' => '/'], 'Googlebot/2.1 (+http://www.google.com/bot.html)')->assertNoContent();

        $this->assertSame(0, PageView::count());
    }

    public function test_old_days_are_rolled_up_and_raw_views_pruned(): void
    {
        $db = DB::connection('analytics');
        $row = fn ($when, $visitor, $type = 'lesson') => [
            'created_at' => $when, 'view_key' => substr(md5(uniqid()), 0, 16), 'visitor' => $visitor, 'site' => 'app',
            'path' => '/gcse/x/', 'page_type' => $type, 'subject' => 'biology', 'lesson_id' => 7, 'source' => 'Google',
            'device' => 'Mobile', 'browser' => 'Safari', 'os' => 'iOS', 'country' => 'GB', 'duration_ms' => 60000,
        ];

        $db->table('page_views')->insert([
            $row(now()->subDays(100), 'aaaaaaaaaaaaaaaa'),
            $row(now()->subDays(2)->setTime(10, 0), 'bbbbbbbbbbbbbbbb', 'home'),
            $row(now()->subDays(2)->setTime(10, 5), 'bbbbbbbbbbbbbbbb'),
            $row(now()->subDays(2)->setTime(11, 0), 'cccccccccccccccc'),
        ]);

        app(Rollup::class)->catchUp();

        $day = $db->table('daily_totals')->where('date', now()->subDays(2)->toDateString())->first();
        $this->assertSame(3, (int) $day->views);
        $this->assertSame(2, (int) $day->visitors);
        $this->assertSame(1, (int) $day->funnel_lesson);
        $this->assertSame(0, $db->table('page_views')->where('created_at', '<', now()->subDays(90))->count());

        $totals = (new Reports('7d'))->totals();
        $this->assertSame(3, $totals['views']);
    }
}
