{{-- Notes | Exam questions | Mock exams, under a lesson's title. --}}
<nav class="tabs mt-5" aria-label="Lesson sections">
    <a href="{{ app_url($lesson->path()) }}" @class(['tab', 'is-active' => $current === 'notes']) @if ($current === 'notes') aria-current="page" @endif>
        <x-icon name="file-text" /> Notes
    </a>
    @if ($questionCount)
        <a href="{{ app_url($lesson->questionsPath()) }}" @class(['tab', 'is-active' => $current === 'questions']) @if ($current === 'questions') aria-current="page" @endif>
            <x-icon name="help-circle" /> Exam questions <span class="tab-count">{{ $questionCount }}</span>
        </a>
    @endif
    @if ($outline->mockExams)
        <a href="{{ app_url($subject->path().'mock-exams/') }}" class="tab">
            <x-icon name="clipboard-list" /> Mock exams <span class="tab-count">{{ $outline->mockExams }}</span>
        </a>
    @endif
</nav>
