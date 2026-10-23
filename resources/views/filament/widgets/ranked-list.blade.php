<x-filament-widgets::widget>
    <x-filament::section :heading="$heading" :description="$range">
        @if ($rows->isEmpty())
            <p class="or-muted">No data for this period yet.</p>
        @else
            <div class="or-ranked-head">{{ $valueLabel }}</div>
            <ul class="or-ranked">
                @foreach ($rows as $row)
                    <li>
                        <span class="bar" style="width: {{ round($value($row) / $max * 100) }}%"></span>
                        <span class="label">
                            @if (! empty($row['url']))
                                <a href="{{ $row['url'] }}" target="_blank">{{ $row['label'] }}</a>
                            @else
                                {{ $row['label'] }}
                            @endif
                            @if (isset($row['avg_seconds']) && $row['avg_seconds'] !== null)
                                <span class="meta">&middot; {{ gmdate('i:s', $row['avg_seconds']) }} on page</span>
                            @endif
                        </span>
                        <span class="value">{{ number_format($value($row)) }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
