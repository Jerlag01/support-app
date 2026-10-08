<x-layouts.app :title="__('support.auth.login_title')">
    <x-auth-card :title="__('support.auth.login_title')" :eyebrow="__('support.nav.tagline')">
        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf
            <x-form-field name="email" type="email" :label="__('support.fields.email')" required autofocus autocomplete="username" />
            <x-form-field name="password" type="password" :label="__('support.fields.password')" required autocomplete="current-password" />

            <div class="flex items-center justify-between text-sm">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="remember" class="rounded border-line text-accent focus:ring-accent">
                    {{ __('support.auth.remember') }}
                </label>
                <a href="{{ route('password.request') }}" class="font-semibold text-accent-hover hover:underline">{{ __('support.auth.forgot') }}</a>
            </div>

            <button type="submit" class="btn btn-primary w-full">{{ __('support.nav.login') }}</button>
        </form>

        @if (app(\App\Support\HelpdeskSettings::class)->get('allow_registration'))
            <x-slot:footer>
                {{ __('support.auth.no_account') }}
                <a href="{{ route('register') }}" class="font-semibold text-white hover:text-accent">{{ __('support.nav.register') }}</a>
            </x-slot:footer>
        @endif
    </x-auth-card>
</x-layouts.app>
