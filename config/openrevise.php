<?php

/*
|--------------------------------------------------------------------------
| OpenRevise
|--------------------------------------------------------------------------
|
| Everything specific to OpenRevise lives here: the three hostnames the one
| codebase answers to, where lesson files and diagrams are kept on disk,
| and the owner details the legal pages need. The installer writes the
| matching .env values, so nothing here needs editing on the server.
|
*/

$main = env('OPENREVISE_DOMAIN', 'openrevise.com');

// Diagrams go in the media folder of openrevise.com. On LWS that is the
// hosting root, which holds openrevise-core; in the repository it is
// sites/main. (Older installs wrote OPENREVISE_MEDIA_PATH into .env, pointing
// at the app subdomain's folder; it is no longer read.)
$mediaPath = basename(base_path()) === 'openrevise-core'
    ? dirname(base_path()).DIRECTORY_SEPARATOR.'media'
    : base_path('sites/main/media');

return [

    // The revision app is served by openrevise.com itself; the old
    // app.openrevise.com only redirects there.
    'domains' => [
        'main' => $main,
        'api' => env('OPENREVISE_API_DOMAIN', 'api.'.$main),
        'admin' => env('OPENREVISE_ADMIN_DOMAIN', 'admin.'.$main),
    ],

    // Used to build absolute links between the three sites.
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
