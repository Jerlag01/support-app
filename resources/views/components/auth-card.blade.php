@props(['title', 'eyebrow' => null])

<div class="relative overflow-hidden bg-gradient-to-br from-navy-950 to-navy-700 px-4 py-16 sm:py-24">
    <div class="bg-grid absolute inset-0" aria-hidden="true"></div>
    <div class="relative mx-auto max-w-md">
        <div class="rounded-lg bg-white p-8 shadow-2xl sm:p-10">
            @if ($eyebrow)
                <p class="eyebrow">{{ $eyebrow }}</p>
            @endif
            <h1 class="mt-2 text-2xl font-extrabold tracking-tight">{{ $title }}</h1>
            <x-flash class="mt-6" />
            <div class="mt-8">{{ $slot }}</div>
        </div>
        @isset($footer)
            <p class="mt-6 text-center text-sm text-white/70">{{ $footer }}</p>
        @endisset
    </div>
</div>
