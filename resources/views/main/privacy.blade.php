<x-layouts.page :seo="$seo" eyebrow="Privacy" heading="What OpenRevise collects" page-name="privacy">
    <p class="lead !text-base">Very little. There are no accounts, no cookies, no advertising trackers and no scripts from other companies. This page explains exactly what is collected and why.</p>

    <h2>Who is responsible</h2>
    <p>OpenRevise is run by {{ $owner['name'] ?: 'the owner of OpenRevise' }}, who is the data controller for the information below.@if ($owner['email']) You can reach them at <a href="mailto:{{ $owner['email'] }}">{{ $owner['email'] }}</a>.@endif</p>

    <h2>Page views</h2>
    <p>To know which lessons are useful, OpenRevise counts page views with its own small script. For each page you open, it records:</p>
    <ul>
        <li>the address of the page, and the website you came from (for example, a search engine);</li>
        <li>your browser, operating system and screen width, to tell phones from computers;</li>
        <li>your country, worked out from your device's time zone setting;</li>
        <li>how long the page stayed open.</li>
    </ul>
    <p>Your IP address is never stored. To count visitors without cookies, it is combined with your browser details and a random code that changes every day, and turned into a scrambled identifier. The random codes are deleted after two days, so the identifier cannot be traced back to you or linked across days.</p>
    <p>Individual page views are kept for 90 days, then turned into daily totals and deleted. The script does not run if your browser sends a &ldquo;Do Not Track&rdquo; signal.</p>

    <h2>Your answers and choices</h2>
    <p>The answers you type, the marks you give yourself and your exam board choice are saved in your browser's local storage, on your device only. They are never sent to OpenRevise. Clearing your browser's site data removes them.</p>

    <h2>Mistake reports</h2>
    <p>If you report a mistake, the message you write and the page it was sent from are stored so the lesson can be fixed. A scrambled version of your IP address is kept with it to stop spam. Please do not include personal details in a report.</p>

    <h2>Hosting</h2>
    <p>OpenRevise is hosted by LWS in France. Like any web host, its servers may keep short-term technical logs of requests for security and to keep the service running.</p>

    <h2>Fonts and other services</h2>
    <p>Fonts and every other file are served from OpenRevise itself. No content is loaded from Google, social networks or advertising services.</p>

    <h2>Your rights</h2>
    <p>Under data protection law (the UK GDPR and the EU GDPR) you can ask what information is held about you, and ask for it to be corrected or deleted. Because OpenRevise does not know who you are, it usually holds nothing that can be linked to you.@if ($owner['email']) Contact <a href="mailto:{{ $owner['email'] }}">{{ $owner['email'] }}</a> with any question.@endif You can also complain to the Information Commissioner's Office (ico.org.uk) in the UK, or the CNIL (cnil.fr) in France.</p>

    <h2>Changes</h2>
    <p>If this page changes, the date below changes too.</p>
    <p class="text-sm text-muted">Last updated: 23 October 2026</p>
</x-layouts.page>
