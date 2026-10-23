<?php

/*
| Front door of one OpenRevise site. All four sites share the Laravel code in
| openrevise-core; Laravel picks the right routes from the hostname.
*/

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// openrevise-core sits next to this folder on the server (or is this
// repository during development).
$core = null;
foreach ([__DIR__.'/openrevise-core', __DIR__.'/../openrevise-core', __DIR__.'/../..'] as $candidate) {
    if (is_file($candidate.'/bootstrap/app.php')) {
        $core = realpath($candidate);
        break;
    }
}

if ($core === null) {
    http_response_code(500);
    exit('OpenRevise: the openrevise-core folder was not found.');
}

if (file_exists($maintenance = $core.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $core.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $core.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
