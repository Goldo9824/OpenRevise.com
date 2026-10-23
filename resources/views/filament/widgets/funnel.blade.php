<x-filament-widgets::widget>
    <x-filament::section heading="From home to exam questions" :description="$range . ' · visitors, same day'">
        <ul class="or-funnel">
            @foreach ($steps as [$label, $count])
                <li>
                    <div class="row">
                        <span>{{ $label }}</span>
                        <strong>{{ number_format($count) }}@if (! $loop->first && $steps[$loop->index - 1][1]) <span class="or-muted">({{ round($count / $steps[$loop->index - 1][1] * 100) }}%)</span>@endif</strong>
                    </div>
                    <div class="track"><div class="fill" style="width: {{ round($count / $max * 100) }}%"></div></div>
                </li>
            @endforeach
        </ul>
    </x-filament::section>
</x-filament-widgets::widget>
