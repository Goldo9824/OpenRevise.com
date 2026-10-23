{{-- The <head> shared by every public page. Expects $seo-like props from the layout. --}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $title }}</title>
@if ($description)
    <meta name="description" content="{{ $description }}">
@endif
<meta name="robots" content="{{ $robots }}">
@if ($canonical)
    <link rel="canonical" href="{{ $canonical }}">
    <meta property="og:url" content="{{ $canonical }}">
@endif
<meta property="og:site_name" content="OpenRevise">
<meta property="og:type" content="{{ $ogType }}">
<meta property="og:title" content="{{ $title }}">
@if ($description)
    <meta property="og:description" content="{{ $description }}">
@endif
<meta name="theme-color" content="#ffffff">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
@vite(['resources/css/site.css', 'resources/js/site.js'])
@if (config('openrevise.analytics.enabled'))
    @vite('resources/js/tracker.js')
@endif
@foreach (array_filter($jsonLd) as $schema)
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endforeach
