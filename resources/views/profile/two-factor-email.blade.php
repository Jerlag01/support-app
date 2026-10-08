<x-layouts.app :title="__('support.two_factor.email_title')">
    <x-page-hero :eyebrow="__('support.two_factor.eyebrow')" :title="__('support.two_factor.email_title')">
        {{ __('support.two_factor.email_intro', ['email' => $email]) }}
    </x-page-hero>

    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="card space-y-6 p-6 sm:p-8">
            <x-flash />

            <form method="POST" action="{{ route('two-factor.email.store') }}" class="flex flex-col gap-4 sm:flex-row sm:items-end">
                @csrf
                <x-form-field class="sm:w-64" name="code" :label="__('support.two_factor.code')" required autofocus inputmode="numeric" autocomplete="one-time-code" maxlength="10" />
                <button type="submit" class="btn btn-primary">{{ __('support.two_factor.enable') }}</button>
            </form>

            <div class="flex flex-wrap gap-6 text-sm">
                <form method="POST" action="{{ route('two-factor.email.send') }}">
                    @csrf
                    <button type="submit" class="font-semibold text-accent-hover hover:underline">{{ __('support.two_factor.resend') }}</button>
                </form>
                <a href="{{ route('profile.edit') }}" class="font-semibold text-accent-hover hover:underline">{{ __('support.two_factor.cancel') }}</a>
            </div>
        </div>
    </div>
</x-layouts.app>
