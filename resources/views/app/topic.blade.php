<x-layouts.app page="topic" :subject="$subject" :topic-id="$topic->id" active="subject" :title="$seo['title']" :description="$seo['description']" :canonical="$seo['canonical']" :json-ld="$seo['jsonLd']">
    <div class="max-w-4xl">
        <x-crumbs :items="[[$subject->name, main_url($subject->path())], [$outline->topicNumber($topic).'. '.$topic->title, null]]" />
        <h1 class="page-title mt-4">{{ $outline->topicNumber($topic) ? $outline->topicNumber($topic).'. ' : '' }}{{ $topic->title }}</h1>
        @if ($topic->description)
            <p class="lead mt-2">{{ $topic->description }}</p>
        @endif

        <div class="mt-7 space-y-4">
            @foreach ($topic->chapters as $chapter)
                @continue($chapter->lessons->isEmpty())
                <section class="topic-card">
                    <div class="topic-head">
                        <h2 class="text-base"><a href="{{ main_url($chapter->path()) }}" class="text-ink no-underline hover:text-brand-dark">{{ $chapter->title }}</a></h2>
                        <span class="text-xs text-muted">{{ $chapter->lessons->count() }} {{ Str::plural('lesson', $chapter->lessons->count()) }}</span>
                    </div>
                    @foreach ($chapter->lessons as $lesson)
                        <a href="{{ main_url($lesson->path()) }}" class="lesson-row" data-boards="{{ $lesson->boards->pluck('slug')->join(',') }}" data-tier="{{ $lesson->tier->value }}" data-course="{{ $lesson->course->value }}" x-show="$store.prefs.shows($el.dataset)">
                            <span class="lesson-num">{{ $outline->lessonNumber($lesson) }}</span>
                            <span class="flex-1 min-w-0">
                                <span class="lesson-title">{{ $lesson->title }}</span>
                                @if ($lesson->summary)
                                    <span class="block text-xs text-muted mt-0.5 truncate">{{ $lesson->summary }}</span>
                                @endif
                            </span>
                            <x-lesson-chips :lesson="$lesson" class="hidden sm:inline-flex" />
                            <x-glyph name="chevron-right" class="!w-4 !h-4 text-faint" />
                        </a>
                    @endforeach
                </section>
            @endforeach
        </div>
    </div>
</x-layouts.app>
