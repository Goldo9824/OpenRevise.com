<x-layouts.app page="saved" active="saved" :title="$seo['title']" :description="$seo['description']" :canonical="$seo['canonical']" :robots="$seo['robots']">
    <div class="max-w-3xl">
        <h1 class="page-title">Saved lessons</h1>
        <p class="lead mt-2">Lessons you saved with the <x-glyph name="bookmark" class="inline !w-4 !h-4 align-[-2px]" /> button. They are kept in this browser, on this device only.</p>

        <div class="topic-card mt-6" x-show="$store.saved.items.length">
            <template x-for="item in $store.saved.items" :key="item.url">
                <div class="lesson-row">
                    <span class="rail-icon"><x-glyph name="file-text" /></span>
                    <a :href="item.url" class="flex-1 min-w-0 no-underline">
                        <span class="lesson-title block truncate" x-text="item.title"></span>
                        <span class="block text-xs text-muted" x-text="item.subject"></span>
                    </a>
                    <button type="button" class="btn btn-quiet btn-sm" @click="$store.saved.remove(item.url)">Remove</button>
                </div>
            </template>
        </div>

        <div class="mt-6 card p-8 text-center" x-show="!$store.saved.items.length">
            <span class="rail-icon mx-auto"><x-glyph name="bookmark" /></span>
            <p class="mt-3 font-semibold text-ink">Nothing saved yet</p>
            <p class="mt-1 text-sm text-muted">Open a lesson and press the bookmark next to its title to keep it here.</p>
            <a href="{{ main_url('/gcse/') }}" class="btn mt-5">Browse subjects</a>
        </div>
    </div>
</x-layouts.app>
