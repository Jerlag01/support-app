{{-- Recommends two-step verification to signed-in users who have not set it up yet. --}}
@auth
    @unless (auth()->user()->hasTwoFactor())
        <div {{ $attributes->merge(['class' => 'flex flex-col gap-3 rounded-lg border border-accent/40 border-l-4 border-l-accent bg-accent/5 p-4 sm:flex-row sm:items-center']) }} data-two-factor-reminder>
            <x-site-icon name="shield" class="h-6 w-6 shrink-0 text-accent" />
            <div class="flex-1 text-sm">
                <p class="font-semibold">{{ __('support.two_factor.reminder_title') }}</p>
                <p class="text-slate">{{ __('support.two_factor.reminder_text') }}</p>
            </div>
            @unless (request()->routeIs('profile.edit'))
                <a href="{{ route('profile.edit') }}#two-factor" class="btn btn-outline py-2">{{ __('support.two_factor.reminder_action') }}</a>
            @endunless
        </div>
    @endunless
@endauth
