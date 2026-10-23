<x-layouts.page :seo="$seo" eyebrow="Contact" heading="Corrections and contact" page-name="contact">
    <p class="text-lg text-ink-2">The quickest way to fix a mistake in a lesson is to report it from the lesson itself.</p>

    <h2>Report a mistake in a lesson</h2>
    <ol class="list-decimal pl-5 space-y-2">
        <li>Open the lesson or exam question page with the mistake.</li>
        <li>Scroll to the bottom and choose <strong>Spotted a mistake? Tell us</strong>.</li>
        <li>Say what is wrong in a sentence or two, and send.</li>
    </ol>
    <p>You do not need to give your name or email address. The report arrives with the page it came from, so the right lesson gets fixed.</p>

    <h2>Anything else</h2>
    @if ($owner['email'])
        <p>For anything else, such as a suggestion, a subject you would like covered, or a question about privacy, email <a href="mailto:{{ $owner['email'] }}">{{ $owner['email'] }}</a>.</p>
    @else
        <p>A contact email address will be listed here soon. Until then, please use the report link on any lesson.</p>
    @endif

    <h2>Teachers</h2>
    <p>You are welcome to link to any OpenRevise page from your own materials, homework or virtual classroom. Each lesson has a permanent address that will not change.</p>
</x-layouts.page>
