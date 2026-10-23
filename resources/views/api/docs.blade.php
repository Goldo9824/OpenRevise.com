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
    <header class="topbar">
        <div class="h-full max-w-4xl mx-auto px-5 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <x-logo :href="main_url('/')" />
                <span class="chip chip-brand">API</span>
            </div>
            <a href="{{ api_url('/v1/docs') }}" class="btn btn-quiet btn-sm">As Markdown</a>
        </div>
    </header>
    <main class="max-w-4xl mx-auto px-5 py-10">
        <article class="api-docs prose-page !max-w-none">
            {!! $content !!}
        </article>
    </main>
</body>
</html>
