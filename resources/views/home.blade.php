@inject('helpdesk', 'App\Support\HelpdeskSettings')

@php
    $topics = \App\Models\HelpTopic::availableToClients()->get();
    $faqCategories = $helpdesk->get('show_knowledge_base')
        ? \App\Models\FaqCategory::public()->withCount(['faqs' => fn ($query) => $query->where('is_published', true)])->get()->where('faqs_count', '>', 0)
        : collect();
@endphp

<x-layouts.app>
    {{-- Hero --}}
    <section class="relative overflow-hidden bg-navy-950 text-white">
        @if ($heroImage = helpdesk()->heroImageUrl())
            {{-- Custom background picture with a navy overlay, so the slogan stays readable. --}}
            <img src="{{ $heroImage }}" alt="" class="absolute inset-0 h-full w-full object-cover" fetchpriority="high" data-hero-image>
            <div class="absolute inset-0 bg-gradient-to-br from-navy-950/90 via-navy-900/75 to-navy-800/50" aria-hidden="true"></div>
        @else
            <div class="absolute inset-0 bg-gradient-to-br from-navy-950 via-navy-800 to-navy-700" aria-hidden="true"></div>
            <div class="bg-grid absolute inset-0" aria-hidden="true"></div>
            <x-logo class="absolute top-1/2 -right-32 hidden h-[560px] w-[560px] -translate-y-1/2 opacity-[0.04] lg:block" />
        @endif
        <div class="absolute top-[15%] right-14 hidden h-28 w-px bg-accent/40 lg:block" aria-hidden="true"></div>

        <div class="relative mx-auto grid max-w-7xl items-center gap-14 px-4 py-20 sm:px-6 lg:grid-cols-[1.3fr_1fr] lg:px-8 lg:py-28">
            <div>
                <span class="inline-flex items-center gap-3 rounded-full border border-accent/25 bg-accent/10 px-5 py-2 font-mono text-xs tracking-wider">
                    <span class="h-2 w-2 animate-pulse rounded-full bg-accent"></span>
                    {{ __('support.home.badge') }}
                </span>
                <h1 class="mt-8 text-4xl leading-[1.05] font-extrabold tracking-tight sm:text-5xl lg:text-6xl">
                    {{ __('support.home.title_start') }} <span class="text-accent">{{ __('support.home.title_highlight') }}</span>
                </h1>
                <p class="mt-8 max-w-xl text-lg leading-relaxed text-white/75">{{ __('support.home.intro', ['company' => helpdesk()->companyName()]) }}</p>
                <div class="mt-10 flex flex-wrap gap-4">
                    @auth
                        <a href="{{ route('tickets.create') }}" class="btn btn-primary px-8 py-4">
                            {{ __('support.nav.new_ticket') }} <x-site-icon name="arrow-right" class="h-4 w-4" />
                        </a>
                        <a href="{{ route('tickets.index') }}" class="btn btn-outline-light px-8 py-4">{{ __('support.nav.tickets') }}</a>
                    @else
                        <a href="{{ route('register') }}" class="btn btn-primary px-8 py-4">
                            {{ __('support.home.cta_register') }} <x-site-icon name="arrow-right" class="h-4 w-4" />
                        </a>
                        <a href="{{ route('login') }}" class="btn btn-outline-light px-8 py-4">{{ __('support.nav.login') }}</a>
                    @endauth
                </div>
            </div>

            <div class="rounded-lg border border-white/10 bg-white/5 p-8 backdrop-blur-md">
                <p class="eyebrow">{{ __('support.home.how_title') }}</p>
                <ol class="mt-6 divide-y divide-white/10">
                    @foreach (__('support.home.steps') as $step)
                        <li class="flex gap-5 py-5">
                            <span class="font-mono text-2xl font-semibold text-accent">{{ sprintf('%02d', $loop->iteration) }}</span>
                            <span>
                                <span class="block font-semibold">{{ $step['title'] }}</span>
                                <span class="mt-1 block text-sm text-white/60">{{ $step['text'] }}</span>
                            </span>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </section>

    {{-- Categories --}}
    <section class="bg-white py-20 lg:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl" data-reveal>
                <p class="eyebrow">{{ __('support.home.categories_eyebrow') }}</p>
                <h2 class="mt-3 text-3xl font-extrabold tracking-tight sm:text-4xl">{{ __('support.home.categories_title') }}</h2>
            </div>

            <div class="mt-12 grid gap-px overflow-hidden rounded-lg border border-line bg-line sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($topics as $topic)
                    <a href="{{ auth()->check() ? route('tickets.create', ['topic' => $topic->id]) : route('login') }}"
                       class="group relative bg-white p-8 transition hover:bg-steel" data-reveal>
                        <span class="font-mono text-xs font-semibold text-slate">{{ sprintf('%02d', $loop->iteration) }}</span>
                        <x-site-icon :name="$topic->icon" class="mt-6 h-8 w-8 text-accent" />
                        <h3 class="mt-5 text-lg font-bold">{{ $topic->label() }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate">{{ $topic->translate('description') }}</p>
                        <x-site-icon name="arrow-right" class="absolute top-8 right-8 h-5 w-5 text-line transition group-hover:translate-x-1 group-hover:text-accent" />
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Knowledge base --}}
    @if ($faqCategories->isNotEmpty())
        <section class="border-t border-line bg-steel py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between" data-reveal>
                    <div class="max-w-2xl">
                        <p class="eyebrow">{{ __('support.kb.eyebrow') }}</p>
                        <h2 class="mt-3 text-3xl font-extrabold tracking-tight sm:text-4xl">{{ __('support.kb.home_title') }}</h2>
                        <p class="mt-4 text-slate">{{ __('support.kb.home_text') }}</p>
                    </div>
                    <a href="{{ route('kb.index') }}" class="btn btn-outline">{{ __('support.kb.browse') }} <x-site-icon name="arrow-right" class="h-4 w-4" /></a>
                </div>
                <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($faqCategories as $category)
                        <a href="{{ route('kb.category', $category) }}" class="card group flex items-center gap-4 p-6 transition hover:border-accent" data-reveal>
                            <span class="flex h-11 w-11 items-center justify-center rounded bg-navy-800 text-accent"><x-site-icon name="book" class="h-5 w-5" /></span>
                            <span>
                                <span class="block font-bold group-hover:text-accent-hover">{{ $category->translate('name') }}</span>
                                <span class="text-sm text-slate">{{ trans_choice('support.kb.articles', $category->faqs_count) }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Urgent banner --}}
    <section class="relative overflow-hidden bg-accent text-white">
        <div class="bg-grid absolute inset-0 opacity-50" aria-hidden="true"></div>
        <div class="relative mx-auto flex max-w-7xl flex-col items-start gap-6 px-4 py-14 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
            <div>
                <h2 class="text-2xl font-extrabold tracking-tight sm:text-3xl">{{ __('support.home.urgent_title') }}</h2>
                <p class="mt-3 max-w-2xl text-white/90">{{ __('support.home.urgent_text') }}</p>
            </div>
            @if (filled(helpdesk()->get('branding.phone')))
                <a href="tel:{{ preg_replace('/[^+\d]/', '', helpdesk()->get('branding.phone')) }}" class="btn bg-navy-800 text-white hover:bg-navy-900">
                    <x-site-icon name="phone" class="h-4 w-4" /> {{ helpdesk()->get('branding.phone') }}
                </a>
            @else
                <a href="{{ auth()->check() ? route('tickets.create', ['priority' => 'urgent']) : route('login') }}" class="btn bg-navy-800 text-white hover:bg-navy-900">
                    {{ __('support.home.urgent_button') }} <x-site-icon name="arrow-right" class="h-4 w-4" />
                </a>
            @endif
        </div>
    </section>
</x-layouts.app>
