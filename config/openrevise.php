<?php

/*
|--------------------------------------------------------------------------
| OpenRevise
|--------------------------------------------------------------------------
|
| Everything specific to OpenRevise lives here: the four hostnames the one
| codebase answers to, where lesson files and diagrams are kept on disk,
| and the owner details the legal pages need. The installer writes the
| matching .env values, so nothing here needs editing on the server.
|
*/

$main = env('OPENREVISE_DOMAIN', 'openrevise.com');

$appDomain = env('OPENREVISE_APP_DOMAIN', 'app.'.$main);

// On LWS each subdomain is served from a folder named after it, next to
// openrevise-core. In the repository the app's public files live in sites/app.
$mediaPath = env('OPENREVISE_MEDIA_PATH');

if (! $mediaPath) {
    $deployed = dirname(base_path()).DIRECTORY_SEPARATOR.$appDomain;
    $mediaPath = is_dir($deployed)
        ? $deployed.DIRECTORY_SEPARATOR.'media'
        : base_path('sites/app/media');
}

return [

    'domains' => [
        'main' => $main,
        'app' => $appDomain,
        'api' => env('OPENREVISE_API_DOMAIN', 'api.'.$main),
        'admin' => env('OPENREVISE_ADMIN_DOMAIN', 'admin.'.$main),
    ],

    // Used to build absolute links between the four sites.
    'scheme' => env('OPENREVISE_SCHEME', 'https'),

    // Only set this for local development, e.g. ":8000".
    'port' => env('OPENREVISE_PORT', ''),

    // The level segment at the start of every app URL. GCSE only for now.
    'level' => 'gcse',

    'paths' => [
        'lessons' => env('OPENREVISE_LESSONS_PATH', storage_path('app/lessons')),
        'media' => $mediaPath,
    ],

    'owner' => [
        'name' => env('OPENREVISE_OWNER_NAME', ''),
        'email' => env('OPENREVISE_CONTACT_EMAIL', ''),
    ],

    'api' => [
        // Requests per minute, per key.
        'rate_limit' => (int) env('OPENREVISE_API_RATE_LIMIT', 120),
        'max_image_kb' => (int) env('OPENREVISE_API_MAX_IMAGE_KB', 4096),
    ],

    'analytics' => [
        'enabled' => (bool) env('OPENREVISE_ANALYTICS', true),
        // Raw page views are rolled up into daily totals, then deleted.
        'raw_days' => (int) env('OPENREVISE_ANALYTICS_RAW_DAYS', 90),
        'live_minutes' => 5,
    ],

];
