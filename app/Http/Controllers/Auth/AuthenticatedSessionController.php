<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\TwoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->where('is_active', true)->first();

        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        // With two-step verification the password alone is not enough: ask for the code first.
        if ($user->hasTwoFactor()) {
            $request->session()->put(TwoFactor::SESSION_KEY, [
                'user_id' => $user->id,
                'remember' => $request->boolean('remember'),
                'expires_at' => now()->addMinutes(TwoFactor::PENDING_MINUTES)->getTimestamp(),
            ]);

            return redirect()->route('two-factor.challenge');
        }

        return static::completeLogin($request, $user, $request->boolean('remember'));
    }

    /**
     * Logs the user in and sends them to where they were going.
     */
    public static function completeLogin(Request $request, User $user, bool $remember): RedirectResponse
    {
        Auth::login($user, $remember);

        $request->session()->regenerate();
        $request->session()->put('locale', $user->locale);

        if ($user->isStaff()) {
            return redirect()->intended('/agent');
        }

        return redirect()->intended(route('tickets.index'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
