{{-- app.openrevise.com: top bar, subject sidebar and the page. --}}
@props([
    'title' => 'OpenRevise',
    'description' => null,
    'canonical' => null,
    'robots' => 'index, follow',
    'jsonLd' => [],
    'page' => '',
    'ogType' => 'website',
    'subject' => null,
    'topicId' => null,
    'active' => null,
    'board' => null,
    'lesson' => null,
])
<!DOCTYPE html>
<html lang="en-GB">
<head>
    @include('partials.head')
</head>
<body x-data data-page="{{ $page }}" @if ($subject) data-subject="{{ $subject->slug }}" @endif @if ($board) data-board="{{ $board }}" @endif @if ($lesson) data-lesson="{{ $lesson }}" @endif>
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 btn">Skip to content</a>

    <header class="topbar">
        <div class="h-full flex items-center gap-2 px-3 lg:px-0">
            <button type="button" class="icon-btn lg:hidden" @click="$store.ui.menu = !$store.ui.menu" :aria-expanded="$store.ui.menu" aria-controls="sidebar" aria-label="Menu">
                <x-icon name="menu" x-show="!$store.ui.menu" />
                <x-icon name="x" x-cloak x-show="$store.ui.menu" />
            </button>
            <div class="lg:w-[15.5rem] lg:pl-6 shrink-0">
                <x-logo :href="app_url('/')" />
            </div>
            <div class="flex-1 min-w-0 flex justify-end sm:justify-start lg:pl-9">
                @include('app.partials.search')
            </div>
            <a href="{{ app_url('/saved/') }}" class="icon-btn lg:mr-6" aria-label="Saved lessons" title="Saved lessons">
                <x-icon name="bookmark" />
                <span class="count-dot" x-cloak x-show="$store.saved.items.length" x-text="$store.saved.items.length"></span>
            </a>
        </div>
    </header>

    <div class="shell">
        <aside id="sidebar" class="sidebar" :class="$store.ui.menu && 'is-open'" aria-label="Sections">
            <div class="sidebar-inner">
                @include('app.partials.sidebar', ['subject' => $subject, 'topicId' => $topicId, 'active' => $active])
            </div>
        </aside>
        <div x-cloak x-show="$store.ui.menu" @click="$store.ui.menu = false" class="fixed inset-0 top-[3.75rem] z-30 bg-gray-900/30 lg:hidden"></div>

        <main id="main" class="page">
            {{ $slot }}
        </main>
    </div>

    @include('partials.footer')
</body>
</html>
