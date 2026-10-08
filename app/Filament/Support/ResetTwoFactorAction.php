<?php

namespace App\Filament\Support;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

/**
 * For someone who lost their phone and recovery codes: switches both methods off so they can
 * log in with their password again and set it up anew.
 */
class ResetTwoFactorAction
{
    public static function make(): Action
    {
        return Action::make('resetTwoFactor')
            ->label(__('admin.two_factor.reset'))
            ->icon(Heroicon::OutlinedShieldExclamation)
            ->color('warning')
            ->visible(fn (User $record): bool => $record->hasTwoFactor() && $record->isNot(auth()->user()))
            ->requiresConfirmation()
            ->modalDescription(fn (User $record): string => __('admin.two_factor.reset_confirm', ['name' => $record->name]))
            ->action(function (User $record): void {
                $record->saveAppAuthenticationSecret(null);
                $record->saveAppAuthenticationRecoveryCodes(null);
                $record->toggleEmailAuthentication(false);
            })
            ->successNotificationTitle(__('admin.two_factor.reset_done'));
    }
}
