<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>API reference | OpenRevise</title>
    <link rel="icon" href="{{ main_url('/favicon.svg') }}" type="image/svg+xml">
    @vite('resources/css/site.css')
</head>
<body>
    <header class="site-header">
        <div class="wrap flex items-center justify-between gap-4 h-16">
            <div class="flex items-baseline gap-3">
                <x-wordmark :href="main_url('/')" />
                <span class="eyebrow">API</span>
            </div>
            <a href="{{ api_url('/v1/docs') }}" class="nav-link">As Markdown</a>
        </div>
    </header>
    <main class="wrap py-12">
        <article class="api-docs page-prose !max-w-3xl">
            {!! $content !!}
        </article>
    </main>
</body>
</html>
