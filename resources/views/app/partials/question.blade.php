{{--
    One exam question. Used on a lesson's exam questions page ($mode =
    'practice') and in mock exams ($mode = 'mock').
--}}
@php
    $q = $item['model'];
    $id = $q->id;
    $options = $q->options ?? [];
    $lines = max(2, min(14, $q->marks * 2));
    $practice = $mode === 'practice';
    $canReveal = $practice ? 'true' : "stage === 'marking'";
@endphp
<div class="question" id="q{{ $id }}">
    <div class="flex items-start gap-3.5">
        <span class="qnum shrink-0">{{ $number }}</span>
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <span class="flex flex-wrap items-center gap-1.5">
                    <span class="chip">{{ \App\Models\Content\QuestionType::tryFrom($q->type)?->label() ?? Str::headline($q->type) }}</span>
                    @if ($practice && $q->board)
                        <span class="chip chip-brand">{{ $q->board->name }} style</span>
                    @endif
                </span>
                <span class="marks">[{{ $q->marks }} {{ Str::plural('mark', $q->marks) }}]</span>
            </div>

            <div class="exam-text mt-3">{!! $item['question'] !!}</div>
        </div>
    </div>

    <div class="mt-4 sm:pl-[3.5rem]">
        @if ($options)
            <p class="text-xs font-medium text-muted mb-2">Choose one answer.</p>
            @foreach ($options as $i => $option)
                @php $isAnswer = $option === $q->answer; @endphp
                <button type="button" class="choice"
                        @click="choose({{ $id }}, @js($option))"
                        :aria-pressed="q({{ $id }}).choice === @js($option)"
                        @if ($practice)
                            :class="q({{ $id }}).choice !== null && ({{ $isAnswer ? 'true' : 'false' }} ? 'is-right' : (q({{ $id }}).choice === @js($option) ? 'is-wrong' : ''))"
                        @else
                            :class="stage === 'marking' && ({{ $isAnswer ? 'true' : 'false' }} ? 'is-right' : (q({{ $id }}).choice === @js($option) ? 'is-wrong' : ''))"
                        @endif
                >
                    <span class="letter">{{ chr(65 + $i) }}</span>
                    <span>{{ $option }}</span>
                </button>
            @endforeach
            @if ($practice)
                <p class="mt-2.5 text-sm font-medium flex items-center gap-1.5" x-cloak x-show="q({{ $id }}).choice !== null">
                    <span x-show="q({{ $id }}).choice === @js($q->answer)" class="text-ok inline-flex items-center gap-1.5"><x-icon name="check-circle" class="!w-4 !h-4" /> Correct</span>
                    <span x-show="q({{ $id }}).choice !== @js($q->answer)" class="text-danger inline-flex items-center gap-1.5"><x-icon name="x-circle" class="!w-4 !h-4" /> Not quite: the answer is {{ $q->answer }}</span>
                </p>
            @endif
        @else
            <label class="sr-only" for="a{{ $id }}">Your answer to question {{ $number }}</label>
            <textarea id="a{{ $id }}" class="answer-box" rows="{{ $lines }}" style="min-height: {{ $lines * 2 + .25 }}rem"
                      x-model="q({{ $id }}).answer" @input.debounce.400ms="save()"
                      @unless ($practice) :readonly="stage === 'marking'" @endunless
                      placeholder="Write your answer here"></textarea>
        @endif

        <div class="mt-3 flex flex-wrap items-center gap-2 no-print" @unless ($practice) x-show="stage === 'marking'" x-cloak @endunless>
            <button type="button" class="btn btn-outline btn-sm" @click="toggle({{ $id }}, 'scheme')" :aria-expanded="q({{ $id }}).scheme">
                <x-icon name="eye" /> <span x-text="q({{ $id }}).scheme ? 'Hide mark scheme' : 'Mark scheme'">Mark scheme</span>
            </button>
            <button type="button" class="btn btn-quiet btn-sm" @click="toggle({{ $id }}, 'model')" :aria-expanded="q({{ $id }}).model">
                <x-icon name="file-text" /> <span x-text="q({{ $id }}).model ? 'Hide model answer' : 'Model answer'">Model answer</span>
            </button>
        </div>

        <div class="mt-3 space-y-2.5" x-cloak x-show="{{ $canReveal }} && (q({{ $id }}).scheme || q({{ $id }}).model)">
            <div class="reveal exam-text" x-show="q({{ $id }}).scheme">
                <p class="reveal-title"><x-icon name="check-circle" /> Mark scheme</p>
                {!! $item['mark_scheme'] !!}
            </div>
            <div class="reveal model exam-text" x-show="q({{ $id }}).model">
                <p class="reveal-title"><x-icon name="pencil" /> Model answer</p>
                {!! $item['model_answer'] !!}
            </div>
        </div>

        @unless ($options)
            <div class="mt-3 flex flex-wrap items-center gap-3" x-cloak x-show="{{ $canReveal }} && (q({{ $id }}).scheme || q({{ $id }}).mark !== null)">
                <span class="text-sm font-medium text-ink">Your mark</span>
                <span class="self-mark" role="group" aria-label="Marks you give yourself for question {{ $number }}">
                    @for ($m = 0; $m <= $q->marks; $m++)
                        <button type="button" @click="setMark({{ $id }}, {{ $m }})" :aria-pressed="q({{ $id }}).mark === {{ $m }}">{{ $m }}</button>
                    @endfor
                </span>
            </div>
        @endunless
    </div>
</div>
