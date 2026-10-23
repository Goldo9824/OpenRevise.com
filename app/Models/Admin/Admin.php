<?php

namespace App\Models\Admin;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\LaravelPasskeys\Models\Concerns\HasPasskeys;
use Spatie\LaravelPasskeys\Models\Concerns\InteractsWithPasskeys;

/** The single admin account. */
class Admin extends Authenticatable implements FilamentUser, HasPasskeys
{
    use InteractsWithPasskeys;

    protected $connection = 'admin';

    protected $guarded = ['id'];

    protected $hidden = ['password', 'remember_token', 'recovery_codes'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'recovery_codes' => 'encrypted:array',
            'last_login_at' => 'datetime',
        ];
    }

    public function passkeys(): HasMany
    {
        return $this->hasMany(Passkey::class, 'authenticatable_id');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function getPasskeyId(): string
    {
        return (string) $this->id;
    }

    public function hasPasskeys(): bool
    {
        return $this->passkeys()->exists();
    }

    /**
     * Ten one-time codes, shown once, that stand in for the passkey if it is
     * lost. Stored hashed.
     *
     * @return list<string>
     */
    public function regenerateRecoveryCodes(): array
    {
        $codes = collect(range(1, 10))
            ->map(fn () => Str::lower(Str::random(5).'-'.Str::random(5)))
            ->all();

        $this->forceFill(['recovery_codes' => array_map(fn ($c) => Hash::make($c), $codes)])->save();

        return $codes;
    }

    public function useRecoveryCode(string $code): bool
    {
        $code = Str::lower(trim($code));
        $hashes = $this->recovery_codes ?? [];

        foreach ($hashes as $i => $hash) {
            if (Hash::check($code, $hash)) {
                unset($hashes[$i]);
                $this->forceFill(['recovery_codes' => array_values($hashes)])->save();

                return true;
            }
        }

        return false;
    }

    public function remainingRecoveryCodes(): int
    {
        return count($this->recovery_codes ?? []);
    }
}
