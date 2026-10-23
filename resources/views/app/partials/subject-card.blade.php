@php $boardSlugs = $subject->boards->pluck('slug')->join(','); $count = $count ?? 0; @endphp
<a href="{{ app_url($subject->path()) }}" class="subject-card" style="--tile: {{ $subject->coverColour() }}"
   data-boards="{{ $boardSlugs }}" x-show="!$store.prefs.board || !$el.dataset.boards || $el.dataset.boards.split(',').includes($store.prefs.board)"
   :href="$store.prefs.board && '{{ $boardSlugs }}'.split(',').includes($store.prefs.board) ? '{{ app_url($subject->path()) }}' + $store.prefs.board + '/' : '{{ app_url($subject->path()) }}'">
    <span class="subject-tile"><x-glyph :name="$subject->iconName()" /></span>
    <span class="min-w-0">
        <span class="block font-semibold text-ink">{{ $subject->name }}</span>
        <span class="block text-xs text-muted mt-0.5">{{ $count ? $count.' '.Str::plural('lesson', $count) : 'Coming soon' }} &middot; {{ $subject->boards->pluck('name')->join(', ') ?: 'All boards' }}</span>
        @if (! empty($topics) && $topics->isNotEmpty())
            <span class="block text-xs text-body mt-2 line-clamp-2">{{ $topics->pluck('title')->join(' · ') }}</span>
        @endif
    </span>
</a>
