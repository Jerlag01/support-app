@php
    $hasApp = in_array('app', $methods, true);
    $hasEmail = in_array('email', $methods, true);
    // Start with the app when there is one, unless the user asked for (or only has) the e-mail code.
    $showEmail = $hasEmail && (! $hasApp || request('method') === 'email');
@endphp

<x-layouts.app :title="__('support.two_factor.challenge_title')">
    <x-auth-card :title="__('support.two_factor.challenge_title')" :eyebrow="__('support.two_factor.eyebrow')">
        @if ($showEmail)
            <p class="text-sm text-slate">{{ __('support.two_factor.challenge_email') }}</p>
            <form method="POST" action="{{ route('two-factor.verify') }}" class="mt-5 space-y-5">
                @csrf
                <input type="hidden" name="method" value="email">
                <x-form-field name="code" :label="__('support.two_factor.code')" required autofocus inputmode="numeric" autocomplete="one-time-code" maxlength="10" />
                <button type="submit" class="btn btn-primary w-full">{{ __('support.two_factor.verify') }}</button>
            </form>
            <form method="POST" action="{{ route('two-factor.send-email') }}" class="mt-4 text-center">
                @csrf
                <button type="submit" class="text-sm font-semibold text-accent-hover hover:underline">
                    {{ $emailSent ? __('support.two_factor.resend') : __('support.two_factor.send_email') }}
                </button>
            </form>
            @if ($hasApp)
                <p class="mt-6 text-center text-sm"><a href="{{ route('two-factor.challenge') }}" class="font-semibold text-accent-hover hover:underline">{{ __('support.two_factor.use_app') }}</a></p>
            @endif
        @else
            <p class="text-sm text-slate">{{ __('support.two_factor.challenge_app') }}</p>
            <form method="POST" action="{{ route('two-factor.verify') }}" class="mt-5 space-y-5">
                @csrf
                <input type="hidden" name="method" value="app">
                <x-form-field name="code" :label="__('support.two_factor.code')" required autofocus inputmode="numeric" autocomplete="one-time-code" maxlength="10" />
                <button type="submit" class="btn btn-primary w-full">{{ __('support.two_factor.verify') }}</button>
            </form>

            <details class="mt-6 rounded border border-line p-4 text-sm">
                <summary class="cursor-pointer font-semibold">{{ __('support.two_factor.lost_device') }}</summary>
                <p class="mt-3 text-slate">{{ __('support.two_factor.recovery_hint') }}</p>
                <form method="POST" action="{{ route('two-factor.verify') }}" class="mt-3 flex gap-2">
                    @csrf
                    <input type="hidden" name="method" value="recovery">
                    <label for="recovery_code" class="sr-only">{{ __('support.two_factor.recovery_code') }}</label>
                    <input id="recovery_code" name="code" type="text" class="form-input font-mono" placeholder="xxxxxxxxxx-xxxxxxxxxx" autocomplete="off" required>
                    <button type="submit" class="btn btn-outline">{{ __('support.two_factor.verify') }}</button>
                </form>
            </details>

            @if ($hasEmail)
                <form method="POST" action="{{ route('two-factor.send-email') }}" class="mt-4 text-center">
                    @csrf
                    <button type="submit" class="text-sm font-semibold text-accent-hover hover:underline">{{ __('support.two_factor.use_email') }}</button>
                </form>
            @endif
        @endif

        <x-slot:footer>
            <a href="{{ route('login') }}" class="font-semibold text-white hover:text-accent">{{ __('support.two_factor.back_to_login') }}</a>
        </x-slot:footer>
    </x-auth-card>
</x-layouts.app>
