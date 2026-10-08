@props(['eyebrow', 'title'])

<section class="relative overflow-hidden bg-gradient-to-br from-navy-950 to-navy-700 text-white">
    <div class="bg-grid absolute inset-0" aria-hidden="true"></div>
    <div class="relative mx-auto flex max-w-7xl flex-col gap-6 px-4 py-12 sm:px-6 lg:flex-row lg:items-end lg:justify-between lg:px-8 lg:py-16">
        <div>
            <p class="eyebrow">{{ $eyebrow }}</p>
            <h1 class="mt-3 max-w-3xl text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $title }}</h1>
            @if ($slot->isNotEmpty())
                <div class="mt-4 max-w-2xl text-white/75">{{ $slot }}</div>
            @endif
        </div>
        @isset($actions)
            <div class="flex flex-wrap gap-3">{{ $actions }}</div>
        @endisset
    </div>
</section>
