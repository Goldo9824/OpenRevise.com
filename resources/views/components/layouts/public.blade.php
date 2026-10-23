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
<!DOCTYPE html>
<html lang="en-GB">
<head>
    @include('partials.head')
</head>
<body x-data data-page="{{ $page }}" class="flex flex-col">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 btn">Skip to content</a>

    <header class="topbar">
        <div class="h-full max-w-6xl mx-auto px-5 flex items-center justify-between gap-6">
            <x-logo :href="main_url('/')" />
            <nav class="flex items-center gap-1 sm:gap-2" aria-label="Main">
                <a href="{{ app_url('/gcse/') }}" class="hidden sm:inline-flex px-3 py-2 rounded-lg text-sm font-medium text-body no-underline hover:bg-gray-100">Subjects</a>
                <a href="{{ main_url('/about/') }}" class="hidden sm:inline-flex px-3 py-2 rounded-lg text-sm font-medium text-body no-underline hover:bg-gray-100">About</a>
                <a href="{{ main_url('/contact/') }}" class="hidden md:inline-flex px-3 py-2 rounded-lg text-sm font-medium text-body no-underline hover:bg-gray-100">Contact</a>
                <a href="{{ app_url('/') }}" class="btn btn-sm ml-2">Start revising <x-glyph name="arrow-right" /></a>
            </nav>
        </div>
    </header>

    <main id="main" class="flex-1">
        {{ $slot }}
    </main>

    @include('partials.footer')
</body>
</html>
