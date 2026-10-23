<?php

namespace App\Http\Controllers;

use App\Analytics\Visitor;
use App\Models\Analytics\PageView;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Throwable;

/** Receives page views from the tracker on openrevise.com and the app. */
class CollectController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $nothing = response('', 204);

        if (! config('openrevise.analytics.enabled') || strlen($request->getContent()) > 4096 || Visitor::isBot($request->userAgent())) {
            return $nothing;
        }

        $data = json_decode($request->getContent(), true);
        if (! is_array($data) || ! preg_match('/^[a-z0-9]{8,24}$/', (string) ($data['k'] ?? ''))) {
            return $nothing;
        }

        $limiter = 'collect:'.$request->ip();
        if (RateLimiter::tooManyAttempts($limiter, 120)) {
            return $nothing;
        }
        RateLimiter::hit($limiter, 60);

        $key = substr($data['k'], 0, 16);

        try {
            // The second ping: how long the page was open.
            if (isset($data['d'])) {
                PageView::query()
                    ->where('view_key', $key)
                    ->whereNull('duration_ms')
                    ->where('created_at', '>', now()->subHours(6))
                    ->update(['duration_ms' => max(0, min((int) $data['d'], 3_600_000))]);

                return $nothing;
            }

            $path = (string) ($data['p'] ?? '/');
            if (! str_starts_with($path, '/')) {
                return $nothing;
            }

            [$source, $referrerHost] = Visitor::source($data['r'] ?? null, array_values(config('openrevise.domains')));
            $device = Visitor::device($request->userAgent(), (int) ($data['w'] ?? 0));

            PageView::create([
                'created_at' => now(),
                'view_key' => $key,
                'visitor' => Visitor::hash($request),
                // The presentation pages and the app share openrevise.com
                // now; page_type tells them apart.
                'site' => 'main',
                'path' => Str::limit($path, 490, ''),
                'page_type' => Str::limit((string) ($data['t'] ?? ''), 28, '') ?: null,
                'subject' => Str::limit((string) ($data['s'] ?? ''), 110, '') ?: null,
                'board' => Str::limit((string) ($data['o'] ?? ''), 50, '') ?: null,
                'lesson_id' => ctype_digit((string) ($data['l'] ?? '')) ? (int) $data['l'] : null,
                'source' => $source,
                'referrer_host' => $referrerHost ? Str::limit($referrerHost, 180, '') : null,
                'device' => $device['device'],
                'browser' => $device['browser'],
                'os' => $device['os'],
                'country' => Visitor::country($request, is_string($data['z'] ?? null) ? $data['z'] : null),
            ]);
        } catch (Throwable $e) {
            report($e);
        }

        return $nothing;
    }
}
