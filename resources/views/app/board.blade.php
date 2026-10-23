<x-layouts.app page="board" :subject="$subject" :board="$board->slug" active="subject" :title="$seo['title']" :description="$seo['description']" :canonical="$seo['canonical']" :json-ld="$seo['jsonLd']">
    <div class="with-rail">
        <div class="min-w-0">
            <x-crumbs :items="[['Subjects', app_url('/gcse/')], [$subject->fullName(), app_url($subject->path())], [$board->name, null]]" />
            <h1 class="page-title mt-4">{{ $board->name }} {{ $subject->fullName() }}</h1>
            <p class="lead mt-2">Every lesson on the {{ $board->full_name ?: $board->name }} course, in order.@if ($specCode) Specification {{ $specCode }}.@endif</p>
            <div class="mt-4" x-show="$store.prefs.board !== '{{ $board->slug }}'">
                <button type="button" class="btn btn-outline btn-sm" @click="$store.prefs.set('board', '{{ $board->slug }}')"><x-icon name="check" /> {{ $board->name }} is my exam board</button>
            </div>

            <div class="mt-7 space-y-4">
                @forelse ($topics as $topic)
                    @include('app.partials.topic-card', ['topic' => $topic, 'outline' => $outline, 'onlyBoard' => $board->id])
                @empty
                    <p class="text-muted">No {{ $board->name }} lessons yet for {{ $subject->name }}.</p>
                @endforelse
            </div>
        </div>

        <aside class="rail" aria-label="{{ $board->name }} mock exams">
            <div class="rail-inner">
                <div class="rail-card">
                    <p class="rail-title">{{ $board->name }} mock exams</p>
                    @forelse ($mocks as $mock)
                        @php $mock->setRelation('subject', $subject); @endphp
                        <a href="{{ app_url($mock->path()) }}" class="rail-link">
                            <span class="rail-icon"><x-icon name="clipboard-list" /></span>
                            <span><span class="rail-link-title">{{ $mock->title }}</span><span class="rail-link-meta">{{ $mock->paperTemplate->durationLabel() }} &middot; {{ $mock->paperTemplate->total_marks }} marks</span></span>
                        </a>
                    @empty
                        <p class="text-xs text-muted">None yet.</p>
                    @endforelse
                </div>
            </div>
        </aside>
    </div>
</x-layouts.app>
