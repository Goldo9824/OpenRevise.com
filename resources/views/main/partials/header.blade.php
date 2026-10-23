<header class="site-header">
    <div class="wrap flex items-center justify-between gap-4 h-16">
        <x-wordmark :href="main_url('/')" />
        <nav class="flex items-center gap-5" aria-label="Main">
            <a href="{{ main_url('/about/') }}" class="nav-link hidden sm:inline" @if (request()->is('about')) aria-current="page" @endif>About</a>
            <a href="{{ main_url('/contact/') }}" class="nav-link hidden sm:inline" @if (request()->is('contact')) aria-current="page" @endif>Contact</a>
            <a href="{{ app_url('/') }}" class="btn btn-small">Start revising <span aria-hidden="true">&rarr;</span></a>
        </nav>
    </div>
</header>
