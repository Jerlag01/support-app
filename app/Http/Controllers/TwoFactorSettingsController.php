<?php

namespace App\Http\Controllers;

use App\Support\TwoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Setting up and switching off two-step verification from the portal profile.
 * Both methods are optional; switching one off needs the current password.
 */
class TwoFactorSettingsController extends Controller
{
    private const PENDING_SECRET = 'two_factor_setup.app_secret';

    /**
     * Step 1 for the app: a new secret, shown as QR code until the first code confirms it.
     */
    public function createApp(Request $request): View|RedirectResponse
    {
        if (filled($request->user()->app_authentication_secret)) {
            return redirect()->route('profile.edit');
        }

        $secret = $request->session()->get(self::PENDING_SECRET) ?? TwoFactor::app()->generateSecret();
        $request->session()->put(self::PENDING_SECRET, $secret);

        return view('profile.two-factor-app', [
            'secret' => $secret,
            'qrCode' => TwoFactor::app()->generateQrCodeDataUri($secret),
        ]);
    }

    /**
     * Step 2 for the app: the first code proves the app is set up; recovery codes are shown once.
     */
    public function storeApp(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:10']]);
        $secret = $request->session()->get(self::PENDING_SECRET);

        if (blank($secret)) {
            return redirect()->route('two-factor.app.create');
        }

        if (! TwoFactor::app()->verifyCode(preg_replace('/\s+/', '', $data['code']), $secret)) {
            throw ValidationException::withMessages(['code' => __('support.two_factor.invalid_code')]);
        }

        $user = $request->user();
        $recoveryCodes = TwoFactor::app()->generateRecoveryCodes();

        $user->saveAppAuthenticationSecret($secret);
        TwoFactor::app()->saveRecoveryCodes($user, $recoveryCodes);
        $request->session()->forget(self::PENDING_SECRET);

        return redirect()->route('profile.edit')
            ->with('status', __('support.two_factor.app_enabled'))
            ->with('recovery_codes', $recoveryCodes);
    }

    public function destroyApp(Request $request): RedirectResponse
    {
        $request->validateWithBag('twoFactorApp', ['app_password' => ['required', 'current_password']]);

        $user = $request->user();
        $user->saveAppAuthenticationSecret(null);
        $user->saveAppAuthenticationRecoveryCodes(null);

        return redirect()->route('profile.edit')->with('status', __('support.two_factor.app_disabled'));
    }

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $request->validateWithBag('twoFactorRecovery', ['recovery_password' => ['required', 'current_password']]);

        $user = $request->user();

        if (blank($user->app_authentication_secret)) {
            return redirect()->route('profile.edit');
        }

        $recoveryCodes = TwoFactor::app()->generateRecoveryCodes();
        TwoFactor::app()->saveRecoveryCodes($user, $recoveryCodes);

        return redirect()->route('profile.edit')
            ->with('status', __('support.two_factor.recovery_regenerated'))
            ->with('recovery_codes', $recoveryCodes);
    }

    /**
     * Step 1 for e-mail: send a code to prove the address receives it.
     */
    public function sendEmail(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->has_email_authentication) {
            return redirect()->route('profile.edit');
        }

        $sent = TwoFactor::email()->sendCode($user);

        return redirect()->route('two-factor.email.create')
            ->with('status', $sent ? __('support.two_factor.email_sent', ['email' => $user->email]) : __('support.two_factor.email_wait'));
    }

    public function createEmail(Request $request): View|RedirectResponse
    {
        if ($request->user()->has_email_authentication) {
            return redirect()->route('profile.edit');
        }

        return view('profile.two-factor-email', ['email' => $request->user()->email]);
    }

    /**
     * Step 2 for e-mail: the code from the mail switches the method on.
     */
    public function storeEmail(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:10']]);
        $user = $request->user();

        if (! TwoFactor::email()->verifyCode(preg_replace('/\s+/', '', $data['code']), $user)) {
            throw ValidationException::withMessages(['code' => __('support.two_factor.invalid_code')]);
        }

        $user->toggleEmailAuthentication(true);

        return redirect()->route('profile.edit')->with('status', __('support.two_factor.email_enabled'));
    }

    public function destroyEmail(Request $request): RedirectResponse
    {
        $request->validateWithBag('twoFactorEmail', ['email_password' => ['required', 'current_password']]);

        $request->user()->toggleEmailAuthentication(false);

        return redirect()->route('profile.edit')->with('status', __('support.two_factor.email_disabled'));
    }
}
