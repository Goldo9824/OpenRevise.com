<x-layouts.public page="landing" :title="$seo['title']" :description="$seo['description']" :canonical="$seo['canonical']" :json-ld="$seo['jsonLd']">
    {{-- Hero --}}
    <section class="hero overflow-hidden">
        <div class="max-w-7xl mx-auto px-5 lg:px-8 pt-14 pb-16 md:pt-20 md:pb-24 grid gap-14 lg:grid-cols-[1fr_1.1fr] lg:items-center">
            <div>
                <p class="eyebrow-wide">Free revision resources for every GCSE student</p>
                <h1 class="hero-title mt-6"><span class="xl:whitespace-nowrap">Revise smarter.</span><br><span class="text-brand">Go further.</span></h1>
                <p class="mt-7 text-lg md:text-xl text-body max-w-xl leading-relaxed">OpenRevise gives you free, high-quality GCSE revision notes, exam questions and mock exams for AQA, Edexcel and OCR. No account, nothing to pay.</p>
                <div class="mt-9 flex flex-wrap gap-3">
                    <a href="{{ app_url('/') }}" class="btn !h-14 !px-8 !text-base !rounded-xl">Start revising &mdash; it&rsquo;s free</a>
                    <a href="{{ app_url('/gcse/') }}" class="btn btn-outline !h-14 !px-8 !text-base !rounded-xl !border-2">Explore subjects</a>
                </div>

                <ul class="mt-12 grid gap-6 sm:grid-cols-3 max-w-2xl">
                    @foreach ([
                        ['book-open', 'Clear, concise notes', 'Written for your exam board'],
                        ['check-circle', 'Exam questions', 'With mark schemes to check'],
                        ['clock', 'Timed mock exams', 'The real time and marks'],
                    ] as [$icon, $title, $text])
                        <li class="flex items-start gap-3">
                            <x-glyph :name="$icon" class="!w-8 !h-8 text-brand" stroke-width="1.6" />
                            <span class="text-sm leading-snug"><span class="block font-medium text-ink">{{ $title }}</span><span class="text-muted">{{ $text }}</span></span>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- A preview of the app, with a note in the margin. --}}
            <div class="relative lg:pl-4 xl:pr-28" aria-hidden="true">
                <svg class="absolute -z-0 -left-10 -top-10 w-[115%] h-[120%] text-brand-100" viewBox="0 0 600 520" preserveAspectRatio="none"><path fill="currentColor" d="M95 120C160 30 330 -10 450 40s160 160 130 260-120 190-250 210S60 470 25 360 30 210 95 120Z" opacity=".7"/></svg>
                <svg class="absolute -left-6 -top-8 w-10 h-10 text-brand" viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M6 22 1 20M14 12l-3-6M25 9l1-7"/></svg>

                <div class="mock-app relative card shadow-[0_30px_70px_-30px_rgba(17,24,39,.35)] grid grid-cols-[10rem_1fr] overflow-hidden">
                    <div class="mock-side bg-panel border-r border-line p-3 flex flex-col">
                        <span class="logo !text-[1.05rem] px-1.5 pb-3"><span class="logo-open">open</span><span class="logo-revise">revise</span></span>
                        <a class="is-active"><x-glyph name="home" /> Home</a>
                        <a><x-glyph name="book-open" /> Subjects</a>
                        <a><x-glyph name="help-circle" /> Exam questions</a>
                        <a><x-glyph name="clipboard-list" /> Mock exams</a>
                        <a><x-glyph name="bookmark" /> Saved</a>
                        <a class="mt-auto pt-10"><x-glyph name="sliders" /> Your course</a>
                    </div>
                    <div class="p-4 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <p class="flex items-center gap-2 text-[.95rem] font-semibold text-ink"><x-glyph name="leaf" class="!w-5 !h-5 text-brand" /> Good luck, keep going!</p>
                            <span class="chip">AQA</span>
                        </div>
                        <div class="mt-3 h-8 rounded-lg border border-line bg-white flex items-center justify-between px-3 text-faint">Search for a topic, subject or lesson... <x-glyph name="search" /></div>

                        <div class="mt-4 flex justify-between items-baseline"><p class="font-semibold text-ink text-[.8rem]">Pick up where you left off</p><span class="text-brand text-[.68rem]">See all &rarr;</span></div>
                        <div class="mt-2 grid grid-cols-3 gap-2">
                            @foreach ([['leaf', '#0f6b56', 'Cell structure', 'Biology', 83], ['sigma', '#2563eb', 'Quadratic equations', 'Maths', 50], ['flask', '#c2620f', 'Ionic bonding', 'Chemistry', 25]] as [$icon, $colour, $title, $subject, $score])
                                <div class="rounded-lg border border-line p-2.5">
                                    <span class="subject-tile !w-7 !h-7 !rounded-md" style="--tile: {{ $colour }}"><x-glyph :name="$icon" /></span>
                                    <p class="mt-2 font-semibold text-ink leading-tight truncate">{{ $title }}</p>
                                    <p class="text-muted text-[.65rem]">{{ $subject }}</p>
                                    <div class="mt-2 flex items-center gap-1.5"><span class="progress flex-1 !h-1"><span style="width: {{ $score }}%; background: {{ $colour }}"></span></span><span class="text-[.6rem] text-muted">{{ $score }}%</span></div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4 flex justify-between items-baseline"><p class="font-semibold text-ink text-[.8rem]">Subjects</p><span class="text-brand text-[.68rem]">See all &rarr;</span></div>
                        <div class="mt-2 grid grid-cols-6 gap-1.5">
                            @foreach ([['leaf', '#0f6b56', 'Biology'], ['flask', '#2563eb', 'Chemistry'], ['atom', '#7c3aed', 'Physics'], ['sigma', '#0f766e', 'Maths'], ['briefcase', '#c2620f', 'Business'], ['globe', '#8a6d12', 'Geography']] as [$icon, $colour, $name])
                                <div class="rounded-lg border border-line py-2 text-center">
                                    <span class="subject-tile !w-7 !h-7 !rounded-md mx-auto" style="--tile: {{ $colour }}"><x-glyph :name="$icon" /></span>
                                    <p class="mt-1 text-[.58rem] text-body truncate px-0.5">{{ $name }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="hidden xl:block absolute right-0 top-[36%] w-24">
                    <svg class="w-10 h-10 text-[#3d5a73] -ml-2" viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M32 36C30 22 22 12 6 6"/><path d="M14 4 6 6l3 8"/></svg>
                    <p class="hand text-[1.35rem] mt-1">Everything you need to revise. In one place.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- What you can count on --}}
    <section class="border-y border-line bg-white">
        <div class="max-w-7xl mx-auto px-5 lg:px-8 py-10 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['heart', '100% free', 'now and always'],
                ['lock-open', 'No account needed', 'nothing to sign up for'],
                ['graduation-cap', 'AQA, Edexcel and OCR', 'notes for your exam board'],
                ['shield', 'No cookies', 'private by design'],
            ] as [$icon, $title, $text])
                <div class="flex items-center gap-4">
                    <x-glyph :name="$icon" class="!w-9 !h-9 text-ink" stroke-width="1.6" />
                    <p class="leading-snug"><span class="block font-semibold text-ink">{{ $title }}</span><span class="text-muted">{{ $text }}</span></p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Study tools --}}
    <section id="tools" class="bg-panel">
        <div class="max-w-7xl mx-auto px-5 lg:px-8 py-16 md:py-24">
            <div class="text-center max-w-3xl mx-auto">
                <p class="eyebrow-wide">Study tools that work for you</p>
                <h2 class="mt-4 text-3xl md:text-[2.6rem] font-extrabold tracking-[-0.035em] leading-tight">Everything you need to revise, all in one place.</h2>
                <p class="mt-3 text-lg text-body">From notes to exam questions to mock papers, OpenRevise helps you revise with confidence.</p>
            </div>
            <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['file-text', '#7c3aed', 'Revision notes', 'One idea per lesson, written for your board\'s specification. Key terms first, then labelled diagrams, tables and formulas.', app_url('/gcse/')],
                    ['check-circle', '#15803d', 'Exam questions', 'Questions in your board\'s style after every lesson. Write your answer, open the mark scheme and the model answer, and mark yourself.', app_url('/gcse/')],
                    ['clipboard-list', '#c2620f', 'Mock exams', 'Full papers with the real time and marks for your board. Sit one against the clock on screen, then see your total.', app_url('/gcse/')],
                    ['bookmark', '#0f766e', 'Save and come back', 'Bookmark lessons, pick up where you left off and keep your answers. All stored on your own device, no account.', app_url('/saved/')],
                ] as [$icon, $colour, $title, $text, $url])
                    <a href="{{ $url }}" class="card p-6 no-underline text-body hover:border-brand-200 hover:shadow-[0_12px_30px_-18px_rgba(15,107,86,.5)] transition-shadow">
                        <div class="flex items-center gap-4">
                            <span class="tool-tile" style="background: color-mix(in srgb, {{ $colour }} 12%, white); color: {{ $colour }}"><x-glyph :name="$icon" /></span>
                            <h3 class="text-lg font-semibold">{{ $title }}</h3>
                        </div>
                        <p class="mt-4 text-sm leading-relaxed">{{ $text }}</p>
                        <span class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-brand">Open <x-glyph name="arrow-right" class="!w-4 !h-4" /></span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Subjects and boards --}}
    <section class="max-w-7xl mx-auto px-5 lg:px-8 py-16 md:py-24 grid gap-12 lg:grid-cols-[1fr_1.35fr] lg:items-start">
        <div>
            <p class="eyebrow-wide">Your course</p>
            <h2 class="mt-4 text-3xl md:text-[2.4rem] font-extrabold tracking-[-0.035em] leading-tight">Pick your board, then your subject.</h2>
            <p class="mt-4 text-body text-lg">When the boards ask for the same thing there is one lesson for all of them; when they differ, each board gets its own. Not sure which board you are on? Your teacher will know, and it is on the front of your past papers.</p>
            <div class="mt-7 flex flex-wrap gap-3">
                @forelse ($boards as $board)
                    <a href="{{ app_url('/') }}?board={{ $board->slug }}" class="btn btn-outline !h-12 !px-6 !rounded-xl !text-base">{{ $board->name }}</a>
                @empty
                    <a href="{{ app_url('/') }}" class="btn">See all subjects</a>
                @endforelse
            </div>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach ($subjects as $subject)
                <a href="{{ app_url($subject->path()) }}" class="subject-card !items-center" style="--tile: {{ $subject->coverColour() }}">
                    <span class="subject-tile"><x-glyph :name="$subject->iconName()" /></span>
                    <span class="flex-1 font-semibold text-ink">GCSE {{ $subject->name }}</span>
                    <x-glyph name="chevron-right" class="!w-4 !h-4 text-faint" />
                </a>
            @endforeach
        </div>
    </section>

    {{-- Questions --}}
    <section class="border-t border-line bg-panel">
        <div class="max-w-4xl mx-auto px-5 lg:px-8 py-16 md:py-24">
            <div class="text-center">
                <p class="eyebrow-wide">Questions</p>
                <h2 class="mt-4 text-3xl md:text-[2.4rem] font-extrabold tracking-[-0.035em]">Before you start</h2>
            </div>
            <div class="card mt-10 divide-y divide-line">
                @foreach ([
                    ['Is it really free?', 'Yes. Every note, question and mock exam is free. There is nothing to unlock and no premium version.'],
                    ['Do I need an account?', 'No. There is no sign-up and no login. Your answers, marks, saved lessons and course choices stay in your own browser and are never sent to us.'],
                    ['Which exam boards does it cover?', 'AQA, Edexcel and OCR, for GCSE. Choose your board once and lessons for other boards are hidden.'],
                    ['Are these real past papers?', 'No. Questions and mock exams are written by OpenRevise in the style of each board. For real past papers, see your exam board\'s website.'],
                    ['I found a mistake. What do I do?', 'Every lesson has a "Found a mistake?" link. It takes ten seconds and you do not need to give your name.'],
                ] as [$question, $answer])
                    <details class="group px-6 py-5">
                        <summary class="cursor-pointer list-none flex items-center justify-between gap-4 font-semibold text-ink">
                            {{ $question }}
                            <x-glyph name="chevron-down" class="!w-5 !h-5 text-muted transition-transform group-open:rotate-180" />
                        </summary>
                        <p class="mt-3 text-body">{{ $answer }}</p>
                    </details>
                @endforeach
            </div>
            <div class="mt-12 text-center">
                <a href="{{ app_url('/') }}" class="btn !h-14 !px-8 !text-base !rounded-xl">Start revising &mdash; it&rsquo;s free</a>
            </div>
        </div>
    </section>
</x-layouts.public>
