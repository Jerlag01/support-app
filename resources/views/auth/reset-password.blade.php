<x-layouts.app :title="__('support.auth.reset_title')">
    <x-auth-card :title="__('support.auth.reset_title')">
        <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            <x-form-field name="email" type="email" :label="__('support.fields.email')" :value="$request->email" required autocomplete="username" />
            <x-form-field name="password" type="password" :label="__('support.fields.password')" required autofocus autocomplete="new-password" />
            <x-form-field name="password_confirmation" type="password" :label="__('support.fields.password_confirmation')" required autocomplete="new-password" />
            <button type="submit" class="btn btn-primary w-full">{{ __('support.auth.reset_button') }}</button>
        </form>
    </x-auth-card>
</x-layouts.app>
