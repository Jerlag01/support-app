<x-layouts.app :title="__('support.auth.register_title')">
    <x-auth-card :title="__('support.auth.register_title')" :eyebrow="__('support.nav.tagline')">
        <p class="-mt-4 mb-6 text-sm text-slate">{{ __('support.auth.register_intro') }}</p>
        <form method="POST" action="{{ route('register') }}" class="space-y-5">
            @csrf
            <x-form-field name="name" :label="__('support.fields.name')" required autofocus autocomplete="name" />
            <x-form-field name="company" :label="__('support.fields.company')" autocomplete="organization" />
            <x-form-field name="email" type="email" :label="__('support.fields.email')" required autocomplete="email" />
            <x-form-field name="phone" type="tel" :label="__('support.fields.phone')" autocomplete="tel" />
            <x-form-field name="password" type="password" :label="__('support.fields.password')" required autocomplete="new-password" />
            <x-form-field name="password_confirmation" type="password" :label="__('support.fields.password_confirmation')" required autocomplete="new-password" />

            <button type="submit" class="btn btn-primary w-full">{{ __('support.auth.register_button') }}</button>
        </form>

        <x-slot:footer>
            {{ __('support.auth.has_account') }}
            <a href="{{ route('login') }}" class="font-semibold text-white hover:text-accent">{{ __('support.nav.login') }}</a>
        </x-slot:footer>
    </x-auth-card>
</x-layouts.app>
