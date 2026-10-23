@php
    $number = $outline->lessonNumber($lesson);
    $topicNumber = $outline->topicNumber($topic);
    $saveItem = ['url' => app_url($lesson->path()), 'title' => ($number ? $number.' ' : '').$lesson->title, 'subject' => $subject->fullName()];
@endphp
<x-layouts.app page="lesson" :subject="$subject" :topic-id="$topic->id" active="subject" :lesson="$lesson->id" :title="$seo['title']" :description="$seo['description']" :canonical="$seo['canonical']" :robots="$seo['robots']" :og-type="$seo['ogType']" :json-ld="$seo['jsonLd']">
    @unless ($lesson->isPublished())
        <div class="mb-5 rounded-lg bg-amber-50 border border-amber-200 text-amber-900 text-sm font-medium px-4 py-2.5">Preview: this lesson is a {{ $lesson->status->value }}, so students cannot see it yet.</div>
    @endunless

    <div class="with-rail" x-data x-init="$store.recent.add(@js($saveItem))">
        <article class="min-w-0">
            <x-crumbs :items="[[$subject->name, app_url($subject->path())], [($topicNumber ? $topicNumber.'. ' : '').$topic->title, app_url($topic->path())], [($number ? $number.' ' : '').$lesson->title, null]]" />

            <div class="mt-4 flex items-start justify-between gap-4">
                <h1 class="page-title">@if ($number)<span>{{ $number }}</span> @endif{{ $doc->title ?? $lesson->title }}</h1>
                <button type="button" class="icon-btn shrink-0 no-print" @click="$store.saved.toggle(@js($saveItem))"
                        :aria-pressed="$store.saved.has(@js($saveItem['url']))"
                        :title="$store.saved.has(@js($saveItem['url'])) ? 'Saved. Click to remove' : 'Save this lesson'"
                        :class="$store.saved.has(@js($saveItem['url'])) && '!text-brand [&_path]:fill-current'">
                    <x-glyph name="bookmark" />
                    <span class="sr-only">Save this lesson</span>
                </button>
            </div>

            @if ($lesson->summary)
                <p class="lead mt-2">{{ $lesson->summary }}</p>
            @endif

            <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-2 text-xs text-muted">
                <x-lesson-chips :lesson="$lesson" />
                <span class="inline-flex items-center gap-1"><x-glyph name="clock" class="!w-3.5 !h-3.5" /> {{ $doc->readingMinutes() }} min read</span>
            </div>

            @include('app.partials.tabs', ['current' => 'notes'])

            <div class="notes mt-6">
                {!! $doc->html !!}
            </div>

            @if ($questionCount)
                <section class="mt-10 rounded-xl border border-brand-100 bg-brand-50 p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 no-print" aria-labelledby="practise">
                    <div>
                        <h2 id="practise" class="text-base">Test yourself on this lesson</h2>
                        <p class="text-sm text-body mt-1">{{ $questionCount }} exam {{ Str::plural('question', $questionCount) }} &middot; {{ $questionMarks }} marks &middot; with mark schemes and model answers</p>
                    </div>
                    <a href="{{ app_url($lesson->questionsPath()) }}" class="btn shrink-0">Start questions <x-glyph name="arrow-right" /></a>
                </section>
            @endif

            <nav class="mt-10 flex flex-wrap items-center justify-between gap-3 no-print" aria-label="Previous and next lesson">
                @if ($previous)
                    <a href="{{ app_url($previous->path()) }}" class="btn btn-outline max-w-full">
                        <x-glyph name="arrow-left" /> <span class="truncate">{{ $outline->lessonNumber($previous) }} {{ $previous->title }}</span>
                    </a>
                @else
                    <span></span>
                @endif
                @if ($next)
                    <a href="{{ app_url($next->path()) }}" class="btn max-w-full">
                        <span class="truncate">{{ $outline->lessonNumber($next) }} {{ $next->title }}</span> <x-glyph name="arrow-right" />
                    </a>
                @endif
            </nav>

            <p class="mt-8 text-sm text-muted no-print xl:hidden">
                Found a mistake? <button type="button" class="text-brand font-medium hover:underline" @click="$dispatch('open-report')">Report it</button>
            </p>
        </article>

        <aside class="rail" aria-label="About this lesson">
            <div class="rail-inner">
                @if (count($doc->headings))
                    <nav class="rail-card toc" aria-label="On this page">
                        <p class="rail-title">On this page</p>
                        @if ($doc->keyTerms)
                            <a href="#{{ $doc->keyTerms[0]['id'] }}">Key terms</a>
                        @endif
                        @foreach ($doc->headings as $h)
                            <a href="#{{ $h['id'] }}" @class(['is-sub' => $h['level'] === 3])>{{ $h['text'] }}</a>
                        @endforeach
                    </nav>
                @endif

                <div class="rail-card">
                    <p class="rail-title">Download</p>
                    <button type="button" class="rail-link" onclick="window.print()">
                        <span class="rail-icon"><x-glyph name="file-down" /></span>
                        <span><span class="rail-link-title">Save as PDF</span><span class="rail-link-meta">Choose "Save as PDF" when printing</span></span>
                    </button>
                    <button type="button" class="rail-link" onclick="window.print()">
                        <span class="rail-icon"><x-glyph name="printer" /></span>
                        <span class="rail-link-title self-center">Print notes</span>
                    </button>
                </div>

                <div class="rail-card">
                    <p class="rail-title">Related resources</p>
                    @if ($questionCount)
                        <a href="{{ app_url($lesson->questionsPath()) }}" class="rail-link">
                            <span class="rail-icon"><x-glyph name="help-circle" /></span>
                            <span><span class="rail-link-title">Exam questions</span><span class="rail-link-meta">{{ $questionCount }} {{ Str::plural('question', $questionCount) }} &middot; {{ $questionMarks }} marks</span></span>
                        </a>
                    @endif
                    @if ($outline->mockExams)
                        <a href="{{ app_url($subject->path().'mock-exams/') }}" class="rail-link">
                            <span class="rail-icon"><x-glyph name="clipboard-list" /></span>
                            <span><span class="rail-link-title">{{ $subject->name }} mock exams</span><span class="rail-link-meta">{{ $outline->mockExams }} {{ Str::plural('paper', $outline->mockExams) }}</span></span>
                        </a>
                    @endif
                    @if ($next)
                        <a href="{{ app_url($next->path()) }}" class="rail-link">
                            <span class="rail-icon"><x-glyph name="arrow-right" /></span>
                            <span><span class="rail-link-title">Next: {{ $next->title }}</span><span class="rail-link-meta">Lesson {{ $outline->lessonNumber($next) }}</span></span>
                        </a>
                    @endif
                    <a href="{{ app_url($topic->path()) }}" class="rail-link">
                        <span class="rail-icon"><x-glyph name="layers" /></span>
                        <span><span class="rail-link-title">All of {{ $topic->title }}</span><span class="rail-link-meta">Topic {{ $topicNumber }}</span></span>
                    </a>
                </div>

                <div class="rail-card">
                    <p class="rail-title">Found a mistake?</p>
                    <p class="text-xs text-muted leading-relaxed">Let us know so we can improve these notes.</p>
                    <button type="button" class="mt-2 inline-flex items-center gap-2 text-sm font-medium text-brand hover:underline" @click="$dispatch('open-report')">
                        <x-glyph name="flag" class="!w-4 !h-4" /> Report
                    </button>
                </div>
            </div>
        </aside>
    </div>

    @include('app.partials.report', ['lessonId' => $lesson->id])
</x-layouts.app>
