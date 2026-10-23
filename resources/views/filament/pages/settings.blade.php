<x-filament-panels::page>
    @vite('resources/js/passkeys.js')

    <div class="grid gap-6 lg:grid-cols-2">
        <x-filament::section heading="Your account" description="The single admin account.">
            <form wire:submit="saveAccount" class="grid gap-4">
                <label class="grid gap-1.5 text-sm font-medium">Name
                    <x-filament::input.wrapper><x-filament::input type="text" wire:model="name" required /></x-filament::input.wrapper>
                </label>
                <label class="grid gap-1.5 text-sm font-medium">Email (your sign-in)
                    <x-filament::input.wrapper><x-filament::input type="email" wire:model="email" required /></x-filament::input.wrapper>
                </label>
                @error('email') <p class="text-sm text-danger-600">{{ $message }}</p> @enderror
                <div><x-filament::button type="submit">Save</x-filament::button></div>
            </form>
        </x-filament::section>

        <x-filament::section heading="Change password" description="At least 12 characters.">
            <form wire:submit="changePassword" class="grid gap-4">
                <label class="grid gap-1.5 text-sm font-medium">Current password
                    <x-filament::input.wrapper><x-filament::input type="password" wire:model="currentPassword" autocomplete="current-password" required /></x-filament::input.wrapper>
                </label>
                @error('currentPassword') <p class="text-sm text-danger-600">{{ $message }}</p> @enderror
                <label class="grid gap-1.5 text-sm font-medium">New password
                    <x-filament::input.wrapper><x-filament::input type="password" wire:model="newPassword" autocomplete="new-password" required /></x-filament::input.wrapper>
                </label>
                <label class="grid gap-1.5 text-sm font-medium">New password again
                    <x-filament::input.wrapper><x-filament::input type="password" wire:model="newPasswordConfirmation" autocomplete="new-password" required /></x-filament::input.wrapper>
                </label>
                @error('newPassword') <p class="text-sm text-danger-600">{{ $message }}</p> @enderror
                <div><x-filament::button type="submit">Change password</x-filament::button></div>
            </form>
        </x-filament::section>

        <x-filament::section heading="Passkeys" description="Once you add a passkey, signing in asks for your password and then the passkey (Face ID, Touch ID, Windows Hello or a security key).">
            @if ($passkeys->isEmpty())
                <p class="text-sm text-gray-500">No passkey yet: signing in needs only your password.</p>
            @else
                <ul class="divide-y divide-gray-200 dark:divide-white/10 text-sm">
                    @foreach ($passkeys as $passkey)
                        <li class="flex items-center justify-between gap-3 py-2.5">
                            <span>
                                <span class="font-medium">{{ $passkey->name }}</span>
                                <span class="block text-xs text-gray-500">Added {{ $passkey->created_at->format('j M Y') }} · {{ $passkey->last_used_at ? 'last used '.$passkey->last_used_at->diffForHumans() : 'not used yet' }}</span>
                            </span>
                            <x-filament::button color="danger" size="sm" outlined wire:click="deletePasskey({{ $passkey->id }})" wire:confirm="Remove this passkey?">Remove</x-filament::button>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="mt-4 grid gap-3" x-data="{ busy: false, error: '' }">
                <label class="grid gap-1.5 text-sm font-medium">Name for the new passkey
                    <x-filament::input.wrapper><x-filament::input type="text" wire:model="passkeyName" placeholder="MacBook, iPhone..." /></x-filament::input.wrapper>
                </label>
                <div>
                    <x-filament::button icon="heroicon-o-finger-print" x-bind:disabled="busy" x-on:click="
                        busy = true; error = '';
                        try {
                            const options = await $wire.passkeyOptions();
                            const passkey = await window.openrevisePasskeys.register(options);
                            await $wire.storePasskey(passkey);
                        } catch (e) {
                            error = 'The passkey prompt was closed or this browser does not support passkeys.';
                        }
                        busy = false;
                    ">Add a passkey</x-filament::button>
                </div>
                <p x-show="error" x-text="error" class="text-sm text-danger-600"></p>
            </div>
        </x-filament::section>

        <x-filament::section heading="Recovery codes" description="If you lose your passkey, a recovery code lets you in instead. Each one works once.">
            @if ($newRecoveryCodes)
                <div class="rounded-lg border border-warning-300 bg-warning-50 p-4 dark:bg-warning-500/10">
                    <p class="text-sm font-semibold">Save these codes somewhere safe now. They will not be shown again.</p>
                    <ul class="mt-3 grid grid-cols-2 gap-1.5 font-mono text-sm select-all">
                        @foreach ($newRecoveryCodes as $code)
                            <li>{{ $code }}</li>
                        @endforeach
                    </ul>
                </div>
            @else
                <p class="text-sm text-gray-500">{{ $recoveryLeft }} unused {{ Str::plural('code', $recoveryLeft) }} left.</p>
            @endif
            <div class="mt-4">
                <x-filament::button color="gray" wire:click="regenerateRecoveryCodes" wire:confirm="Make new codes? The old ones will stop working.">Make new codes</x-filament::button>
            </div>
            <p class="mt-4 text-xs text-gray-500">Lost your password as well? From the LWS web terminal, run <code>php artisan openrevise:admin</code> in the openrevise-core folder to set a new one.</p>
        </x-filament::section>

        <x-filament::section heading="Site settings" class="lg:col-span-2">
            <form wire:submit="saveSite" class="grid gap-4 max-w-2xl">
                <label class="grid gap-1.5 text-sm font-medium">Announcement
                    <x-filament::input.wrapper><x-filament::input type="text" wire:model="announcement" maxlength="300" placeholder="e.g. New: AQA Chemistry paper 1 mock exams are live" /></x-filament::input.wrapper>
                    <span class="text-xs font-normal text-gray-500">Shown in a thin bar at the top of every app page. Leave empty for none.</span>
                </label>
                <label class="flex items-center gap-3 text-sm font-medium">
                    <x-filament::input.checkbox wire:model="analytics" />
                    Count page views (the cookie-free tracker on openrevise.com and the app)
                </label>
                <div><x-filament::button type="submit">Save site settings</x-filament::button></div>
            </form>
        </x-filament::section>
    </div>
</x-filament-panels::page>
