@inject('helpdesk', 'App\Support\HelpdeskSettings')

@php
    $links = array_values(array_filter([
        $helpdesk->get('show_knowledge_base') ? ['route' => 'kb.index', 'label' => __('support.nav.kb'), 'active' => ['kb.*']] : null,
        \App\Models\Employee::pageIsAvailable() ? ['route' => 'who-is-who', 'label' => __('support.nav.who_is_who'), 'active' => ['who-is-who']] : null,
        auth()->check() ? ['route' => 'tickets.index', 'label' => __('support.nav.tickets'), 'active' => ['tickets.index', 'tickets.show']] : null,
        auth()->check() ? ['route' => 'profile.edit', 'label' => __('support.nav.profile'), 'active' => ['profile.*']] : null,
    ]));
    $canRegister = $helpdesk->get('allow_registration');
@endphp

<header data-header class="fixed inset-x-0 top-0 z-40 border-b border-line bg-white/95 backdrop-blur transition-shadow [&.is-scrolled]:shadow-md">
    <div class="mx-auto flex h-[72px] max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <a href="{{ route('home') }}" class="flex items-center gap-3" aria-label="{{ helpdesk()->companyName() }} - {{ __('support.nav.home') }}">
            <x-logo class="h-10 w-10" />
            <span class="leading-tight">
                <span class="block text-lg font-extrabold tracking-tight">{{ helpdesk()->companyName() }}</span>
                <span class="block font-mono text-[10px] tracking-[0.25em] text-slate uppercase">{{ helpdesk()->translated('branding.tagline') ?? __('support.nav.tagline') }}</span>
            </span>
        </a>

        <nav class="hidden items-center gap-6 lg:flex" aria-label="{{ __('support.nav.main') }}">
            @foreach ($links as $link)
                <a href="{{ route($link['route']) }}"
                   @class([
                       'relative py-2 text-sm font-semibold transition hover:text-accent',
                       'text-accent after:absolute after:inset-x-0 after:-bottom-px after:h-0.5 after:bg-accent' => request()->routeIs(...$link['active']),
                   ])
                   @if (request()->routeIs(...$link['active'])) aria-current="page" @endif>
                    {{ $link['label'] }}
                </a>
            @endforeach
            <x-language-switcher />
            @auth
                @if (auth()->user()->isStaff())
                    <a href="{{ url('/agent') }}" class="text-sm font-semibold transition hover:text-accent">{{ __('support.nav.agent_panel') }}</a>
                @endif
                <a href="{{ route('tickets.create') }}" class="btn btn-primary py-2.5">
                    <x-site-icon name="plus" class="h-4 w-4" /> {{ __('support.nav.new_ticket') }}
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded p-2 text-slate transition hover:text-accent" title="{{ __('support.nav.logout') }}">
                        <x-site-icon name="logout" class="h-5 w-5" />
                        <span class="sr-only">{{ __('support.nav.logout') }}</span>
                    </button>
                </form>
            @else
                @if ($canRegister)
                    <a href="{{ route('login') }}" class="text-sm font-semibold transition hover:text-accent">{{ __('support.nav.login') }}</a>
                    <a href="{{ route('register') }}" class="btn btn-primary py-2.5">{{ __('support.nav.register') }}</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary py-2.5">{{ __('support.nav.login') }}</a>
                @endif
            @endauth
        </nav>

        <button type="button" data-nav-toggle aria-expanded="false" aria-controls="mobile-menu" class="rounded p-2 lg:hidden">
            <span class="sr-only">{{ __('support.nav.menu') }}</span>
            <x-site-icon name="menu" class="h-6 w-6" />
        </button>
    </div>

    <nav id="mobile-menu" data-nav-menu class="hidden border-t border-line bg-white px-4 pb-6 lg:hidden" aria-label="{{ __('support.nav.mobile') }}">
        @foreach ($links as $link)
            <a href="{{ route($link['route']) }}" @class(['block border-b border-line py-3 font-semibold', 'text-accent' => request()->routeIs(...$link['active'])])>
                {{ $link['label'] }}
            </a>
        @endforeach
        <x-language-switcher class="mt-4" />
        @auth
            <a href="{{ route('tickets.create') }}" class="btn btn-primary mt-4 w-full">{{ __('support.nav.new_ticket') }}</a>
            <form method="POST" action="{{ route('logout') }}" class="mt-2">
                @csrf
                <button type="submit" class="btn btn-ghost w-full">{{ __('support.nav.logout') }}</button>
            </form>
        @else
            <a href="{{ route('login') }}" class="btn btn-outline mt-4 w-full">{{ __('support.nav.login') }}</a>
            @if ($canRegister)
                <a href="{{ route('register') }}" class="btn btn-primary mt-2 w-full">{{ __('support.nav.register') }}</a>
            @endif
        @endauth
    </nav>
</header>
