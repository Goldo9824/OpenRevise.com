<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\RequirePasskey;
use App\Models\Admin\SignInAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Spatie\LaravelPasskeys\Actions\FindPasskeyToAuthenticateAction;
use Spatie\LaravelPasskeys\Actions\GeneratePasskeyAuthenticationOptionsAction;
use Throwable;

/** The second sign-in step: confirm with a passkey, or a recovery code. */
class PasskeyController extends Controller
{
    public function challenge(Request $request): View|RedirectResponse
    {
        $admin = $request->user();

        if (! $admin->hasPasskeys() || $request->session()->get(RequirePasskey::SESSION_KEY) === $admin->getKey()) {
            return redirect()->to(admin_url('/'));
        }

        return view('admin.passkey', ['admin' => $admin]);
    }

    public function options(GeneratePasskeyAuthenticationOptionsAction $action): JsonResponse
    {
        return response()->json(json_decode($action->execute(), true));
    }

    public function verify(Request $request, FindPasskeyToAuthenticateAction $find): RedirectResponse
    {
        $request->validate(['response' => ['required', 'string', 'max:20000']]);
        $this->throttle($request);

        $admin = $request->user();
        $options = (string) $request->session()->pull('passkey-authentication-options');

        try {
            $passkey = $options ? $find->execute($request->input('response'), $options) : null;
        } catch (Throwable) {
            $passkey = null;
        }

        if (! $passkey || (int) $passkey->authenticatable_id !== (int) $admin->getKey()) {
            SignInAttempt::log($admin->email, 'passkey', false);

            return back()->withErrors(['passkey' => 'That passkey was not accepted. Try again, or use a recovery code.']);
        }

        return $this->confirmed($request, 'passkey');
    }

    public function recovery(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'max:40']]);
        $this->throttle($request);

        $admin = $request->user();

        if (! $admin->useRecoveryCode($request->input('code'))) {
            SignInAttempt::log($admin->email, 'recovery code', false);

            return back()->withErrors(['code' => 'That recovery code is not valid, or was already used.']);
        }

        return $this->confirmed($request, 'recovery code');
    }

    public function signOut(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to(admin_url('/login'));
    }

    protected function confirmed(Request $request, string $method): RedirectResponse
    {
        $admin = $request->user();
        $request->session()->put(RequirePasskey::SESSION_KEY, $admin->getKey());
        $request->session()->regenerate();
        RateLimiter::clear($this->limiterKey($request));
        SignInAttempt::log($admin->email, $method, true);

        return redirect()->intended(admin_url('/'));
    }

    protected function throttle(Request $request): void
    {
        $key = $this->limiterKey($request);

        abort_if(RateLimiter::tooManyAttempts($key, 5), 429, 'Too many attempts. Wait a minute and try again.');
        RateLimiter::hit($key, 60);
    }

    protected function limiterKey(Request $request): string
    {
        return 'passkey:'.$request->user()->getKey().':'.$request->ip();
    }
}
