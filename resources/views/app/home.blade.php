<x-layouts.app page="home" active="home" :title="$seo['title']" :description="$seo['description']" :canonical="$seo['canonical']" :json-ld="$seo['jsonLd']">
    <div class="max-w-5xl">
        <h1 class="page-title">What are you revising?</h1>
        <p class="lead mt-2">Pick your exam board, then a subject. Every lesson has notes, key terms and exam questions with the mark scheme.</p>

        @if ($boards->isNotEmpty())
            <div class="mt-5 flex flex-wrap items-center gap-3">
                <span class="text-sm font-medium text-ink">Exam board</span>
                <div class="pills" role="group" aria-label="Exam board">
                    @foreach ($boards as $b)
                        <button type="button" class="pill" @click="$store.prefs.set('board', '{{ $b->slug }}')" :aria-pressed="$store.prefs.board === '{{ $b->slug }}'">{{ $b->name }}</button>
                    @endforeach
                </div>
                <span class="text-xs text-muted" x-show="!$store.prefs.board">Not sure? Ask your teacher. It is on the front of your past papers.</span>
            </div>
        @endif

        {{-- Picked up from this device. --}}
        <section class="mt-8" x-cloak x-show="$store.recent.items.length" aria-labelledby="recent">
            <h2 id="recent" class="section-label">Continue where you left off</h2>
            <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <template x-for="item in $store.recent.items.slice(0, 3)" :key="item.url">
                    <a :href="item.url" class="card p-4 no-underline hover:border-brand-200 flex items-start gap-3">
                        <span class="rail-icon"><x-glyph name="file-text" /></span>
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-ink truncate" x-text="item.title"></span>
                            <span class="block text-xs text-muted mt-0.5" x-text="item.subject"></span>
                        </span>
                    </a>
                </template>
            </div>
        </section>

        <section class="mt-8" aria-labelledby="subjects">
            <h2 id="subjects" class="section-label">Subjects</h2>
            @if ($subjects->isEmpty())
                <p class="mt-3 text-muted">The first subjects are being written. Come back soon.</p>
            @else
                <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($subjects as $subject)
                        @include('app.partials.subject-card', ['subject' => $subject, 'count' => $counts[$subject->id] ?? 0])
                    @endforeach
                </div>
            @endif
        </section>

        <section class="mt-10 grid gap-3 md:grid-cols-3" aria-label="How OpenRevise works">
            @foreach ([
                ['file-text', 'Read the notes', 'Short lessons with the key terms first, then labelled diagrams, tables and formulas.'],
                ['help-circle', 'Answer exam questions', 'Every lesson ends with questions in your board\'s style, with the mark scheme and a model answer.'],
                ['clipboard-list', 'Sit a mock exam', 'Full papers with the real time and marks for your board, timed on screen.'],
            ] as [$icon, $heading, $text])
                <div class="card p-5">
                    <span class="rail-icon"><x-glyph :name="$icon" /></span>
                    <h3 class="mt-3 text-sm">{{ $heading }}</h3>
                    <p class="mt-1 text-sm text-muted">{{ $text }}</p>
                </div>
            @endforeach
        </section>
    </div>
</x-layouts.app>
