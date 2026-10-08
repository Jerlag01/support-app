<?php

namespace App\Providers\Filament\Concerns;

use App\Http\Middleware\SetLocale;
use App\Support\HelpdeskSettings;
use App\Support\TwoFactor;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Shared look and plumbing for the agent and admin panels, so both stay visually identical.
 */
trait ConfiguresSupportPanel
{
    protected function configureSupportPanel(Panel $panel, string $label): Panel
    {
        return $panel
            ->login()
            ->passwordReset()
            ->profile(isSimple: false)
            // Recommended, not required: users switch it on in their profile.
            ->multiFactorAuthentication(fn (): array => [TwoFactor::app(), TwoFactor::email()], isRequired: false)
            ->renderHook(PanelsRenderHook::CONTENT_START, fn () => view('filament.two-factor-reminder'))
            ->brandName(fn (): string => app(HelpdeskSettings::class)->companyName().' '.$label)
            ->brandLogo(fn () => view('filament.brand', ['label' => $label]))
            ->brandLogoHeight('2.25rem')
            ->favicon(fn (): string => app(HelpdeskSettings::class)->faviconUrl())
            ->colors(fn (): array => [
                'primary' => Color::hex(app(HelpdeskSettings::class)->accentColor()),
                'gray' => Color::Slate,
            ])
            ->font('Inter')
            ->sidebarCollapsibleOnDesktop()
            ->userMenuItems([
                Action::make('agentPanel')
                    ->label(fn (): string => __('admin.panels.agent'))
                    ->icon(Heroicon::OutlinedLifebuoy)
                    ->url('/agent')
                    ->visible(fn (): bool => $panel->getId() !== 'agent'),
                Action::make('adminPanel')
                    ->label(fn (): string => __('admin.panels.admin'))
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->url('/admin')
                    ->visible(fn (): bool => $panel->getId() !== 'admin' && (bool) auth()->user()?->isAdmin()),
                Action::make('clientPortal')
                    ->label(fn (): string => __('admin.panels.portal'))
                    ->icon(Heroicon::OutlinedGlobeAlt)
                    ->url('/', shouldOpenInNewTab: true),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                SetLocale::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
