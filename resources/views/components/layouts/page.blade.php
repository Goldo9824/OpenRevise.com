@props(['seo', 'eyebrow', 'heading', 'pageName' => 'page'])
{{-- Shared frame for the text pages: About, Privacy, Terms, Contact. --}}
<x-layouts.public :page="$pageName" :title="$seo['title']" :description="$seo['description']" :canonical="$seo['canonical']">
    <div class="max-w-6xl mx-auto px-5 py-10 md:py-14 grid gap-10 lg:grid-cols-[13rem_1fr]">
        <aside>
            <nav aria-label="OpenRevise pages" class="lg:sticky lg:top-24">
                <ul class="flex flex-wrap gap-1 lg:block lg:space-y-0.5">
                    @foreach (['/about/' => ['About', 'help-circle'], '/contact/' => ['Contact', 'flag'], '/privacy/' => ['Privacy', 'eye'], '/terms/' => ['Terms of use', 'file-text']] as $path => [$label, $icon])
                        <li><a href="{{ main_url($path) }}" @class(['side-link', 'is-active' => $seo['canonical'] === main_url($path)])><x-glyph :name="$icon" /> {{ $label }}</a></li>
                    @endforeach
                </ul>
            </nav>
        </aside>
        <article class="prose-page">
            <p class="section-label">{{ $eyebrow }}</p>
            <h1 class="page-title !mt-2">{{ $heading }}</h1>
            {{ $slot }}
        </article>
    </div>
</x-layouts.public>
