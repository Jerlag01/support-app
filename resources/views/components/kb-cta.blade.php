<div {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-lg bg-navy-800 px-6 py-8 text-white sm:px-10']) }}>
    <div class="bg-grid absolute inset-0" aria-hidden="true"></div>
    <div class="relative flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-extrabold">{{ __('support.kb.cta_title') }}</h2>
            <p class="mt-1 text-sm text-white/75">{{ __('support.kb.cta_text') }}</p>
        </div>
        <a href="{{ auth()->check() ? route('tickets.create') : route('login') }}" class="btn btn-primary">{{ __('support.nav.new_ticket') }} <x-site-icon name="arrow-right" class="h-4 w-4" /></a>
    </div>
</div>
