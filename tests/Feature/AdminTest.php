<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePasskey;
use App\Models\Admin\Admin;
use App\Models\Admin\SignInAttempt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminTest extends TestCase
{
    protected function admin(): Admin
    {
        return Admin::create(['name' => 'Louis', 'email' => 'louis@openrevise.test', 'password' => 'a-long-password-123']);
    }

    protected function addPasskey(Admin $admin): void
    {
        DB::connection('admin')->table('passkeys')->insert([
            'authenticatable_id' => $admin->id, 'name' => 'Laptop', 'credential_id' => 'abc', 'data' => '{}',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_guests_are_sent_to_the_sign_in_page(): void
    {
        $this->get('http://admin.openrevise.test/')->assertRedirect('http://admin.openrevise.test/login');
        $this->get('http://admin.openrevise.test/login')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_the_dashboard_opens_after_signing_in(): void
    {
        $this->actingAs($this->admin())->get('http://admin.openrevise.test/')->assertOk()->assertSee('Analytics');
    }

    public function test_with_a_passkey_the_password_is_not_enough(): void
    {
        $admin = $this->admin();
        $this->addPasskey($admin);

        $this->actingAs($admin)->get('http://admin.openrevise.test/')
            ->assertRedirect(route('admin.passkey.challenge'));

        $this->actingAs($admin)->get('http://admin.openrevise.test/passkey')->assertOk()->assertSee('Use my passkey');
    }

    public function test_a_recovery_code_stands_in_for_the_passkey_once(): void
    {
        $admin = $this->admin();
        $this->addPasskey($admin);
        $codes = $admin->regenerateRecoveryCodes();

        $this->actingAs($admin)
            ->withSession(['_token' => 'test'])
            ->post('http://admin.openrevise.test/passkey/recovery', ['_token' => 'test', 'code' => $codes[0]])
            ->assertRedirect('http://admin.openrevise.test/')
            ->assertSessionHas(RequirePasskey::SESSION_KEY, $admin->id);

        $this->assertSame(9, $admin->fresh()->remainingRecoveryCodes());
        $this->assertTrue(SignInAttempt::where('method', 'recovery code')->where('succeeded', true)->exists());

        $this->actingAs($admin)
            ->withSession(['_token' => 'test'])
            ->post('http://admin.openrevise.test/passkey/recovery', ['_token' => 'test', 'code' => $codes[0]])
            ->assertSessionHasErrors('code');
    }

    public function test_the_admin_command_resets_the_password(): void
    {
        $this->admin();

        $this->artisan('openrevise:admin', ['--password' => 'another-long-password'])->assertSuccessful();

        $this->assertTrue(password_verify('another-long-password', Admin::first()->password));
    }
}
