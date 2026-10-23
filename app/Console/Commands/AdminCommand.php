<?php

namespace App\Console\Commands;

use App\Models\Admin\Admin;
use Illuminate\Console\Attributes\AsCommand;
use Illuminate\Console\Command;

/**
 * Creates the single admin account, or resets its password. This is the
 * recovery route if the password is lost: run it from the LWS web terminal.
 */
#[AsCommand(name: 'openrevise:admin')]
class AdminCommand extends Command
{
    protected $signature = 'openrevise:admin
        {--email= : Sign-in email}
        {--name= : Display name}
        {--password= : New password (asked for if left out)}
        {--remove-passkeys : Also remove passkeys, if the passkey and the recovery codes are lost}';

    protected $description = 'Create the admin account or reset its password';

    public function handle(): int
    {
        $admin = Admin::query()->first();

        $email = $this->option('email') ?: ($admin?->email ?? $this->ask('Email'));
        $name = $this->option('name') ?: ($admin?->name ?? $this->ask('Name', 'Admin'));
        $password = $this->option('password') ?: $this->secret('New password (at least 12 characters)');

        if (strlen((string) $password) < 12) {
            $this->components->error('The password must be at least 12 characters.');

            return self::FAILURE;
        }

        $admin ??= new Admin;
        $admin->fill(['name' => $name, 'email' => $email, 'password' => $password])->save();

        if ($this->option('remove-passkeys')) {
            $admin->passkeys()->delete();
            $admin->forceFill(['recovery_codes' => null])->save();
            $this->components->info('Passkeys and recovery codes removed.');
        }

        $this->components->info("Admin account saved for $email. Sign in at ".admin_url('/login'));

        return self::SUCCESS;
    }
}
