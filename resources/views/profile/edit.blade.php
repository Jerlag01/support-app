<x-layouts.app :title="__('support.nav.profile')">
    <x-page-hero :eyebrow="__('support.nav.profile')" :title="__('support.profile.title')" />

    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <x-flash class="mb-6" />

        <form method="POST" action="{{ route('profile.update') }}" class="card space-y-8 p-6 sm:p-8">
            @csrf
            @method('PUT')

            <fieldset class="grid gap-6 sm:grid-cols-2">
                <legend class="mb-4 text-lg font-bold">{{ __('support.profile.details') }}</legend>
                <x-form-field name="name" :label="__('support.fields.name')" :value="$user->name" required autocomplete="name" />
                <x-form-field name="company" :label="__('support.fields.company')" :value="$user->company" autocomplete="organization" />
                <x-form-field name="email" type="email" :label="__('support.fields.email')" :value="$user->email" required autocomplete="email" />
                <x-form-field name="phone" type="tel" :label="__('support.fields.phone')" :value="$user->phone" autocomplete="tel" />
                <x-form-field name="locale" type="select" :label="__('support.fields.locale')" :hint="__('support.profile.locale_hint')" required>
                    @foreach (config('app.locales') as $code => $language)
                        <option value="{{ $code }}" @selected(old('locale', $user->locale) === $code)>{{ $language }}</option>
                    @endforeach
                </x-form-field>
            </fieldset>

            <fieldset class="grid gap-6 border-t border-line pt-8 sm:grid-cols-2">
                <legend class="mb-4 text-lg font-bold">{{ __('support.profile.password') }}</legend>
                <x-form-field class="sm:col-span-2" name="current_password" type="password" :label="__('support.fields.current_password')" autocomplete="current-password" :hint="__('support.profile.password_hint')" />
                <x-form-field name="password" type="password" :label="__('support.fields.new_password')" autocomplete="new-password" />
                <x-form-field name="password_confirmation" type="password" :label="__('support.fields.password_confirmation')" autocomplete="new-password" />
            </fieldset>

            <div class="flex justify-end border-t border-line pt-6">
                <button type="submit" class="btn btn-primary">{{ __('support.profile.save') }}</button>
            </div>
        </form>

        {{-- Two-step verification: recommended, not required. --}}
        @php
            $hasApp = filled($user->app_authentication_secret);
            $recoveryLeft = $hasApp ? count($user->app_authentication_recovery_codes ?? []) : 0;
        @endphp
        <section id="two-factor" class="card mt-8 scroll-mt-28 space-y-6 p-6 sm:p-8">
            <div>
                <h2 class="text-lg font-bold">{{ __('support.two_factor.section_title') }}</h2>
                <p class="mt-1 text-sm text-slate">{{ __('support.two_factor.section_intro') }}</p>
            </div>

            <x-two-factor-reminder />

            @if (session('recovery_codes'))
                <div class="rounded-lg border border-line bg-steel p-5" data-recovery-codes>
                    <p class="font-semibold">{{ __('support.two_factor.recovery_title') }}</p>
                    <p class="mt-1 text-sm text-slate">{{ __('support.two_factor.recovery_text') }}</p>
                    <ul class="mt-4 grid gap-2 font-mono text-sm sm:grid-cols-2">
                        @foreach (session('recovery_codes') as $code)
                            <li class="rounded bg-white px-3 py-1.5 select-all">{{ $code }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Authenticator app --}}
            <div class="flex flex-col gap-4 border-t border-line pt-6 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="font-semibold">{{ __('support.two_factor.method_app') }}
                        <span @class(['ml-2 rounded px-2 py-0.5 text-xs font-semibold', 'bg-green-100 text-green-800' => $hasApp, 'bg-steel text-slate' => ! $hasApp])>{{ $hasApp ? __('support.two_factor.on') : __('support.two_factor.off') }}</span>
                    </p>
                    <p class="mt-1 text-sm text-slate">{{ __('support.two_factor.method_app_text') }}</p>
                    @if ($hasApp)
                        <p class="mt-1 text-xs text-slate">{{ __('support.two_factor.recovery_left', ['count' => $recoveryLeft]) }}</p>
                    @endif
                </div>
                @unless ($hasApp)
                    <a href="{{ route('two-factor.app.create') }}" class="btn btn-primary shrink-0 py-2">{{ __('support.two_factor.set_up') }}</a>
                @endunless
            </div>
            @if ($hasApp)
                <div class="grid gap-4 sm:grid-cols-2">
                    <form method="POST" action="{{ route('two-factor.recovery-codes') }}" class="space-y-3 rounded border border-line p-4">
                        @csrf
                        <p class="text-sm font-semibold">{{ __('support.two_factor.new_recovery_codes') }}</p>
                        <label for="recovery_password" class="sr-only">{{ __('support.fields.current_password') }}</label>
                        <input id="recovery_password" name="recovery_password" type="password" class="form-input" placeholder="{{ __('support.fields.current_password') }}" autocomplete="current-password" required>
                        @error('recovery_password', 'twoFactorRecovery') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                        <button type="submit" class="btn btn-outline py-2">{{ __('support.two_factor.generate') }}</button>
                    </form>
                    <form method="POST" action="{{ route('two-factor.app.destroy') }}" class="space-y-3 rounded border border-line p-4">
                        @csrf
                        @method('DELETE')
                        <p class="text-sm font-semibold">{{ __('support.two_factor.switch_off') }}</p>
                        <label for="app_password" class="sr-only">{{ __('support.fields.current_password') }}</label>
                        <input id="app_password" name="app_password" type="password" class="form-input" placeholder="{{ __('support.fields.current_password') }}" autocomplete="current-password" required>
                        @error('app_password', 'twoFactorApp') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                        <button type="submit" class="btn btn-ghost py-2 text-red-700">{{ __('support.two_factor.switch_off') }}</button>
                    </form>
                </div>
            @endif

            {{-- E-mail code --}}
            <div class="flex flex-col gap-4 border-t border-line pt-6 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="font-semibold">{{ __('support.two_factor.method_email') }}
                        <span @class(['ml-2 rounded px-2 py-0.5 text-xs font-semibold', 'bg-green-100 text-green-800' => $user->has_email_authentication, 'bg-steel text-slate' => ! $user->has_email_authentication])>{{ $user->has_email_authentication ? __('support.two_factor.on') : __('support.two_factor.off') }}</span>
                    </p>
                    <p class="mt-1 text-sm text-slate">{{ __('support.two_factor.method_email_text', ['email' => $user->email]) }}</p>
                </div>
                @unless ($user->has_email_authentication)
                    <form method="POST" action="{{ route('two-factor.email.send') }}" class="shrink-0">
                        @csrf
                        <button type="submit" class="btn btn-primary py-2">{{ __('support.two_factor.set_up') }}</button>
                    </form>
                @endunless
            </div>
            @if ($user->has_email_authentication)
                <form method="POST" action="{{ route('two-factor.email.destroy') }}" class="space-y-3 rounded border border-line p-4 sm:max-w-sm">
                    @csrf
                    @method('DELETE')
                    <p class="text-sm font-semibold">{{ __('support.two_factor.switch_off') }}</p>
                    <label for="email_password" class="sr-only">{{ __('support.fields.current_password') }}</label>
                    <input id="email_password" name="email_password" type="password" class="form-input" placeholder="{{ __('support.fields.current_password') }}" autocomplete="current-password" required>
                    @error('email_password', 'twoFactorEmail') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    <button type="submit" class="btn btn-ghost py-2 text-red-700">{{ __('support.two_factor.switch_off') }}</button>
                </form>
            @endif
        </section>
    </div>
</x-layouts.app>
