<x-layouts.public site="main" page="landing" :title="$seo['title']" :description="$seo['description']" :canonical="$seo['canonical']" :json-ld="$seo['jsonLd']">
    {{-- Hero: what it is, and a real piece of it. --}}
    <section class="wrap pt-12 pb-16 md:pt-20 md:pb-24 grid gap-14 lg:grid-cols-[1.15fr_1fr] lg:items-center">
        <div>
            <p class="eyebrow">Free GCSE revision &middot; {{ $boards->pluck('name')->join(', ', ' and ') ?: 'AQA, Edexcel and OCR' }}</p>
            <h1 class="mt-4 text-[2.6rem] leading-[1.02] md:text-[4rem] tracking-[-0.03em]">
                Learn it. Answer the question. Check the <span class="hl">mark scheme</span>.
            </h1>
            <p class="mt-6 text-lg md:text-xl text-ink-2 max-w-xl">Short notes on every GCSE topic, exam questions after every lesson and timed mock exams, written for your exam board. No account, no sign-up, nothing to pay.</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ app_url('/') }}" class="btn">Start revising <span aria-hidden="true">&rarr;</span></a>
                <a href="#how" class="btn btn-ghost">How it works</a>
            </div>
        </div>

        {{-- A specimen question, marked. --}}
        <figure class="paper p-6 md:p-8 rotate-[.6deg] max-w-md w-full justify-self-center lg:justify-self-end" aria-label="An example exam question with its mark scheme">
            <div class="flex items-center gap-3">
                <x-qnum number="01.2" />
                <span class="eyebrow">Calculation</span>
            </div>
            <p class="mt-4">The image of a cell is 12 mm wide. The real cell is 0.03 mm wide.</p>
            <p class="mt-2 font-bold">Calculate the magnification.</p>
            <div class="mt-4 ruled pt-1 font-mono text-pen text-[1.05rem] leading-8 relative" aria-hidden="true">
                <p>12 &divide; 0.03</p>
                <p>= 400</p>
                <x-tick class="absolute right-16 top-0 w-7 h-7 hand-tick" />
                <x-tick class="absolute right-16 top-8 w-7 h-7 hand-tick" />
            </div>
            <p class="mt-1 text-right"><span class="marks">[2 marks]</span></p>
            <div class="reveal mt-4 text-[.95rem]">
                <p class="reveal-title">Mark scheme</p>
                <p>12 / 0.03 <span class="font-mono text-ink-3">(1)</span></p>
                <p>&times;400 <span class="font-mono text-ink-3">(1)</span></p>
            </div>
            <figcaption class="mt-4 flex items-center justify-between text-sm text-ink-3">
                <span>From <em>Microscopy and magnification</em></span>
                <span class="font-mono font-bold text-red text-base border-2 border-red rounded-full px-2.5 py-0.5 -rotate-6">2/2</span>
            </figcaption>
        </figure>
    </section>

    {{-- Contents page. --}}
    <section id="how" class="border-t border-rule bg-card">
        <div class="wrap py-16 md:py-20 grid gap-12 lg:grid-cols-[18rem_1fr]">
            <div>
                <p class="eyebrow">Contents</p>
                <h2 class="mt-3 text-3xl">Everything you need, in the order you need it</h2>
            </div>
            <ol class="space-y-10">
                <li>
                    <div class="leader text-xl md:text-2xl font-bold">
                        <span><span class="font-mono text-red mr-3">1</span>Revision notes</span>
                        <span class="font-mono text-base text-ink-3 font-semibold">every topic</span>
                    </div>
                    <p class="mt-2 text-ink-2 max-w-2xl md:pl-8">One idea per lesson, written for your board's specification. Key terms are highlighted, diagrams are labelled and formulas are written out. Lessons only on the Higher tier or Separate Science say so, and can be hidden.</p>
                </li>
                <li>
                    <div class="leader text-xl md:text-2xl font-bold">
                        <span><span class="font-mono text-red mr-3">2</span>Exam questions</span>
                        <span class="font-mono text-base text-ink-3 font-semibold">after every lesson</span>
                    </div>
                    <p class="mt-2 text-ink-2 max-w-2xl md:pl-8">Realistic questions in the style of your board, from one-mark recall to six-mark answers. Write your answer, open the mark scheme and the model answer, and give yourself the marks you earned.</p>
                </li>
                <li>
                    <div class="leader text-xl md:text-2xl font-bold">
                        <span><span class="font-mono text-red mr-3">3</span>Mock exams</span>
                        <span class="font-mono text-base text-ink-3 font-semibold">timed, on screen</span>
                    </div>
                    <p class="mt-2 text-ink-2 max-w-2xl md:pl-8">Full papers with the same time and marks as the real thing for your board. Sit one against the clock, then mark it question by question and see your total.</p>
                </li>
            </ol>
        </div>
    </section>

    {{-- Boards and subjects. --}}
    <section class="wrap py-16 md:py-20">
        <div class="grid gap-10 lg:grid-cols-[18rem_1fr]">
            <div>
                <p class="eyebrow">Your course</p>
                <h2 class="mt-3 text-3xl">Pick your board</h2>
                <p class="mt-3 text-ink-2">Not sure which one you are on? Your teacher will know, and it is printed on the front of your past papers.</p>
            </div>
            <div class="grid gap-5 sm:grid-cols-3">
                @forelse ($boards as $board)
                    <a href="{{ app_url('/') }}?board={{ $board->slug }}" class="group border-2 border-ink bg-card p-5 no-underline text-ink hover:bg-ink hover:text-paper transition-colors">
                        <span class="block text-3xl font-extrabold tracking-tight">{{ $board->name }}</span>
                        <span class="block mt-6 text-sm font-semibold">See {{ $board->name }} subjects &rarr;</span>
                    </a>
                @empty
                    <a href="{{ app_url('/') }}" class="btn">See all subjects</a>
                @endforelse
            </div>
        </div>
        @if ($subjects->isNotEmpty())
            <ul class="mt-12 flex flex-wrap gap-x-6 gap-y-2 text-lg lg:pl-[20.5rem]">
                @foreach ($subjects as $subject)
                    <li><a href="{{ app_url($subject->path()) }}">{{ $subject->name }}</a></li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- Questions people ask. --}}
    <section class="border-t border-rule">
        <div class="wrap py-16 md:py-20 grid gap-10 lg:grid-cols-[18rem_1fr]">
            <div>
                <p class="eyebrow">Questions</p>
                <h2 class="mt-3 text-3xl">Before you start</h2>
            </div>
            <div class="divide-y divide-rule border-y border-rule max-w-3xl">
                <details class="group py-4">
                    <summary class="cursor-pointer font-bold text-lg list-none flex justify-between gap-4">Is it really free?<span class="font-mono text-red group-open:rotate-45 transition-transform">+</span></summary>
                    <p class="mt-3 text-ink-2">Yes. Every note, question and mock exam is free to use. There is nothing to unlock and no premium version.</p>
                </details>
                <details class="group py-4">
                    <summary class="cursor-pointer font-bold text-lg list-none flex justify-between gap-4">Do I need an account?<span class="font-mono text-red group-open:rotate-45 transition-transform">+</span></summary>
                    <p class="mt-3 text-ink-2">No. There is no sign-up and no login. Your answers, marks and course choices are saved in your own browser, on your own device, and are never sent to us.</p>
                </details>
                <details class="group py-4">
                    <summary class="cursor-pointer font-bold text-lg list-none flex justify-between gap-4">Which exam boards does it cover?<span class="font-mono text-red group-open:rotate-45 transition-transform">+</span></summary>
                    <p class="mt-3 text-ink-2">AQA, Edexcel and OCR. When the boards ask for the same thing, there is one lesson for all of them; when they differ, each board gets its own version.</p>
                </details>
                <details class="group py-4">
                    <summary class="cursor-pointer font-bold text-lg list-none flex justify-between gap-4">Are these real past papers?<span class="font-mono text-red group-open:rotate-45 transition-transform">+</span></summary>
                    <p class="mt-3 text-ink-2">No. The questions and mock exams are written by OpenRevise in the style of each board. For real past papers, see your exam board's website.</p>
                </details>
                <details class="group py-4">
                    <summary class="cursor-pointer font-bold text-lg list-none flex justify-between gap-4">I found a mistake. What do I do?<span class="font-mono text-red group-open:rotate-45 transition-transform">+</span></summary>
                    <p class="mt-3 text-ink-2">Every lesson has a &ldquo;Spotted a mistake?&rdquo; link at the bottom. It takes ten seconds and you do not need to give your name. See <a href="{{ main_url('/contact/') }}">Contact</a> for other ways to get in touch.</p>
                </details>
            </div>
        </div>
    </section>
</x-layouts.public>
