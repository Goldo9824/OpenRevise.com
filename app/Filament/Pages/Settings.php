<?php

namespace App\Filament\Pages;

use App\Http\Middleware\RequirePasskey;
use App\Models\Admin\Admin;
use App\Models\Admin\Passkey;
use App\Models\Admin\Setting;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Spatie\LaravelPasskeys\Actions\GeneratePasskeyRegisterOptionsAction;
use Spatie\LaravelPasskeys\Actions\StorePasskeyAction;
use Throwable;

/** Password, passkeys and recovery codes, and the few site-wide settings. */
class Settings extends Page
{
    protected string $view = 'filament.pages.settings';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 1;

    public string $name = '';

    public string $email = '';

    public string $currentPassword = '';

    public string $newPassword = '';

    public string $newPasswordConfirmation = '';

    public string $passkeyName = '';

    /** @var list<string> Shown once, right after they are made. */
    public array $newRecoveryCodes = [];

    public string $announcement = '';

    public bool $analytics = true;

    public function mount(): void
    {
        $admin = $this->admin();
        $this->name = $admin->name;
        $this->email = $admin->email;
        $this->announcement = (string) Setting::get('announcement', '');
        $this->analytics = Setting::get('analytics', '1') === '1';
    }

    protected function admin(): Admin
    {
        return Auth::guard('web')->user();
    }

    public function saveAccount(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $this->admin()->update(['name' => $this->name, 'email' => $this->email]);
        Notification::make()->title('Account saved')->success()->send();
    }

    public function changePassword(): void
    {
        $this->validate([
            'currentPassword' => ['required', 'current_password:web'],
            'newPassword' => ['required', 'string', Password::min(12), 'same:newPasswordConfirmation'],
        ], [
            'newPassword.same' => 'The two new passwords do not match.',
        ]);

        $this->admin()->update(['password' => $this->newPassword]);
        $this->reset('currentPassword', 'newPassword', 'newPasswordConfirmation');

        Notification::make()->title('Password changed')->success()->send();
    }

    /** Step 1 of adding a passkey: the browser needs these options. */
    public function passkeyOptions(): string
    {
        $options = app(GeneratePasskeyRegisterOptionsAction::class)->execute($this->admin());
        session()->put('passkey-register-options', $options);

        return $options;
    }

    /** Step 2: the browser made a passkey; check it and keep it. */
    public function storePasskey(string $passkeyJson): void
    {
        $admin = $this->admin();
        $first = ! $admin->hasPasskeys();

        try {
            app(StorePasskeyAction::class)->execute(
                $admin,
                $passkeyJson,
                (string) session()->pull('passkey-register-options'),
                request()->getHost(),
                ['name' => trim($this->passkeyName) ?: 'Passkey added '.now()->format('j M Y')],
            );
        } catch (Throwable $e) {
            report($e);
            Notification::make()->title('That passkey could not be added')->body('Please try again.')->danger()->send();

            return;
        }

        // This session is already trusted; do not ask for the new passkey straight away.
        session()->put(RequirePasskey::SESSION_KEY, $admin->getKey());
        $this->passkeyName = '';

        if ($first || $admin->remainingRecoveryCodes() === 0) {
            $this->newRecoveryCodes = $admin->regenerateRecoveryCodes();
        }

        Notification::make()->title('Passkey added')->body('From now on, signing in asks for your password and then this passkey.')->success()->send();
    }

    public function deletePasskey(int $id): void
    {
        $admin = $this->admin();
        Passkey::where('authenticatable_id', $admin->getKey())->whereKey($id)->delete();

        Notification::make()->title('Passkey removed')
            ->body($admin->hasPasskeys() ? null : 'Signing in now needs only your password.')
            ->success()->send();
    }

    public function regenerateRecoveryCodes(): void
    {
        $this->newRecoveryCodes = $this->admin()->regenerateRecoveryCodes();
        Notification::make()->title('New recovery codes made')->body('The old ones no longer work.')->success()->send();
    }

    public function saveSite(): void
    {
        $this->validate(['announcement' => ['nullable', 'string', 'max:300']]);

        Setting::put('announcement', trim($this->announcement));
        Setting::put('analytics', $this->analytics ? '1' : '0');

        Notification::make()->title('Site settings saved')->body('They show on the public sites within five minutes.')->success()->send();
    }

    protected function getViewData(): array
    {
        $admin = $this->admin();

        return [
            'passkeys' => $admin->passkeys()->latest()->get(),
            'recoveryLeft' => $admin->remainingRecoveryCodes(),
        ];
    }
}
