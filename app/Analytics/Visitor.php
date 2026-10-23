<?php

namespace App\Analytics;

use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Works out what can be known about a page view without cookies: a visitor
 * hash that changes every day (its salt is deleted after two days, so it
 * cannot be traced back to an IP address), the kind of device, the browser,
 * the country and where the visitor came from.
 */
class Visitor
{
    public const BOTS = '/bot|crawl|spider|slurp|facebookexternalhit|preview|headless|lighthouse|pingdom|monitor|curl|wget|python|axios|go-http|java\/|httpclient|scrapy|phantom|selenium|puppeteer|playwright/i';

    public static function isBot(?string $userAgent): bool
    {
        return ! $userAgent || preg_match(self::BOTS, $userAgent) === 1;
    }

    public static function hash(Request $request): string
    {
        return substr(hash('sha256', static::salt().'|'.$request->ip().'|'.$request->userAgent().'|'.$request->getHost()), 0, 16);
    }

    protected static function salt(): string
    {
        $today = now()->toDateString();
        $db = DB::connection('analytics');

        $salt = $db->table('salts')->where('date', $today)->value('salt');

        if (! $salt) {
            $salt = bin2hex(random_bytes(32));
            try {
                $db->table('salts')->insert(['date' => $today, 'salt' => $salt]);
            } catch (Throwable) {
                // Another request made today's salt first.
                $salt = $db->table('salts')->where('date', $today)->value('salt') ?? $salt;
            }
            $db->table('salts')->where('date', '<', now()->subDay()->toDateString())->delete();
        }

        return $salt;
    }

    /** @return array{0: string, 1: ?string} [source, referrer host] */
    public static function source(?string $referrer, array $ownHosts): array
    {
        $host = $referrer ? strtolower((string) parse_url($referrer, PHP_URL_HOST)) : '';
        $host = preg_replace('/^www\./', '', $host);

        if ($host === '') {
            return ['Direct', null];
        }

        if (in_array($host, $ownHosts, true)) {
            return ['Internal', $host];
        }

        $known = [
            '/(^|\.)google\./' => 'Google',
            '/(^|\.)bing\.com$/' => 'Bing',
            '/duckduckgo\.com$/' => 'DuckDuckGo',
            '/(^|\.)yahoo\./' => 'Yahoo',
            '/ecosia\.org$/' => 'Ecosia',
            '/(^|\.)yandex\./' => 'Yandex',
            '/(chatgpt\.com|openai\.com)$/' => 'ChatGPT',
            '/claude\.ai$/' => 'Claude',
            '/perplexity\.ai$/' => 'Perplexity',
            '/gemini\.google\.com$/' => 'Gemini',
            '/(^|\.)(t\.co|twitter\.com|x\.com)$/' => 'X',
            '/facebook\.com$/' => 'Facebook',
            '/instagram\.com$/' => 'Instagram',
            '/tiktok\.com$/' => 'TikTok',
            '/reddit\.com$/' => 'Reddit',
            '/youtube\.com$/' => 'YouTube',
            '/snapchat\.com$/' => 'Snapchat',
            '/teams\.(microsoft|live)\.com$/' => 'Microsoft Teams',
        ];

        if (str_starts_with($host, 'classroom.google.')) {
            return ['Google Classroom', $host];
        }

        foreach ($known as $pattern => $name) {
            if (preg_match($pattern, $host)) {
                return [$name, $host];
            }
        }

        return [Str::limit($host, 75, ''), $host];
    }

    /** @return array{device: string, browser: string, os: string} */
    public static function device(?string $ua, int $screenWidth): array
    {
        $ua = (string) $ua;

        $os = match (true) {
            (bool) preg_match('/iPhone|iPod/', $ua) => 'iOS',
            (bool) preg_match('/iPad/', $ua) => 'iPadOS',
            (bool) preg_match('/Android/', $ua) => 'Android',
            (bool) preg_match('/CrOS/', $ua) => 'ChromeOS',
            (bool) preg_match('/Windows/', $ua) => 'Windows',
            (bool) preg_match('/Mac OS X|Macintosh/', $ua) => 'macOS',
            (bool) preg_match('/Linux/', $ua) => 'Linux',
            default => 'Other',
        };

        $browser = match (true) {
            (bool) preg_match('/Edg\//', $ua) => 'Edge',
            (bool) preg_match('/OPR\/|Opera/', $ua) => 'Opera',
            (bool) preg_match('/SamsungBrowser/', $ua) => 'Samsung Internet',
            (bool) preg_match('/Firefox|FxiOS/', $ua) => 'Firefox',
            (bool) preg_match('/Chrome|CriOS/', $ua) => 'Chrome',
            (bool) preg_match('/Safari/', $ua) => 'Safari',
            default => 'Other',
        };

        $tablet = preg_match('/iPad|Tablet|Android(?!.*Mobile)/', $ua) === 1;
        $mobile = preg_match('/Mobi|iPhone|Android.*Mobile/', $ua) === 1;

        $device = match (true) {
            $tablet => 'Tablet',
            $mobile || ($screenWidth > 0 && $screenWidth < 600) => 'Mobile',
            default => 'Desktop',
        };

        return ['device' => $device, 'browser' => $browser, 'os' => $os];
    }

    /** From the host's geo header when there is one, otherwise the time zone. */
    public static function country(Request $request, ?string $timezone): ?string
    {
        foreach (['CF-IPCountry', 'X-Country-Code'] as $header) {
            $code = strtoupper((string) $request->header($header));
            if (preg_match('/^[A-Z]{2}$/', $code) && $code !== 'XX') {
                return $code;
            }
        }

        $server = strtoupper((string) $request->server('GEOIP_COUNTRY_CODE'));
        if (preg_match('/^[A-Z]{2}$/', $server)) {
            return $server;
        }

        if ($timezone && in_array($timezone, DateTimeZone::listIdentifiers(), true)) {
            $code = (new DateTimeZone($timezone))->getLocation()['country_code'] ?? null;

            return $code && $code !== '??' ? $code : null;
        }

        return null;
    }
}
