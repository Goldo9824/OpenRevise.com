@props(['items' => []])
<nav aria-label="Breadcrumb">
    <ol class="crumbs">
        @foreach ($items as [$label, $url])
            <li>
                @if ($url && ! $loop->last)
                    <a href="{{ $url }}">{{ $label }}</a>
                @else
                    <span @if ($loop->last) aria-current="page" class="text-body" @endif>{{ $label }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
