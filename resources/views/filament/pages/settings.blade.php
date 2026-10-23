<x-filament-panels::page>
    @vite('resources/js/passkeys.js')

    <div class="or-settings">
        <x-filament::section heading="Your account" description="The single admin account.">
            <form wire:submit="saveAccount" class="or-form">
                <label class="or-field">Name
                    <x-filament::input.wrapper><x-filament::input type="text" wire:model="name" required /></x-filament::input.wrapper>
                </label>
                <label class="or-field">Email (your sign-in)
                    <x-filament::input.wrapper><x-filament::input type="email" wire:model="email" required /></x-filament::input.wrapper>
                </label>
                @error('email') <p class="or-error">{{ $message }}</p> @enderror
                <div><x-filament::button type="submit">Save</x-filament::button></div>
            </form>
        </x-filament::section>

        <x-filament::section heading="Change password" description="At least 12 characters.">
            <form wire:submit="changePassword" class="or-form">
                <label class="or-field">Current password
                    <x-filament::input.wrapper><x-filament::input type="password" wire:model="currentPassword" autocomplete="current-password" required /></x-filament::input.wrapper>
                </label>
                @error('currentPassword') <p class="or-error">{{ $message }}</p> @enderror
                <label class="or-field">New password
                    <x-filament::input.wrapper><x-filament::input type="password" wire:model="newPassword" autocomplete="new-password" required /></x-filament::input.wrapper>
                </label>
                <label class="or-field">New password again
                    <x-filament::input.wrapper><x-filament::input type="password" wire:model="newPasswordConfirmation" autocomplete="new-password" required /></x-filament::input.wrapper>
                </label>
                @error('newPassword') <p class="or-error">{{ $message }}</p> @enderror
                <div><x-filament::button type="submit">Change password</x-filament::button></div>
            </form>
        </x-filament::section>

        <x-filament::section heading="Passkeys" description="Once you add a passkey, signing in asks for your password and then the passkey (Face ID, Touch ID, Windows Hello or a security key).">
            @if ($passkeys->isEmpty())
                <p class="or-muted">No passkey yet: signing in needs only your password.</p>
            @else
                <ul class="or-list">
                    @foreach ($passkeys as $passkey)
                        <li>
                            <span>
                                <strong>{{ $passkey->name }}</strong>
                                <span class="or-help" style="display:block">Added {{ $passkey->created_at->format('j M Y') }} · {{ $passkey->last_used_at ? 'last used '.$passkey->last_used_at->diffForHumans() : 'not used yet' }}</span>
                            </span>
                            <x-filament::button color="danger" size="sm" outlined wire:click="deletePasskey({{ $passkey->id }})" wire:confirm="Remove this passkey?">Remove</x-filament::button>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="or-form" style="margin-top:1rem" x-data="{ busy: false, error: '' }">
                <label class="or-field">Name for the new passkey
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
                <p x-show="error" x-text="error" class="or-error"></p>
            </div>
        </x-filament::section>

        <x-filament::section heading="Recovery codes" description="If you lose your passkey, a recovery code lets you in instead. Each one works once.">
            @if ($newRecoveryCodes)
                <div class="or-codes">
                    <p><strong>Save these codes somewhere safe now. They will not be shown again.</strong></p>
                    <ul>
                        @foreach ($newRecoveryCodes as $code)
                            <li>{{ $code }}</li>
                        @endforeach
                    </ul>
                </div>
            @else
                <p class="or-muted">{{ $recoveryLeft }} unused {{ Str::plural('code', $recoveryLeft) }} left.</p>
            @endif
            <div style="margin-top:1rem">
                <x-filament::button color="gray" wire:click="regenerateRecoveryCodes" wire:confirm="Make new codes? The old ones will stop working.">Make new codes</x-filament::button>
            </div>
            <p class="or-help" style="margin-top:1rem">Lost your password as well? From the LWS web terminal, run <code>php artisan openrevise:admin</code> in the openrevise-core folder to set a new one.</p>
        </x-filament::section>

        <x-filament::section heading="Site settings" class="wide">
            <form wire:submit="saveSite" class="or-form">
                <label class="or-field">Announcement
                    <x-filament::input.wrapper><x-filament::input type="text" wire:model="announcement" maxlength="300" placeholder="e.g. New: AQA Chemistry paper 1 mock exams are live" /></x-filament::input.wrapper>
                    <span class="or-help">Shown in a thin bar at the top of every app page. Leave empty for none.</span>
                </label>
                <label class="or-check">
                    <x-filament::input.checkbox wire:model="analytics" />
                    Count page views (the cookie-free tracker on openrevise.com and the app)
                </label>
                <div><x-filament::button type="submit">Save site settings</x-filament::button></div>
            </form>
        </x-filament::section>
    </div>
</x-filament-panels::page>
