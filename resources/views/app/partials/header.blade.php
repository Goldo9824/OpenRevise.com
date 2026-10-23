<header class="site-header" x-data="{ open: false }" @keydown.escape.window="open = false">
    <div class="wrap flex items-center justify-between gap-4 h-16">
        <div class="flex items-baseline gap-3">
            <x-wordmark :href="app_url('/')" />
            <span class="eyebrow hidden sm:inline">GCSE</span>
        </div>
        <nav class="flex items-center gap-4" aria-label="App">
            <a href="{{ app_url('/') }}" class="nav-link hidden sm:inline">Subjects</a>
            <button type="button" class="btn btn-ghost btn-small" @click="open = !open" :aria-expanded="open" aria-controls="course-panel">
                <span class="text-ink-3 font-semibold hidden sm:inline">Your course:</span>
                <span x-text="$store.prefs.label()">All boards</span>
            </button>
        </nav>
    </div>
    <div id="course-panel" x-cloak x-show="open" x-transition.opacity class="border-t border-rule bg-card">
        <div class="wrap py-5 grid gap-5 md:grid-cols-3">
            <div>
                <p class="eyebrow mb-2">Exam board</p>
                <div class="segmented" role="group" aria-label="Exam board">
                    @foreach (\App\Support\Navigation::boards() as $b)
                        <button type="button" @click="$store.prefs.set('board', '{{ $b->slug }}')" :aria-pressed="$store.prefs.board === '{{ $b->slug }}'">{{ $b->name }}</button>
                    @endforeach
                </div>
            </div>
            <div>
                <p class="eyebrow mb-2">Tier</p>
                <div class="segmented" role="group" aria-label="Tier">
                    <button type="button" @click="$store.prefs.set('tier', 'foundation')" :aria-pressed="$store.prefs.tier === 'foundation'">Foundation</button>
                    <button type="button" @click="$store.prefs.set('tier', 'higher')" :aria-pressed="$store.prefs.tier === 'higher'">Higher</button>
                </div>
            </div>
            <div>
                <p class="eyebrow mb-2">Science course</p>
                <div class="segmented" role="group" aria-label="Science course">
                    <button type="button" @click="$store.prefs.set('course', 'combined')" :aria-pressed="$store.prefs.course === 'combined'">Combined</button>
                    <button type="button" @click="$store.prefs.set('course', 'separate')" :aria-pressed="$store.prefs.course === 'separate'">Separate</button>
                </div>
            </div>
            <p class="md:col-span-3 text-sm text-ink-2">Lessons that are not on your course are hidden from lists. Tap a choice again to clear it. This is saved on this device only.</p>
        </div>
    </div>
</header>
