@php
    $onApp = request()->getHost() === config('openrevise.domains.app');
@endphp
<x-layouts.public :site="$onApp ? 'app' : 'main'" page="404" title="Page not found | OpenRevise" robots="noindex">
    <section class="wrap py-20 md:py-28 max-w-3xl">
        <p class="font-mono font-bold text-red text-lg">[0 marks]</p>
        <h1 class="mt-3 text-[2.4rem] md:text-[3.4rem] leading-none">This page isn&rsquo;t on the paper.</h1>
        <p class="mt-5 text-lg text-ink-2 max-w-xl">The link may be old, or the page may have moved. Lessons that change address are redirected, so this one may never have existed.</p>
        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ app_url('/') }}" class="btn">Find a subject</a>
            <a href="{{ main_url('/') }}" class="btn btn-ghost">OpenRevise home</a>
        </div>
    </section>
</x-layouts.public>
