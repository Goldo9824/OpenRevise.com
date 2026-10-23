@props(['lesson', 'boards' => true])
<span {{ $attributes->merge(['class' => 'inline-flex flex-wrap gap-1.5 align-middle']) }}>
    @if ($lesson->tier->value === 'higher')
        <span class="badge badge-red" title="Only for Higher tier students">Higher only</span>
    @endif
    @if ($lesson->course->value === 'separate')
        <span class="badge badge-red" title="Only for Separate Science (triple) students">Separate only</span>
    @endif
    @if ($boards)
        @foreach ($lesson->boards as $b)
            <span class="badge badge-quiet">{{ $b->name }}</span>
        @endforeach
    @endif
</span>
