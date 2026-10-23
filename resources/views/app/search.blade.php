<x-layouts.app page="search" :title="$seo['title']" :description="$seo['description']" :canonical="$seo['canonical']" :robots="$seo['robots']">
    <div class="max-w-3xl">
        <h1 class="page-title">Search</h1>
        <form action="{{ main_url('/search/') }}" method="get" role="search" class="search !max-w-none mt-4">
            <x-glyph name="search" />
            <label for="search-page" class="sr-only">Search for a topic, subject or lesson</label>
            <input id="search-page" type="search" name="q" value="{{ $q }}" placeholder="Search for a topic, subject or lesson..." autofocus>
        </form>

        @if ($q !== '')
            <p class="mt-5 text-sm text-muted">{{ $results->count() }} {{ Str::plural('result', $results->count()) }} for &ldquo;{{ $q }}&rdquo;</p>
            @if ($results->isNotEmpty())
                <div class="topic-card mt-3">
                    @foreach ($results as $r)
                        <a href="{{ $r['url'] }}" class="lesson-row">
                            <span class="flex-1 min-w-0">
                                <span class="lesson-title">{{ $r['title'] }}</span>
                                <span class="block text-xs text-muted mt-0.5">{{ $r['context'] }}</span>
                                @if (! empty($r['summary']))
                                    <span class="block text-xs text-body mt-1">{{ $r['summary'] }}</span>
                                @endif
                            </span>
                            <span class="chip">{{ $r['type'] }}</span>
                        </a>
                    @endforeach
                </div>
            @else
                <p class="mt-3 text-body">Try a shorter word, or <a href="{{ main_url('/gcse/') }}">browse the subjects</a>.</p>
            @endif
        @endif
    </div>
</x-layouts.app>
