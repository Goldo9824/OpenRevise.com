@php
    $payload = $questions->map(fn ($i) => [
        'id' => $i['model']->id,
        'marks' => $i['model']->marks,
        'answer' => $i['model']->options ? $i['model']->answer : null,
    ])->values();

    // The "For Examiner's Use" grid: one row per main question (01, 02...).
    $grid = $questions->groupBy(fn ($i) => explode('.', $i['number'])[0])->map(fn ($group, $label) => [
        'label' => $label,
        'marks' => $group->sum(fn ($i) => $i['model']->marks),
        'ids' => $group->map(fn ($i) => $i['model']->id)->values()->all(),
    ])->values();

    $lines = fn ($text) => array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $text))));
@endphp
<x-layouts.public site="app" page="mock" :subject="$subject->slug" :board="$exam->board->slug" :title="$seo['title']" :description="$seo['description']" :canonical="$seo['canonical']" :robots="$seo['robots']" :json-ld="$seo['jsonLd']">
    <div x-data="mockExam('mock-{{ $exam->id }}', {{ $template->duration_minutes }}, @js($payload))">

        {{-- The bar that follows you down the paper. --}}
        <div class="timer-bar" x-cloak x-show="stage !== 'cover'">
            <div class="wrap h-14 flex items-center justify-between gap-4">
                <span class="font-bold truncate text-sm sm:text-base">{{ $exam->title }}</span>
                <span class="flex items-center gap-4">
                    <template x-if="stage === 'sitting'">
                        <span class="timer" :class="timed && remaining < 300 && 'text-[#ff8a7f]'" x-text="timed ? clock(remaining) + ' left' : clock(elapsed)" aria-live="off"></span>
                    </template>
                    <template x-if="stage === 'sitting'">
                        <button type="button" class="btn btn-small !bg-paper !text-ink !border-paper" @click="finish()">Finish</button>
                    </template>
                    <template x-if="stage === 'marking'">
                        <span class="timer"><span x-text="scored"></span> / <span x-text="total"></span></span>
                    </template>
                </span>
            </div>
        </div>

        <div class="wrap pt-6" x-show="stage === 'cover'">
            <x-crumbs :items="[['Subjects', app_url('/')], [$subject->fullName(), app_url($subject->path())], ['Mock exams', app_url($subject->path().'mock-exams/')], [$exam->title, null]]" />
        </div>

        {{-- Front cover, like the real paper. --}}
        <section class="wrap pt-8 pb-12" x-show="stage !== 'sitting'">
            <div class="paper max-w-3xl mx-auto">
                <div class="paper-head px-6 md:px-10 pt-8 pb-6 flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-[2.4rem] font-extrabold leading-none tracking-tight">{{ $exam->board->name }}</p>
                        <p class="eyebrow mt-2">Mock exam by OpenRevise</p>
                    </div>
                    <div class="text-right">
                        <p class="font-mono font-bold">{{ strtoupper(config('openrevise.level')) }}</p>
                        <p class="font-mono text-sm text-ink-3">{{ strtoupper($subject->name) }}</p>
                    </div>
                </div>

                <div class="px-6 md:px-10 py-8">
                    <h1 class="text-[1.9rem] md:text-[2.3rem] uppercase tracking-tight">{{ $subject->name }}</h1>
                    <p class="mt-1 text-xl font-bold">{{ $template->name }}@if ($template->tierLabel()) <span class="text-ink-2">{{ $template->tierLabel() }}</span>@endif</p>
                    @if ($exam->title !== trim($exam->board->name.' '.$subject->name.' '.$template->name))
                        <p class="mt-1 text-ink-2">{{ $exam->title }}</p>
                    @endif
                    @if ($exam->topic)
                        <p class="mt-1 text-ink-2">Topic: {{ $exam->topic->title }}</p>
                    @endif

                    <p class="mt-6 text-lg"><span class="font-bold">Time allowed:</span> {{ $template->durationLabel() }}</p>

                    <div class="mt-8 grid gap-8 md:grid-cols-[1fr_auto]">
                        <div class="space-y-6 text-[.98rem]">
                            <div>
                                <h2 class="font-bold text-base uppercase tracking-wide">Instructions</h2>
                                <ul class="mt-2 list-disc pl-5 space-y-1">
                                    @forelse ($template->instructionLines() as $line)
                                        <li>{{ $line }}</li>
                                    @empty
                                        <li>Answer all questions in the spaces provided.</li>
                                        <li>Show all your working in calculations.</li>
                                        <li>Your answers are saved on this device as you type.</li>
                                    @endforelse
                                </ul>
                            </div>
                            <div>
                                <h2 class="font-bold text-base uppercase tracking-wide">Information</h2>
                                <ul class="mt-2 list-disc pl-5 space-y-1">
                                    <li>The maximum mark for this paper is {{ $template->total_marks }}.</li>
                                    <li>The marks for questions are shown in brackets.</li>
                                    @foreach ($template->informationLines() as $line)
                                        <li>{{ $line }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            @if ($exam->description)
                                <p class="text-ink-2">{{ $exam->description }}</p>
                            @endif
                        </div>

                        <table class="examiner-table self-start">
                            <caption class="text-left font-bold text-xs uppercase tracking-wide pb-1.5">For examiner's use</caption>
                            <thead><tr><th>Question</th><th>Mark</th></tr></thead>
                            <tbody>
                                @foreach ($grid as $row)
                                    <tr>
                                        <td>{{ $row['label'] }}</td>
                                        <td class="got"><span x-show="stage === 'marking'" x-text="groupScore(@js($row['ids']))"></span><span class="text-ink-3 font-normal" x-show="stage !== 'marking'">/{{ $row['marks'] }}</span></td>
                                    </tr>
                                @endforeach
                                <tr><th>Total</th><td class="got"><span x-show="stage === 'marking'" x-text="scored"></span><span class="text-ink-3 font-normal" x-show="stage !== 'marking'">/{{ $template->total_marks }}</span></td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-10 flex flex-wrap gap-3" x-show="stage === 'cover'">
                        <button type="button" class="btn" @click="start(true)">Start, timed ({{ $template->durationLabel() }})</button>
                        <button type="button" class="btn btn-ghost" @click="start(false)">Start without a timer</button>
                    </div>

                    <div class="mt-10 border-t-2 border-ink pt-6" x-cloak x-show="stage === 'marking'">
                        <p class="eyebrow">Your result</p>
                        <p class="mt-1 text-[2rem] font-extrabold"><span class="score" x-text="scored + ' / ' + total"></span> <span class="text-ink-3 text-xl font-bold" x-text="'(' + percent + '%)'"></span></p>
                        <p class="mt-2 text-ink-2">Multiple-choice questions are marked for you. For the rest, open each mark scheme below and give yourself the marks you earned.</p>
                        <button type="button" class="btn btn-ghost btn-small mt-4" @click="restart()">Sit it again</button>
                    </div>
                </div>
            </div>
        </section>

        {{-- The questions. --}}
        <section class="wrap pb-20 max-w-3xl" x-cloak x-show="stage !== 'cover'">
            <div class="space-y-12 pt-8">
                @foreach ($sections as $sectionName => $items)
                    @if ($sectionName !== '')
                        <h2 class="text-xl uppercase tracking-wide border-b-2 border-ink pb-2 pt-4">{{ $sectionName }}</h2>
                    @endif
                    @foreach ($items as $item)
                        @include('app.partials.question', ['item' => $item, 'number' => $item['number'], 'mode' => 'mock'])
                    @endforeach
                @endforeach
            </div>

            <div class="mt-14 border-t-2 border-ink pt-6 text-center" x-show="stage === 'sitting'">
                <p class="font-bold uppercase tracking-wide">End of questions</p>
                <button type="button" class="btn mt-4" @click="finish()">Finish and mark my paper</button>
            </div>
        </section>
    </div>
</x-layouts.public>
