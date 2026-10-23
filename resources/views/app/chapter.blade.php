<x-layouts.app page="chapter" :subject="$subject" :topic-id="$topic->id" active="subject" :title="$seo['title']" :description="$seo['description']" :canonical="$seo['canonical']" :json-ld="$seo['jsonLd']">
    <div class="max-w-4xl">
        <x-crumbs :items="[[$subject->name, app_url($subject->path())], [$outline->topicNumber($topic).'. '.$topic->title, app_url($topic->path())], [$chapter->title, null]]" />
        <h1 class="page-title mt-4">{{ $chapter->title }}</h1>
        <p class="lead mt-2">{{ $chapter->description ?: $lessons->count().' '.Str::plural('lesson', $lessons->count()).' in '.$topic->title.'.' }}</p>
        @if ($lessons->sum('questions_count'))
            <p class="mt-2 text-sm text-muted">{{ $lessons->sum('questions_count') }} exam questions &middot; {{ (int) $lessons->sum('questions_sum_marks') }} marks</p>
        @endif

        <section class="topic-card mt-7">
            @foreach ($lessons as $lesson)
                <a href="{{ app_url($lesson->path()) }}" class="lesson-row" data-boards="{{ $lesson->boards->pluck('slug')->join(',') }}" data-tier="{{ $lesson->tier->value }}" data-course="{{ $lesson->course->value }}" x-show="$store.prefs.shows($el.dataset)">
                    <span class="lesson-num">{{ $outline->lessonNumber($lesson) }}</span>
                    <span class="flex-1 min-w-0">
                        <span class="lesson-title">{{ $lesson->title }}</span>
                        @if ($lesson->summary)
                            <span class="block text-xs text-muted mt-0.5">{{ $lesson->summary }}</span>
                        @endif
                    </span>
                    <x-lesson-chips :lesson="$lesson" class="hidden sm:inline-flex" />
                    <x-glyph name="chevron-right" class="!w-4 !h-4 text-faint" />
                </a>
            @endforeach
        </section>
    </div>
</x-layouts.app>
