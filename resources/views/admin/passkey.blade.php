<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Confirm it is you | OpenRevise admin</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/css/site.css', 'resources/js/passkeys.js'])
</head>
<body class="bg-panel min-h-dvh grid place-items-center p-5">
    <main class="w-full max-w-md">
        <p class="text-center"><span class="logo">openrevise</span></p>
        <div class="card mt-6 p-7">
            <h1 class="text-xl">Confirm it is you</h1>
            <p class="mt-2 text-sm text-muted">Signed in as {{ $admin->email }}. Use your passkey to finish signing in.</p>

            @if ($errors->any())
                <div class="mt-4 rounded-lg bg-danger-50 border border-orange-200 px-3.5 py-2.5 text-sm text-danger">{{ $errors->first() }}</div>
            @endif

            <form id="passkey-form" method="post" action="{{ route('admin.passkey.verify') }}" class="mt-5">
                @csrf
                <input type="hidden" name="response" id="passkey-response">
                <button type="button" id="passkey-button" class="btn btn-lg w-full">Use my passkey</button>
                <p id="passkey-message" class="mt-3 text-sm text-danger hidden"></p>
            </form>

            <details class="mt-6 border-t border-line pt-4">
                <summary class="cursor-pointer text-sm font-medium text-brand">Lost your passkey? Use a recovery code</summary>
                <form method="post" action="{{ route('admin.passkey.recovery') }}" class="mt-3 flex gap-2">
                    @csrf
                    <label for="code" class="sr-only">Recovery code</label>
                    <input id="code" name="code" autocomplete="one-time-code" required placeholder="abcde-fghij" class="flex-1 h-10 rounded-lg border border-line px-3 text-sm font-mono">
                    <button class="btn btn-outline">Use code</button>
                </form>
                <p class="mt-2 text-xs text-muted">{{ $admin->remainingRecoveryCodes() }} unused {{ Str::plural('code', $admin->remainingRecoveryCodes()) }} left. Each code works once.</p>
            </details>

            <form method="post" action="{{ route('admin.passkey.sign-out') }}" class="mt-5">
                @csrf
                <button class="text-sm text-muted hover:text-ink underline">Sign out</button>
            </form>
        </div>
    </main>

    <script>
        document.getElementById('passkey-button').addEventListener('click', async () => {
            const message = document.getElementById('passkey-message');
            message.classList.add('hidden');
            try {
                const options = await (await fetch(@js(route('admin.passkey.options')), { headers: { Accept: 'application/json' } })).json();
                document.getElementById('passkey-response').value = await window.openrevisePasskeys.authenticate(options);
                document.getElementById('passkey-form').submit();
            } catch (e) {
                message.textContent = 'The passkey prompt was closed or is not available on this device. Try again, or use a recovery code.';
                message.classList.remove('hidden');
            }
        });
    </script>
</body>
</html>
