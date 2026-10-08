<x-layouts.app :title="__('support.two_factor.app_title')">
    <x-page-hero :eyebrow="__('support.two_factor.eyebrow')" :title="__('support.two_factor.app_title')">
        {{ __('support.two_factor.app_intro') }}
    </x-page-hero>

    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="card space-y-8 p-6 sm:p-8">
            <x-flash />

            <section>
                <h2 class="font-bold">1. {{ __('support.two_factor.app_step_install') }}</h2>
                <p class="mt-2 text-sm text-slate">{{ __('support.two_factor.app_step_install_text') }}</p>
            </section>

            <section>
                <h2 class="font-bold">2. {{ __('support.two_factor.app_step_scan') }}</h2>
                <div class="mt-4 flex flex-col items-start gap-6 sm:flex-row sm:items-center">
                    <img src="{{ $qrCode }}" alt="{{ __('support.two_factor.qr_alt') }}" class="h-48 w-48 rounded border border-line bg-white p-2">
                    <div class="text-sm">
                        <p class="text-slate">{{ __('support.two_factor.app_manual') }}</p>
                        <p class="mt-2 font-mono text-base font-semibold tracking-wider break-all select-all" data-secret>{{ trim(chunk_split($secret, 4, ' ')) }}</p>
                    </div>
                </div>
            </section>

            <section>
                <h2 class="font-bold">3. {{ __('support.two_factor.app_step_confirm') }}</h2>
                <form method="POST" action="{{ route('two-factor.app.store') }}" class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-end">
                    @csrf
                    <x-form-field class="sm:w-64" name="code" :label="__('support.two_factor.code')" required autofocus inputmode="numeric" autocomplete="one-time-code" maxlength="10" />
                    <button type="submit" class="btn btn-primary">{{ __('support.two_factor.enable') }}</button>
                </form>
            </section>

            <a href="{{ route('profile.edit') }}" class="inline-block text-sm font-semibold text-accent-hover hover:underline">{{ __('support.two_factor.cancel') }}</a>
        </div>
    </div>
</x-layouts.app>
