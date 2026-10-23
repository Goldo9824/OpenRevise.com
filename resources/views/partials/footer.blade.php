<footer class="site-footer no-print">
    <div class="max-w-6xl mx-auto px-5 py-10 grid gap-8 md:grid-cols-[1.5fr_1fr_1fr]">
        <div class="max-w-sm">
            <x-logo :href="main_url('/')" class="!text-xl" />
            <p class="mt-3">Free GCSE revision notes, exam questions and mock exams. No account, no sign-up.</p>
            <p class="mt-3 text-xs text-faint">OpenRevise is independent. It is not affiliated with or endorsed by AQA, Pearson Edexcel or OCR.</p>
        </div>
        <nav aria-label="Subjects">
            <p class="section-label mb-3">Revise</p>
            <ul class="space-y-1.5">
                <li><a href="{{ app_url('/gcse/') }}">All subjects</a></li>
                @foreach (\App\Support\Navigation::footerSubjects()->take(5) as $s)
                    <li><a href="{{ app_url($s->path()) }}">GCSE {{ $s->name }}</a></li>
                @endforeach
            </ul>
        </nav>
        <nav aria-label="About OpenRevise">
            <p class="section-label mb-3">OpenRevise</p>
            <ul class="space-y-1.5">
                <li><a href="{{ main_url('/about/') }}">About</a></li>
                <li><a href="{{ main_url('/contact/') }}">Contact and corrections</a></li>
                <li><a href="{{ main_url('/privacy/') }}">Privacy</a></li>
                <li><a href="{{ main_url('/terms/') }}">Terms of use</a></li>
            </ul>
        </nav>
    </div>
    <div class="max-w-6xl mx-auto px-5 pb-8 text-xs text-faint flex flex-wrap gap-x-6 gap-y-1">
        <span>&copy; {{ now()->year }} OpenRevise</span>
        <span>No cookies. Page views are counted without tracking you.</span>
    </div>
</footer>
