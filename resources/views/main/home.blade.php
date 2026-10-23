<x-layouts.public page="landing" :title="$seo['title']" :description="$seo['description']" :canonical="$seo['canonical']" :json-ld="$seo['jsonLd']">
    {{-- Hero: what it is, next to a real piece of a lesson. --}}
    <section class="border-b border-line bg-gradient-to-b from-brand-50/70 to-white">
        <div class="max-w-6xl mx-auto px-5 py-14 md:py-20 grid gap-12 lg:grid-cols-[1.05fr_1fr] lg:items-center">
            <div>
                <span class="chip chip-brand">Free &middot; GCSE &middot; {{ $boards->pluck('name')->join(', ', ' and ') ?: 'AQA, Edexcel and OCR' }}</span>
                <h1 class="mt-5 text-4xl md:text-5xl font-bold tracking-tight leading-[1.08]">Revise for your GCSEs with notes, exam questions and mock papers.</h1>
                <p class="mt-5 text-lg text-body max-w-xl">Short notes on every topic, exam questions with mark schemes after every lesson, and timed mock exams, written for your exam board. No account, no sign-up, nothing to pay.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ app_url('/') }}" class="btn btn-lg">Start revising <x-glyph name="arrow-right" /></a>
                    <a href="#how" class="btn btn-outline btn-lg">How it works</a>
                </div>
            </div>

            {{-- A lesson, as it looks in the app. --}}
            <figure class="card shadow-[0_24px_60px_-28px_rgba(15,107,86,.45)] overflow-hidden" aria-label="A preview of a lesson page">
                <div class="px-5 py-3 border-b border-line flex items-center gap-2 text-xs text-muted">
                    <span>Biology</span><x-glyph name="chevron-right" class="!w-3 !h-3" /><span>1. Cell biology</span><x-glyph name="chevron-right" class="!w-3 !h-3" /><span class="text-body">1.3 Microscopy</span>
                </div>
                <div class="p-5 md:p-6">
                    <p class="text-xl font-bold text-ink">1.3 Microscopy and magnification</p>
                    <div class="tabs mt-3 text-sm" aria-hidden="true">
                        <span class="tab is-active"><x-glyph name="file-text" /> Notes</span>
                        <span class="tab"><x-glyph name="help-circle" /> Exam questions <span class="tab-count">4</span></span>
                    </div>
                    <div class="keyterms !my-4 text-sm">
                        <p class="keyterms-title !text-sm"><x-glyph name="lightbulb" /> Key terms</p>
                        <ul>
                            <li><dfn>Magnification</dfn>: how many times bigger the image is than the object.</li>
                            <li><dfn>Resolution</dfn>: the smallest distance at which two points can be told apart.</li>
                        </ul>
                    </div>
                    <div class="formula !my-4 !text-base"><span class="text-ink">magnification =
                        <span class="inline-flex flex-col items-center align-middle mx-1 text-sm"><span class="px-1 border-b border-ink">image size</span><span>real size</span></span></span></div>
                    <div class="flex items-center justify-between gap-3 rounded-lg bg-brand-50 border border-brand-100 px-4 py-3 text-sm">
                        <span><span class="font-semibold text-ink">Test yourself</span> <span class="text-muted">&middot; 4 questions &middot; 6 marks</span></span>
                        <span class="btn btn-sm">Start <x-glyph name="arrow-right" /></span>
                    </div>
                </div>
            </figure>
        </div>
    </section>

    {{-- How it works. --}}
    <section id="how" class="max-w-6xl mx-auto px-5 py-16 md:py-20">
        <p class="section-label">How it works</p>
        <h2 class="mt-2 text-2xl md:text-3xl font-bold max-w-2xl">Everything for one topic in one place, in the order you need it</h2>
        <div class="mt-8 grid gap-4 md:grid-cols-3">
            @foreach ([
                ['file-text', 'Revision notes', 'One idea per lesson, written for your board\'s specification. Key terms first, then labelled diagrams, tables and formulas. Higher-tier and Separate Science lessons are marked, so you can hide what is not on your course.'],
                ['help-circle', 'Exam questions', 'Realistic questions in the style of your board after every lesson, from one-mark recall to six-mark answers. Write your answer, open the mark scheme and the model answer, and give yourself the marks.'],
                ['clipboard-list', 'Mock exams', 'Full papers with the same time and marks as the real thing for your board. Sit one against the clock on screen, then mark it question by question and see your total.'],
            ] as [$icon, $title, $text])
                <div class="card p-6">
                    <span class="rail-icon !w-10 !h-10"><x-glyph :name="$icon" /></span>
                    <h3 class="mt-4 text-lg">{{ $title }}</h3>
                    <p class="mt-2 text-sm text-body leading-relaxed">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Boards and subjects. --}}
    <section class="border-y border-line bg-panel">
        <div class="max-w-6xl mx-auto px-5 py-16 md:py-20 grid gap-10 lg:grid-cols-[1fr_1.4fr]">
            <div>
                <p class="section-label">Your course</p>
                <h2 class="mt-2 text-2xl md:text-3xl font-bold">Pick your exam board</h2>
                <p class="mt-3 text-body">When the boards ask for the same thing there is one lesson for all of them; when they differ, each board gets its own. Not sure which board you are on? Your teacher will know, and it is on the front of your past papers.</p>
            </div>
            <div>
                <div class="grid gap-3 sm:grid-cols-3">
                    @forelse ($boards as $board)
                        <a href="{{ app_url('/') }}?board={{ $board->slug }}" class="subject-card !block hover:!border-brand">
                            <span class="block text-2xl font-bold text-ink">{{ $board->name }}</span>
                            <span class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-brand">See subjects <x-glyph name="arrow-right" class="!w-4 !h-4" /></span>
                        </a>
                    @empty
                        <a href="{{ app_url('/') }}" class="btn">See all subjects</a>
                    @endforelse
                </div>
                @if ($subjects->isNotEmpty())
                    <ul class="mt-6 flex flex-wrap gap-2">
                        @foreach ($subjects as $subject)
                            <li><a href="{{ app_url($subject->path()) }}" class="chip !h-8 !px-3 !text-sm !font-medium bg-white border border-line no-underline hover:border-brand-200"><x-glyph :name="$subject->iconName()" class="!w-4 !h-4 text-brand" /> {{ $subject->name }}</a></li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </section>

    {{-- Questions people ask. --}}
    <section class="max-w-6xl mx-auto px-5 py-16 md:py-20 grid gap-10 lg:grid-cols-[1fr_1.4fr]">
        <div>
            <p class="section-label">Questions</p>
            <h2 class="mt-2 text-2xl md:text-3xl font-bold">Before you start</h2>
        </div>
        <div class="card divide-y divide-line">
            @foreach ([
                ['Is it really free?', 'Yes. Every note, question and mock exam is free. There is nothing to unlock and no premium version.'],
                ['Do I need an account?', 'No. There is no sign-up and no login. Your answers, marks, saved lessons and course choices stay in your own browser and are never sent to us.'],
                ['Which exam boards does it cover?', 'AQA, Edexcel and OCR, for GCSE. Choose your board once and lessons for other boards are hidden.'],
                ['Are these real past papers?', 'No. Questions and mock exams are written by OpenRevise in the style of each board. For real past papers, see your exam board\'s website.'],
                ['I found a mistake. What do I do?', 'Every lesson has a "Found a mistake?" link. It takes ten seconds and you do not need to give your name.'],
            ] as [$question, $answer])
                <details class="group px-5 py-4">
                    <summary class="cursor-pointer list-none flex items-center justify-between gap-4 font-semibold text-ink">
                        {{ $question }}
                        <x-glyph name="chevron-down" class="!w-4 !h-4 text-muted transition-transform group-open:rotate-180" />
                    </summary>
                    <p class="mt-2 text-sm text-body">{{ $answer }}</p>
                </details>
            @endforeach
        </div>
    </section>
</x-layouts.public>
