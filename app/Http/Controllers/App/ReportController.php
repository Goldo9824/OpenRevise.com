<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Admin\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/** "Report a mistake" on lesson pages. Saved in the Admin DB for the owner. */
class ReportController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'min:5', 'max:2000'],
            'lesson_id' => ['nullable', 'integer'],
            'page' => ['required', 'string', 'max:500'],
            'website' => ['nullable', 'max:0'], // honeypot: people never fill it
        ]);

        $origin = parse_url((string) ($request->headers->get('Origin') ?? $request->headers->get('Referer')), PHP_URL_HOST);
        abort_unless($origin === $request->getHost(), 403);

        $key = 'report:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['error' => 'Thanks - we have your reports. Try again later.'], 429);
        }
        RateLimiter::hit($key, 3600);

        Report::create([
            'lesson_id' => $data['lesson_id'] ?? null,
            'page_url' => $data['page'],
            'message' => $data['message'],
            'ip_hash' => substr(hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')), 0, 16),
        ]);

        return response()->json(['ok' => true], 201);
    }
}
