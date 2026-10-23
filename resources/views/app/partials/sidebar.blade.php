@php
    $outline = $subject ? \App\Support\SubjectOutline::for($subject) : null;
@endphp
@if ($subject)
    <a href="{{ app_url($subject->path()) }}" class="side-title">
        <span>{{ $subject->fullName() }}</span>
        <x-glyph name="chevron-right" class="!w-4 !h-4 text-faint" />
    </a>
@else
    <p class="side-title">GCSE revision</p>
@endif

<nav class="space-y-0.5" aria-label="Revision">
    <a href="{{ app_url('/') }}" @class(['side-link', 'is-active' => $active === 'home'])><x-glyph name="home" /> Home</a>
    <a href="{{ app_url('/gcse/') }}" @class(['side-link', 'is-active' => $active === 'subjects'])><x-glyph name="book-open" /> Subjects</a>

    @if ($subject)
        <a href="{{ app_url($subject->path()) }}" @class(['side-link', 'is-active' => $active === 'subject'])>
            <x-glyph :name="$subject->iconName()" /> {{ $subject->name }}
        </a>
        @if ($outline->topics->isNotEmpty())
            <div class="py-1 space-y-0.5">
                @foreach ($outline->topics as $topic)
                    <a href="{{ app_url($topic->path()) }}" @class(['side-sub', 'is-active' => $topicId === $topic->id]) title="{{ $topic->title }}">{{ $outline->topicNumber($topic) }}. {{ $topic->title }}</a>
                @endforeach
            </div>
        @endif
    @endif
</nav>

<nav class="mt-5 pt-4 border-t border-line space-y-0.5" aria-label="More">
    @if ($subject)
        <a href="{{ app_url($subject->path().'mock-exams/') }}" @class(['side-link', 'is-active' => $active === 'mocks'])>
            <x-glyph name="clipboard-list" /> Mock exams
            @if ($outline->mockExams)
                <span class="ml-auto chip">{{ $outline->mockExams }}</span>
            @endif
        </a>
    @endif
    <a href="{{ app_url('/saved/') }}" @class(['side-link', 'is-active' => $active === 'saved'])>
        <x-glyph name="bookmark" /> Saved
        <span class="ml-auto chip" x-cloak x-show="$store.saved.items.length" x-text="$store.saved.items.length"></span>
    </a>
</nav>

{{-- Your course: hides lessons that are not on it. Saved on this device. --}}
<div class="mt-6 rounded-xl border border-line bg-white p-3.5">
    <p class="flex items-center gap-2 text-sm font-semibold text-ink"><x-glyph name="sliders" class="!w-4 !h-4 text-brand" /> Your course</p>
    <p class="mt-2 text-xs text-muted">Exam board</p>
    <div class="pills mt-1">
        @foreach (\App\Support\Navigation::boards() as $b)
            <button type="button" class="pill pill-sm" @click="$store.prefs.set('board', '{{ $b->slug }}')" :aria-pressed="$store.prefs.board === '{{ $b->slug }}'">{{ $b->name }}</button>
        @endforeach
    </div>
    <p class="mt-2.5 text-xs text-muted">Tier</p>
    <div class="pills mt-1">
        <button type="button" class="pill pill-sm" @click="$store.prefs.set('tier', 'foundation')" :aria-pressed="$store.prefs.tier === 'foundation'">Foundation</button>
        <button type="button" class="pill pill-sm" @click="$store.prefs.set('tier', 'higher')" :aria-pressed="$store.prefs.tier === 'higher'">Higher</button>
    </div>
    <p class="mt-2.5 text-xs text-muted">Science</p>
    <div class="pills mt-1">
        <button type="button" class="pill pill-sm" @click="$store.prefs.set('course', 'combined')" :aria-pressed="$store.prefs.course === 'combined'">Combined</button>
        <button type="button" class="pill pill-sm" @click="$store.prefs.set('course', 'separate')" :aria-pressed="$store.prefs.course === 'separate'">Separate</button>
    </div>
</div>
