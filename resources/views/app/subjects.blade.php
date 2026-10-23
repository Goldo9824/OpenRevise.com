<x-layouts.app page="subjects" active="subjects" :title="$seo['title']" :description="$seo['description']" :canonical="$seo['canonical']" :json-ld="$seo['jsonLd']">
    <div class="max-w-5xl">
        <x-crumbs :items="[['Home', app_url('/')], ['Subjects', null]]" />
        <h1 class="page-title mt-4">GCSE subjects</h1>
        <p class="lead mt-2">Notes, exam questions and mock exams for AQA, Edexcel and OCR. Choose your exam board in the menu to see only your course.</p>

        <div class="mt-6 grid gap-3 sm:grid-cols-2">
            @forelse ($subjects as $subject)
                @include('app.partials.subject-card', ['subject' => $subject, 'count' => $outlines[$subject->id]->lessonCount(), 'topics' => $outlines[$subject->id]->topics])
            @empty
                <p class="text-muted">The first subjects are being written. Come back soon.</p>
            @endforelse
        </div>
    </div>
</x-layouts.app>
