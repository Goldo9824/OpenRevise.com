@props(['seo', 'eyebrow', 'heading', 'pageName' => 'page'])
{{-- Shared frame for the text pages: About, Privacy, Terms, Contact. --}}
<x-layouts.public site="main" :page="$pageName" :title="$seo['title']" :description="$seo['description']" :canonical="$seo['canonical']">
    <div class="wrap pt-12 pb-20 md:pt-16 grid gap-10 lg:grid-cols-[14rem_1fr]">
        <aside class="lg:pt-3">
            <nav aria-label="OpenRevise pages">
                <ul class="flex flex-wrap gap-x-5 gap-y-1 lg:block lg:space-y-1.5 text-[.95rem]">
                    @foreach (['/about/' => 'About', '/contact/' => 'Contact', '/privacy/' => 'Privacy', '/terms/' => 'Terms of use'] as $path => $label)
                        <li><a href="{{ main_url($path) }}" @class(['no-underline', 'font-bold text-ink' => $seo['canonical'] === main_url($path), 'text-ink-2' => $seo['canonical'] !== main_url($path)])>{{ $label }}</a></li>
                    @endforeach
                </ul>
            </nav>
        </aside>
        <article class="page-prose">
            <p class="eyebrow">{{ $eyebrow }}</p>
            <h1 class="!mt-3 text-[2.4rem] md:text-[3rem] leading-none">{{ $heading }}</h1>
            {{ $slot }}
        </article>
    </div>
</x-layouts.public>
