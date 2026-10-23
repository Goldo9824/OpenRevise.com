<?php

/*
| Local development only: serves the three sites from one PHP built-in
| server, choosing the folder from the hostname the way LWS does.
|
|   php -S 127.0.0.1:8000 dev-server.php
|
| then open http://openrevise.localhost:8000 (the presentation pages and the
| app), http://api.openrevise.localhost:8000 and
| http://admin.openrevise.localhost:8000 (*.localhost always points at your
| own machine). The old app.openrevise.localhost redirects like on LWS.
*/

$host = strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? '')[0]);
$site = match (true) {
    str_starts_with($host, 'api.') => 'api',
    str_starts_with($host, 'admin.') => 'admin',
    default => 'main',
};

$root = __DIR__.'/sites/'.$site;
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');

// Static files: the site's own folder first, then the shared build output
// in public/ (Vite assets, Filament assets) that deploys copy into each site.
foreach ([$root, __DIR__.'/public'] as $dir) {
    $file = realpath($dir.$uri);
    if ($uri !== '/' && $file && is_file($file) && str_starts_with($file, realpath($dir)) && ! str_ends_with($file, '.php')) {
        $types = [
            'css' => 'text/css', 'js' => 'text/javascript', 'svg' => 'image/svg+xml', 'png' => 'image/png',
            'webp' => 'image/webp', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'woff2' => 'font/woff2',
            'woff' => 'font/woff', 'ico' => 'image/x-icon', 'json' => 'application/json', 'txt' => 'text/plain',
            'map' => 'application/json', 'ttf' => 'font/ttf',
        ];
        header('Content-Type: '.($types[strtolower(pathinfo($file, PATHINFO_EXTENSION))] ?? 'application/octet-stream'));
        readfile($file);

        return true;
    }
}

if ($site === 'main' && $uri === '/install.php') {
    require $root.'/install.php';

    return true;
}

$_SERVER['SCRIPT_FILENAME'] = $root.'/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

require $root.'/index.php';
