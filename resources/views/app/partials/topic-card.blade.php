{{--
    A topic with its chapters and lessons. Lessons not on the student's
    course (board, tier, science course) are hidden by site.js.
--}}
@php
    $lessonsOf = fn ($chapter) => isset($onlyBoard) ? $chapter->lessons->filter(fn ($l) => $l->boards->contains('id', $onlyBoard)) : $chapter->lessons;
    $all = $topic->chapters->flatMap(fn ($c) => $lessonsOf($c));
@endphp
<section class="topic-card" data-boards="{{ $all->flatMap->boards->pluck('slug')->unique()->join(',') }}" x-show="$store.prefs.shows($el.dataset)">
    <div class="topic-head">
        <h2 class="text-base">
            <a href="{{ main_url($topic->path()) }}" class="text-ink no-underline hover:text-brand-dark">{{ $outline->topicNumber($topic) }}. {{ $topic->title }}</a>
        </h2>
        <span class="text-xs text-muted whitespace-nowrap">{{ $all->count() }} {{ Str::plural('lesson', $all->count()) }}</span>
    </div>
    @foreach ($topic->chapters as $chapter)
        @php $lessons = $lessonsOf($chapter); @endphp
        @continue($lessons->isEmpty())
        @if ($topic->chapters->count() > 1 || ! empty($showChapter))
            <a href="{{ main_url($chapter->path()) }}" class="chapter-label block no-underline hover:text-ink">{{ $chapter->title }}</a>
        @endif
        @foreach ($lessons as $lesson)
            <a href="{{ main_url($lesson->path()) }}" class="lesson-row" data-boards="{{ $lesson->boards->pluck('slug')->join(',') }}" data-tier="{{ $lesson->tier->value }}" data-course="{{ $lesson->course->value }}" x-show="$store.prefs.shows($el.dataset)">
                <span class="lesson-num">{{ $outline->lessonNumber($lesson) }}</span>
                <span class="flex-1 min-w-0">
                    <span class="lesson-title">{{ $lesson->title }}</span>
                    @if (! empty($withSummary) && $lesson->summary)
                        <span class="block text-xs text-muted mt-0.5 truncate">{{ $lesson->summary }}</span>
                    @endif
                </span>
                <x-lesson-chips :lesson="$lesson" :boards="false" class="hidden sm:inline-flex" />
                @if ($lesson->questions_count)
                    <span class="text-xs text-muted whitespace-nowrap hidden md:inline">{{ $lesson->questions_count }} {{ Str::plural('question', $lesson->questions_count) }}</span>
                @endif
                <x-glyph name="chevron-right" class="!w-4 !h-4 text-faint" />
            </a>
        @endforeach
    @endforeach
</section>
