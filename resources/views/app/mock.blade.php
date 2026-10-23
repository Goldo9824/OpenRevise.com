@php
    $payload = $questions->map(fn ($i) => [
        'id' => $i['model']->id,
        'marks' => $i['model']->marks,
        'answer' => $i['model']->options ? $i['model']->answer : null,
    ])->values();

    // The examiner's grid: one row per main question (01, 02...).
    $grid = $questions->groupBy(fn ($i) => explode('.', $i['number'])[0])->map(fn ($group, $label) => [
        'label' => $label,
        'marks' => $group->sum(fn ($i) => $i['model']->marks),
        'ids' => $group->map(fn ($i) => $i['model']->id)->values()->all(),
    ])->values();
@endphp
<x-layouts.app page="mock" :subject="$subject" :board="$exam->board->slug" active="mocks" :title="$seo['title']" :description="$seo['description']" :canonical="$seo['canonical']" :robots="$seo['robots']" :json-ld="$seo['jsonLd']">
    <div x-data="mockExam('mock-{{ $exam->id }}', {{ $template->duration_minutes }}, @js($payload))" class="max-w-4xl">

        {{-- Follows you down the paper while you sit it. --}}
        <div class="timer-bar -mx-5 md:-mx-9 -mt-6 md:-mt-7 mb-6" x-cloak x-show="stage !== 'cover'">
            <div class="px-5 md:px-9 h-14 flex items-center justify-between gap-4">
                <span class="font-semibold truncate text-sm">{{ $exam->title }}</span>
                <span class="flex items-center gap-3">
                    <template x-if="stage === 'sitting'">
                        <span class="timer inline-flex items-center gap-2" :class="timed && remaining < 300 && 'text-amber-300'"><x-glyph name="clock" class="!w-4 !h-4" /><span x-text="timed ? clock(remaining) + ' left' : clock(elapsed)"></span></span>
                    </template>
                    <template x-if="stage === 'sitting'">
                        <button type="button" class="btn btn-sm !bg-white !text-brand-dark !border-white" @click="finish()">Finish</button>
                    </template>
                    <template x-if="stage === 'marking'">
                        <span class="timer"><span x-text="scored"></span> / <span x-text="total"></span></span>
                    </template>
                </span>
            </div>
        </div>

        <div x-show="stage === 'cover'">
            <x-crumbs :items="[[$subject->name, main_url($subject->path())], ['Mock exams', main_url($subject->path().'mock-exams/')], [$exam->title, null]]" />
        </div>

        {{-- The front cover. --}}
        <section class="card mt-4 overflow-hidden" x-show="stage !== 'sitting'">
            <div class="px-6 md:px-8 py-6 bg-panel border-b border-line flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="section-label">{{ $exam->board->name }} &middot; GCSE {{ $subject->name }}</p>
                    <h1 class="page-title mt-1">{{ $exam->title }}</h1>
                    <p class="mt-1 text-sm text-muted">{{ $template->name }}@if ($template->tierLabel()) &middot; {{ $template->tierLabel() }}@endif @if ($exam->topic) &middot; Topic: {{ $exam->topic->title }}@endif</p>
                </div>
                <div class="flex gap-6 text-sm">
                    <div><p class="text-muted text-xs">Time allowed</p><p class="font-semibold text-ink">{{ $template->durationLabel() }}</p></div>
                    <div><p class="text-muted text-xs">Total marks</p><p class="font-semibold text-ink">{{ $template->total_marks }}</p></div>
                </div>
            </div>

            <div class="px-6 md:px-8 py-6 grid gap-8 md:grid-cols-[1fr_auto]">
                <div class="space-y-5 text-sm">
                    <div>
                        <h2 class="text-sm">Instructions</h2>
                        <ul class="mt-1.5 list-disc pl-5 space-y-1 marker:text-faint">
                            @forelse ($template->instructionLines() as $line)
                                <li>{{ $line }}</li>
                            @empty
                                <li>Answer all questions in the spaces provided.</li>
                                <li>Show your working in calculations.</li>
                            @endforelse
                            <li>Your answers are saved on this device as you type.</li>
                        </ul>
                    </div>
                    <div>
                        <h2 class="text-sm">Information</h2>
                        <ul class="mt-1.5 list-disc pl-5 space-y-1 marker:text-faint">
                            <li>The maximum mark for this paper is {{ $template->total_marks }}.</li>
                            <li>The marks for each question are shown in brackets.</li>
                            @foreach ($template->informationLines() as $line)
                                <li>{{ $line }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @if ($exam->description)
                        <p class="text-muted">{{ $exam->description }}</p>
                    @endif
                </div>

                <table class="examiner-table self-start">
                    <caption>Your marks</caption>
                    <thead><tr><th>Question</th><th>Mark</th></tr></thead>
                    <tbody>
                        @foreach ($grid as $row)
                            <tr>
                                <td>{{ $row['label'] }}</td>
                                <td class="got"><span x-show="stage === 'marking'" x-text="groupScore(@js($row['ids']))"></span><span class="text-faint font-normal" x-show="stage !== 'marking'">/{{ $row['marks'] }}</span></td>
                            </tr>
                        @endforeach
                        <tr><th>Total</th><td class="got"><span x-show="stage === 'marking'" x-text="scored"></span><span class="text-faint font-normal" x-show="stage !== 'marking'">/{{ $template->total_marks }}</span></td></tr>
                    </tbody>
                </table>
            </div>

            <div class="px-6 md:px-8 pb-7 flex flex-wrap gap-3" x-show="stage === 'cover'">
                <button type="button" class="btn btn-lg" @click="start(true)"><x-glyph name="clock" /> Start timed ({{ $template->durationLabel() }})</button>
                <button type="button" class="btn btn-outline btn-lg" @click="start(false)">Start without a timer</button>
            </div>

            <div class="mx-6 md:mx-8 mb-7 rounded-xl bg-brand-50 border border-brand-100 p-5" x-cloak x-show="stage === 'marking'">
                <p class="section-label">Your result</p>
                <p class="mt-1 text-3xl font-bold text-ink"><span class="score" x-text="scored + ' / ' + total"></span> <span class="text-lg text-muted font-semibold" x-text="'(' + percent + '%)'"></span></p>
                <div class="progress mt-3 max-w-sm bg-white"><span :style="'width:' + percent + '%'"></span></div>
                <p class="mt-3 text-sm text-body">Multiple-choice questions are marked for you. For the rest, open each mark scheme below and give yourself the marks you earned.</p>
                <button type="button" class="btn btn-quiet btn-sm mt-4" @click="restart()"><x-glyph name="rotate" /> Sit it again</button>
            </div>
        </section>

        {{-- The questions. --}}
        <section class="mt-6 space-y-4" x-cloak x-show="stage !== 'cover'">
            @foreach ($sections as $sectionName => $items)
                @if ($sectionName !== '')
                    <h2 class="text-lg pt-3">{{ $sectionName }}</h2>
                @endif
                @foreach ($items as $item)
                    @include('app.partials.question', ['item' => $item, 'number' => $item['number'], 'mode' => 'mock'])
                @endforeach
            @endforeach

            <div class="pt-4 text-center" x-show="stage === 'sitting'">
                <p class="text-sm text-muted">End of questions</p>
                <button type="button" class="btn btn-lg mt-3" @click="finish()">Finish and mark my paper</button>
            </div>
        </section>
    </div>
</x-layouts.app>
