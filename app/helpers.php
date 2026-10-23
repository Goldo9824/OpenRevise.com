<?php

/*
| Links between the four sites. Each builds an absolute URL from the
| configured hostname, so the same code works on openrevise.com and on
| *.localhost during development.
*/

if (! function_exists('site_url')) {
    function site_url(string $site, string $path = '/'): string
    {
        $host = config("openrevise.domains.$site");

        return config('openrevise.scheme').'://'.$host.config('openrevise.port').'/'.ltrim($path, '/');
    }
}

if (! function_exists('main_url')) {
    function main_url(string $path = '/'): string
    {
        return site_url('main', $path);
    }
}

if (! function_exists('app_url')) {
    function app_url(string $path = '/'): string
    {
        return site_url('app', $path);
    }
}

if (! function_exists('api_url')) {
    function api_url(string $path = '/'): string
    {
        return site_url('api', $path);
    }
}

if (! function_exists('admin_url')) {
    function admin_url(string $path = '/'): string
    {
        return site_url('admin', $path);
    }
}
