@php
    $payload = $questions->map(fn ($i) => [
        'id' => $i['model']->id,
        'marks' => $i['model']->marks,
        'answer' => $i['model']->options ? $i['model']->answer : null,
    ])->values();
    $number = $outline->lessonNumber($lesson);
    $topicNumber = $outline->topicNumber($topic);
    $questionCount = $questions->count();
@endphp
<x-layouts.app page="questions" :subject="$subject" :topic-id="$topic->id" active="subject" :lesson="$lesson->id" :title="$seo['title']" :description="$seo['description']" :canonical="$seo['canonical']" :robots="$seo['robots']" :json-ld="$seo['jsonLd']">
    <div class="with-rail" x-data="questionSet('lesson-{{ $lesson->id }}', @js($payload))">
        <article class="min-w-0">
            <x-crumbs :items="[[$subject->name, app_url($subject->path())], [($topicNumber ? $topicNumber.'. ' : '').$topic->title, app_url($topic->path())], [($number ? $number.' ' : '').$lesson->title, app_url($lesson->path())], ['Exam questions', null]]" />

            <h1 class="page-title mt-4">@if ($number)<span>{{ $number }}</span> @endif{{ $lesson->title }}</h1>
            <p class="lead mt-2">{{ $questionCount }} exam {{ Str::plural('question', $questionCount) }} worth {{ $payload->sum('marks') }} marks. Write your answer, open the mark scheme, then give yourself the marks you earned. Your answers are saved on this device.</p>

            @include('app.partials.tabs', ['current' => 'questions'])

            {{-- Score, for small screens (the rail shows it on large ones). --}}
            <div class="mt-5 xl:hidden rounded-xl border border-line p-4 flex items-center gap-4">
                <div class="flex-1">
                    <div class="progress"><span :style="'width:' + (total ? Math.round(scored / total * 100) : 0) + '%'"></span></div>
                    <p class="text-xs text-muted mt-1.5" x-text="marked + ' of {{ $questionCount }} marked'"></p>
                </div>
                <p class="score text-lg" x-text="scored + ' / ' + total">0 / {{ $payload->sum('marks') }}</p>
            </div>

            <div class="mt-6 space-y-4">
                @foreach ($questions as $item)
                    @include('app.partials.question', ['item' => $item, 'number' => $loop->iteration, 'mode' => 'practice'])
                @endforeach
            </div>

            <div class="mt-8 flex flex-wrap items-center justify-between gap-3 no-print">
                <a href="{{ app_url($lesson->path()) }}" class="btn btn-outline"><x-glyph name="arrow-left" /> Back to the notes</a>
                <button type="button" class="btn btn-quiet" @click="reset()"><x-glyph name="rotate" /> Clear my answers</button>
            </div>

            <p class="mt-8 text-sm text-muted no-print xl:hidden">
                Found a mistake? <button type="button" class="text-brand font-medium hover:underline" @click="$dispatch('open-report')">Report it</button>
            </p>
        </article>

        <aside class="rail" aria-label="Your score">
            <div class="rail-inner">
                <div class="rail-card">
                    <p class="rail-title">Your score</p>
                    <p class="score text-2xl" x-text="scored + ' / ' + total">0 / {{ $payload->sum('marks') }}</p>
                    <div class="progress mt-2"><span :style="'width:' + (total ? Math.round(scored / total * 100) : 0) + '%'"></span></div>
                    <p class="text-xs text-muted mt-2" x-text="marked + ' of {{ $questionCount }} questions marked'"></p>
                </div>

                <nav class="rail-card toc" aria-label="Questions">
                    <p class="rail-title">Questions</p>
                    @foreach ($questions as $item)
                        <a href="#q{{ $item['model']->id }}" class="flex justify-between gap-2">
                            <span>Question {{ $loop->iteration }}</span>
                            <span class="text-faint" x-text="q({{ $item['model']->id }}).mark !== null ? q({{ $item['model']->id }}).mark + '/{{ $item['model']->marks }}' : '{{ $item['model']->marks }} {{ Str::plural('mark', $item['model']->marks) }}'"></span>
                        </a>
                    @endforeach
                </nav>

                <div class="rail-card">
                    <p class="rail-title">Found a mistake?</p>
                    <p class="text-xs text-muted leading-relaxed">Let us know so we can improve these questions.</p>
                    <button type="button" class="mt-2 inline-flex items-center gap-2 text-sm font-medium text-brand hover:underline" @click="$dispatch('open-report')">
                        <x-glyph name="flag" class="!w-4 !h-4" /> Report
                    </button>
                </div>
            </div>
        </aside>
    </div>

    @include('app.partials.report', ['lessonId' => $lesson->id])
</x-layouts.app>
