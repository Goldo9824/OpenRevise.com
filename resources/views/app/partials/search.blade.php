<div class="search hidden sm:block" x-data="siteSearch('{{ main_url('/_search') }}', '{{ main_url('/search/') }}')" @click.outside="open = false" @keydown.escape="open = false">
    <x-glyph name="search" />
    <form action="{{ main_url('/search/') }}" method="get" role="search" @submit="go($event)">
        <label for="site-search" class="sr-only">Search</label>
        <input id="site-search" type="search" name="q" autocomplete="off" placeholder="Search for a topic, subject or lesson..."
               x-model="q" @input.debounce.200ms="find()" @focus="open = results.length > 0"
               @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)"
               role="combobox" :aria-expanded="open" aria-controls="search-results" aria-autocomplete="list">
    </form>
    <div id="search-results" class="search-results" x-cloak x-show="open" role="listbox">
        <template x-for="(r, i) in results" :key="r.url">
            <a :href="r.url" :class="i === active && 'is-active'" role="option">
                <span class="flex items-center justify-between gap-3">
                    <span class="font-medium" x-text="r.title"></span>
                    <span class="chip shrink-0" x-text="r.type"></span>
                </span>
                <span class="block text-xs text-muted mt-0.5" x-text="r.context"></span>
            </a>
        </template>
        <p x-show="!results.length && q.length > 1 && !loading" class="px-3 py-2 text-sm text-muted">Nothing found for &ldquo;<span x-text="q"></span>&rdquo;.</p>
    </div>
</div>
<a href="{{ main_url('/search/') }}" class="icon-btn sm:hidden" aria-label="Search"><x-glyph name="search" /></a>
