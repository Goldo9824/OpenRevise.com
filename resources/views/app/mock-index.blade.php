<x-layouts.public site="app" page="mock-index" :subject="$subject->slug" :title="$seo['title']" :description="$seo['description']" :canonical="$seo['canonical']" :json-ld="$seo['jsonLd']">
    <div class="wrap pt-6">
        <x-crumbs :items="[['Subjects', app_url('/')], [$subject->fullName(), app_url($subject->path())], ['Mock exams', null]]" />
    </div>

    <header class="wrap pt-6 pb-10">
        <p class="eyebrow">{{ $subject->fullName() }}</p>
        <h1 class="mt-2 text-[2.2rem] md:text-[3rem] leading-none">Mock exams</h1>
        <p class="mt-4 max-w-2xl text-lg text-ink-2">Full papers written by OpenRevise, with the time and marks of the real thing. Sit one on screen, timed or not, then mark it with the mark scheme.</p>
    </header>

    <div class="wrap pb-20 space-y-12 max-w-4xl">
        @forelse ($groups as $boardName => $exams)
            @php $boardSlug = $exams->first()->board->slug; @endphp
            <section data-boards="{{ $boardSlug }}" x-show="$store.prefs.shows($el.dataset)">
                <h2 class="text-[1.4rem] border-b-2 border-ink pb-2">{{ $boardName }}</h2>
                <ul class="index-list">
                    @foreach ($exams as $exam)
                        @php $exam->setRelation('subject', $subject); @endphp
                        <li data-tier="{{ $exam->paperTemplate->tier === 'higher' ? 'higher' : 'all' }}" x-show="$store.prefs.shows($el.dataset)">
                            <a href="{{ app_url($exam->path()) }}" class="index-row">
                                <span class="index-num">{{ $loop->iteration }}</span>
                                <span>
                                    <span class="index-title">{{ $exam->title }}</span>
                                    <span class="block text-sm text-ink-3 mt-1">
                                        {{ $exam->paperTemplate->name }}
                                        @if ($exam->paperTemplate->tierLabel()) &middot; {{ $exam->paperTemplate->tierLabel() }} @endif
                                        @if ($exam->topic) &middot; {{ $exam->topic->title }} @endif
                                    </span>
                                </span>
                                <span class="index-note">{{ $exam->paperTemplate->durationLabel() }} &middot; {{ $exam->paperTemplate->total_marks }} marks</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @empty
            <p class="text-ink-2 border-t-2 border-ink pt-6">No mock exams for {{ $subject->name }} yet.</p>
        @endforelse
    </div>
</x-layouts.public>
