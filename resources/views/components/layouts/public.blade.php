{{-- openrevise.com: the presentation pages. --}}
@props([
    'title' => 'OpenRevise',
    'description' => null,
    'canonical' => null,
    'robots' => 'index, follow',
    'jsonLd' => [],
    'page' => '',
    'ogType' => 'website',
])
@php
    $subjects = \App\Support\Navigation::footerSubjects();
    $menus = [
        'Subjects' => $subjects->map(fn ($s) => [$s->iconName(), 'GCSE '.$s->name, null, app_url($s->path())])->push(['book-open', 'All subjects', 'Every subject and topic', app_url('/gcse/')])->all(),
        'Study tools' => [
            ['file-text', 'Revision notes', 'Short lessons with key terms first', app_url('/gcse/')],
            ['help-circle', 'Exam questions', 'After every lesson, with mark schemes', app_url('/gcse/')],
            ['clipboard-list', 'Mock exams', 'Timed papers for your board', app_url('/gcse/')],
            ['bookmark', 'Saved lessons', 'Kept on your device', app_url('/saved/')],
        ],
        'Resources' => [
            ['help-circle', 'How lessons are written', 'Who is behind OpenRevise', main_url('/about/')],
            ['flag', 'Report a mistake', 'Corrections and contact', main_url('/contact/')],
            ['shield', 'Privacy', 'What is collected (very little)', main_url('/privacy/')],
            ['file-text', 'Terms of use', 'The rules for using the site', main_url('/terms/')],
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="en-GB">
<head>
    @include('partials.head')
</head>
<body x-data data-page="{{ $page }}" class="flex flex-col">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 btn">Skip to content</a>

    <header class="sticky top-0 z-30 bg-white/95 backdrop-blur border-b border-line/70" x-data="{ open: null, mobile: false }" @keydown.escape.window="open = null; mobile = false" @click.outside="open = null">
        <div class="max-w-7xl mx-auto px-5 lg:px-8 h-[4.5rem] flex items-center justify-between gap-6">
            <x-logo :href="main_url('/')" class="!text-[1.9rem]" />

            <nav class="hidden lg:flex items-center gap-1" aria-label="Main">
                @foreach ($menus as $label => $items)
                    <div class="relative">
                        <button type="button" class="nav-item" @click="open = open === '{{ $label }}' ? null : '{{ $label }}'" :aria-expanded="open === '{{ $label }}'">
                            {{ $label }} <x-glyph name="chevron-down" class="!w-4 !h-4 text-muted" />
                        </button>
                        <div class="nav-menu" x-cloak x-show="open === '{{ $label }}'" x-transition.opacity.duration.150ms>
                            @foreach ($items as [$icon, $title, $hint, $url])
                                <a href="{{ $url }}">
                                    <span class="rail-icon !w-8 !h-8"><x-glyph :name="$icon" /></span>
                                    <span>{{ $title }}@if ($hint)<small>{{ $hint }}</small>@endif</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                <a href="{{ main_url('/about/') }}" class="nav-item">About</a>
            </nav>

            <div class="flex items-center gap-2">
                <a href="{{ app_url('/search/') }}" class="icon-btn" aria-label="Search"><x-glyph name="search" /></a>
                <a href="{{ app_url('/') }}" class="btn hidden sm:inline-flex !h-11 !px-5 !text-[.95rem]">Start revising</a>
                <button type="button" class="icon-btn lg:hidden" @click="mobile = !mobile" :aria-expanded="mobile" aria-label="Menu"><x-glyph name="menu" /></button>
            </div>
        </div>

        {{-- Small screens: the same links, stacked. --}}
        <div class="lg:hidden border-t border-line bg-white" x-cloak x-show="mobile" x-transition.opacity>
            <nav class="max-w-7xl mx-auto px-5 py-4 grid gap-5 sm:grid-cols-3" aria-label="Main">
                @foreach ($menus as $label => $items)
                    <div>
                        <p class="section-label mb-1">{{ $label }}</p>
                        @foreach ($items as [$icon, $title, $hint, $url])
                            <a href="{{ $url }}" class="side-link !py-1.5"><x-glyph :name="$icon" /> {{ $title }}</a>
                        @endforeach
                    </div>
                @endforeach
                <a href="{{ app_url('/') }}" class="btn sm:col-span-3">Start revising</a>
            </nav>
        </div>
    </header>

    <main id="main" class="flex-1">
        {{ $slot }}
    </main>

    @include('partials.footer')
</body>
</html>
