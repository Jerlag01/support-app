<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\TwoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * The second step of the portal login: a code from the authenticator app (or a recovery code),
 * or a code sent by e-mail. The user is only logged in once the code is right.
 */
class TwoFactorChallengeController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $user = $this->pendingUser($request);

        if ($user === null) {
            return redirect()->route('login');
        }

        $methods = TwoFactor::methodsFor($user);

        // E-mail only: send the code straight away (once per login attempt).
        if ($methods === ['email'] && ! $request->session()->get(TwoFactor::SESSION_KEY.'.email_sent')) {
            $this->sendEmailCode($request, $user);
        }

        return view('auth.two-factor-challenge', [
            'methods' => $methods,
            'emailSent' => (bool) $request->session()->get(TwoFactor::SESSION_KEY.'.email_sent'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);

        if ($user === null) {
            return redirect()->route('login')->withErrors(['email' => __('support.two_factor.expired')]);
        }

        $data = $request->validate([
            'method' => ['required', 'in:app,email,recovery'],
            'code' => ['required', 'string', 'max:64'],
        ]);

        $code = preg_replace('/\s+/', '', $data['code']);
        $methods = TwoFactor::methodsFor($user);

        $valid = match ($data['method']) {
            'app' => in_array('app', $methods, true) && TwoFactor::app()->verifyCode($code, $user->app_authentication_secret, shouldPreventCodeReuse: true),
            'recovery' => in_array('app', $methods, true) && TwoFactor::app()->verifyRecoveryCode($code, $user),
            'email' => in_array('email', $methods, true) && TwoFactor::email()->verifyCode($code, $user),
        };

        if (! $valid) {
            throw ValidationException::withMessages(['code' => __('support.two_factor.invalid_code')]);
        }

        $remember = (bool) $request->session()->get(TwoFactor::SESSION_KEY.'.remember');
        $request->session()->forget(TwoFactor::SESSION_KEY);

        return AuthenticatedSessionController::completeLogin($request, $user, $remember);
    }

    /**
     * (Re)sends the e-mail code, e.g. when the app is not at hand.
     */
    public function sendEmail(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);

        if ($user === null || ! $user->has_email_authentication) {
            return redirect()->route('login');
        }

        $this->sendEmailCode($request, $user);

        return redirect()->route('two-factor.challenge', ['method' => 'email']);
    }

    protected function sendEmailCode(Request $request, User $user): void
    {
        if (TwoFactor::email()->sendCode($user)) {
            $request->session()->put(TwoFactor::SESSION_KEY.'.email_sent', true);
            $request->session()->flash('status', __('support.two_factor.email_sent', ['email' => $user->email]));
        } else {
            $request->session()->flash('status', __('support.two_factor.email_wait'));
        }
    }

    /**
     * The user who entered the right password and still has to give a code, if not expired.
     */
    protected function pendingUser(Request $request): ?User
    {
        $pending = $request->session()->get(TwoFactor::SESSION_KEY);

        if (! is_array($pending) || ($pending['expires_at'] ?? 0) < now()->getTimestamp()) {
            $request->session()->forget(TwoFactor::SESSION_KEY);

            return null;
        }

        return User::where('is_active', true)->find($pending['user_id'] ?? null);
    }
}
