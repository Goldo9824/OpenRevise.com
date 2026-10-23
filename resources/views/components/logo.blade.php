@props(['href' => '/'])
<a href="{{ $href }}" {{ $attributes->merge(['class' => 'logo']) }} aria-label="OpenRevise home"><span class="logo-open">open</span><span class="logo-revise">revise</span></a>
