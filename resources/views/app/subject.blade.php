<x-layouts.app page="subject" :subject="$subject" active="subject" :title="$seo['title']" :description="$seo['description']" :canonical="$seo['canonical']" :json-ld="$seo['jsonLd']">
    <div class="max-w-4xl">
        <x-crumbs :items="[['Subjects', main_url('/gcse/')], [$subject->fullName(), null]]" />

        <div class="mt-4 flex items-center gap-4">
            <span class="subject-tile" style="--tile: {{ $subject->coverColour() }}"><x-glyph :name="$subject->iconName()" /></span>
            <h1 class="page-title">{{ $subject->fullName() }}</h1>
        </div>
        <p class="lead mt-3">{{ $subject->description ?: 'Revision notes for every topic, with exam questions after each lesson and mock exams for your board.' }}</p>

        <div class="mt-5 flex flex-wrap items-center gap-2">
            @foreach ($subject->boards as $b)
                <a href="{{ main_url($subject->path().$b->slug.'/') }}" class="btn btn-quiet btn-sm" :class="$store.prefs.board === '{{ $b->slug }}' && '!border-brand !text-brand-dark !bg-brand-50'">
                    {{ $b->name }} @if ($b->pivot->spec_code)<span class="text-faint font-normal">{{ $b->pivot->spec_code }}</span>@endif
                </a>
            @endforeach
            @if ($outline->mockExams)
                <a href="{{ main_url($subject->path().'mock-exams/') }}" class="btn btn-outline btn-sm"><x-glyph name="clipboard-list" /> Mock exams ({{ $outline->mockExams }})</a>
            @endif
        </div>

        <div class="mt-7 space-y-4">
            @forelse ($outline->topics as $topic)
                @include('app.partials.topic-card', ['topic' => $topic, 'outline' => $outline])
            @empty
                <p class="text-muted">The first {{ $subject->name }} lessons are being written. Come back soon.</p>
            @endforelse
        </div>
    </div>
</x-layouts.app>
