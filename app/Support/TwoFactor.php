<?php

namespace App\Support;

use App\Models\User;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\MultiFactor\Email\EmailAuthentication;

/**
 * Two-step verification for everyone: an authenticator app (Google/Microsoft Authenticator…)
 * or a code by e-mail. Recommended, never required. The portal and the Filament panels use the
 * same providers and the same user columns, so one set-up works everywhere.
 */
class TwoFactor
{
    public const SESSION_KEY = 'two_factor_login';

    /**
     * Minutes someone may take between the password and the code.
     */
    public const PENDING_MINUTES = 10;

    public static function app(): AppAuthentication
    {
        return AppAuthentication::make()
            ->recoverable()
            ->regenerableRecoveryCodes()
            ->brandName(helpdesk()->companyName());
    }

    public static function email(): EmailAuthentication
    {
        return EmailAuthentication::make()->codeExpiryMinutes(10);
    }

    /**
     * The methods a user has switched on, e.g. ['app', 'email'].
     *
     * @return list<string>
     */
    public static function methodsFor(User $user): array
    {
        return array_values(array_filter([
            filled($user->app_authentication_secret) ? 'app' : null,
            $user->has_email_authentication ? 'email' : null,
        ]));
    }
}
