<x-layouts.page :seo="$seo" eyebrow="About" heading="Free revision, for everyone sitting GCSEs" page-name="about">
    <p class="lead !text-base">OpenRevise is a free revision website for GCSE students. It has notes on every topic, exam questions after every lesson and mock exams, organised by exam board. There are no accounts and nothing to pay.</p>

    <h2>Why it exists</h2>
    <p>Good revision material should not depend on what your family can afford. The best GCSE resources are often behind a subscription, buried in long videos or written for a different exam board. OpenRevise puts short, accurate notes and realistic exam practice in one place, sorted by your board, and keeps them free.</p>

    <h2>Who is behind it</h2>
    <p>OpenRevise is an independent project{{ $owner['name'] ? ', run by '.$owner['name'] : '' }}. It is not connected to any exam board, school or publisher.</p>

    <h2>How the lessons are written</h2>
    <p>Lessons, questions and mock exams are drafted with the help of Claude, an AI model made by Anthropic, following each board's specification. They are saved as drafts and only published once they have been checked. If you spot something wrong, the &ldquo;Spotted a mistake?&rdquo; link at the bottom of every lesson reaches us directly.</p>

    <h2>What it is not</h2>
    <ul>
        <li>It is not a past paper archive. Questions are written in the style of each board, not copied from real exams.</li>
        <li>It is not affiliated with or endorsed by AQA, Pearson Edexcel or OCR. Their names are used only to say which course a lesson is for.</li>
        <li>It does not replace your teacher or your textbook. Use it alongside them.</li>
    </ul>

    <h2>Why free</h2>
    <p>Running OpenRevise costs very little: one small web hosting plan. Keeping it free and without accounts is the point of the project.</p>
</x-layouts.page>
