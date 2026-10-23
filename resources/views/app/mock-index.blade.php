<x-layouts.app page="mock-index" :subject="$subject" active="mocks" :title="$seo['title']" :description="$seo['description']" :canonical="$seo['canonical']" :json-ld="$seo['jsonLd']">
    <div class="max-w-4xl">
        <x-crumbs :items="[[$subject->name, main_url($subject->path())], ['Mock exams', null]]" />
        <h1 class="page-title mt-4">{{ $subject->fullName() }} mock exams</h1>
        <p class="lead mt-2">Full papers written by OpenRevise, with the time and marks of the real exam for your board. Sit one on screen, timed or untimed, then mark it with the mark scheme.</p>

        <div class="mt-7 space-y-4">
            @forelse ($groups as $boardName => $exams)
                <section class="topic-card" data-boards="{{ $exams->first()->board->slug }}" x-show="$store.prefs.shows($el.dataset)">
                    <div class="topic-head">
                        <h2 class="text-base">{{ $boardName }}</h2>
                        <span class="text-xs text-muted">{{ $exams->count() }} {{ Str::plural('paper', $exams->count()) }}</span>
                    </div>
                    @foreach ($exams as $exam)
                        @php $exam->setRelation('subject', $subject); @endphp
                        <a href="{{ main_url($exam->path()) }}" class="lesson-row" data-tier="{{ $exam->paperTemplate->tier === 'higher' ? 'higher' : 'all' }}" x-show="$store.prefs.shows($el.dataset)">
                            <span class="rail-icon"><x-glyph name="clipboard-list" /></span>
                            <span class="flex-1 min-w-0">
                                <span class="lesson-title">{{ $exam->title }}</span>
                                <span class="block text-xs text-muted mt-0.5">
                                    {{ $exam->paperTemplate->name }}@if ($exam->paperTemplate->tierLabel()) &middot; {{ $exam->paperTemplate->tierLabel() }}@endif @if ($exam->topic) &middot; {{ $exam->topic->title }}@endif
                                </span>
                            </span>
                            <span class="text-xs text-muted whitespace-nowrap hidden sm:inline-flex items-center gap-1"><x-glyph name="clock" class="!w-3.5 !h-3.5" /> {{ $exam->paperTemplate->durationLabel() }} &middot; {{ $exam->paperTemplate->total_marks }} marks</span>
                            <x-glyph name="chevron-right" class="!w-4 !h-4 text-faint" />
                        </a>
                    @endforeach
                </section>
            @empty
                <p class="text-muted">No mock exams for {{ $subject->name }} yet.</p>
            @endforelse
        </div>
    </div>
</x-layouts.app>
