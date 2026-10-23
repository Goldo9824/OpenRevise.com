@php
    $onApp = request()->getHost() === config('openrevise.domains.app');
    $layout = $onApp ? 'layouts.app' : 'layouts.public';
@endphp
<x-dynamic-component :component="$layout" page="404" title="Page not found | OpenRevise" robots="noindex">
    <section @class(['py-16 md:py-24', 'max-w-6xl mx-auto px-5' => ! $onApp, 'max-w-2xl' => $onApp])>
        <span class="chip chip-brand">404</span>
        <h1 class="page-title mt-3">We can&rsquo;t find that page</h1>
        <p class="lead mt-3">The link may be old, or the page may have moved. Lessons that change address are redirected, so this one may never have existed.</p>
        <div class="mt-6 flex flex-wrap gap-3">
            <a href="{{ app_url('/gcse/') }}" class="btn">Browse subjects</a>
            <a href="{{ app_url('/search/') }}" class="btn btn-outline"><x-glyph name="search" /> Search</a>
        </div>
    </section>
</x-dynamic-component>
