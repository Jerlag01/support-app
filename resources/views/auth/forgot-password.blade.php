<x-layouts.app :title="__('support.auth.forgot_title')">
    <x-auth-card :title="__('support.auth.forgot_title')">
        <p class="-mt-4 mb-6 text-sm text-slate">{{ __('support.auth.forgot_intro') }}</p>
        <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
            @csrf
            <x-form-field name="email" type="email" :label="__('support.fields.email')" required autofocus autocomplete="username" />
            <button type="submit" class="btn btn-primary w-full">{{ __('support.auth.send_link') }}</button>
        </form>

        <x-slot:footer>
            <a href="{{ route('login') }}" class="font-semibold text-white hover:text-accent">{{ __('support.auth.back_to_login') }}</a>
        </x-slot:footer>
    </x-auth-card>
</x-layouts.app>
