@props(['lesson', 'boards' => true])
<span {{ $attributes->merge(['class' => 'inline-flex flex-wrap gap-1.5']) }}>
    @if ($lesson->tier->value === 'higher')
        <span class="chip chip-warn" title="Only for Higher tier students">Higher only</span>
    @endif
    @if ($lesson->course->value === 'separate')
        <span class="chip chip-warn" title="Only for Separate Science (triple) students">Separate only</span>
    @endif
    @if ($boards)
        @foreach ($lesson->boards as $b)
            <span class="chip">{{ $b->name }}</span>
        @endforeach
    @endif
</span>
