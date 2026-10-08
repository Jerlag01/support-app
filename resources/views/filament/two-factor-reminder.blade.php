{{--
    Recommends two-step verification on the dashboard to users who have not set it up.
    Filament's precompiled CSS only ships its own classes, so styling is inline.
--}}
@if (auth()->check() && ! auth()->user()->hasTwoFactor() && request()->routeIs('filament.*.pages.dashboard'))
    <div data-two-factor-reminder style="display: flex; flex-wrap: wrap; align-items: center; gap: .75rem 1rem; margin-bottom: 1.5rem; padding: .875rem 1rem; border: 1px solid rgb(247 148 29 / .45); border-left: 4px solid #f7941d; border-radius: .75rem; background: rgb(247 148 29 / .08);">
        <x-filament::icon icon="heroicon-o-shield-check" style="width: 1.5rem; height: 1.5rem; color: #f7941d; flex-shrink: 0;" />
        <div style="flex: 1; min-width: 14rem;">
            <strong>{{ __('admin.two_factor.reminder_title') }}</strong>
            <div style="font-size: .875rem; opacity: .8;">{{ __('admin.two_factor.reminder_text') }}</div>
        </div>
        <x-filament::button tag="a" :href="filament()->getProfileUrl()" size="sm" icon="heroicon-o-lock-closed">
            {{ __('admin.two_factor.reminder_action') }}
        </x-filament::button>
    </div>
@endif
