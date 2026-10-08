<x-layouts.app :title="__('support.nav.tickets')">
    <x-page-hero :eyebrow="__('support.tickets.eyebrow')" :title="__('support.tickets.index_title', ['name' => auth()->user()->name])">
        {{ __('support.tickets.index_intro') }}
        <x-slot:actions>
            <a href="{{ route('tickets.create') }}" class="btn btn-primary">
                <x-site-icon name="plus" class="h-4 w-4" /> {{ __('support.nav.new_ticket') }}
            </a>
        </x-slot:actions>
    </x-page-hero>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <x-flash class="mb-6" />
        <x-two-factor-reminder class="mb-6" />

        <dl class="grid gap-4 sm:grid-cols-3">
            @foreach (['active' => 'clock', 'waiting' => 'chat', 'closed' => 'check'] as $key => $icon)
                <div class="card flex items-center gap-4 p-5">
                    <span class="flex h-11 w-11 items-center justify-center rounded bg-navy-800 text-accent">
                        <x-site-icon :name="$icon" class="h-5 w-5" />
                    </span>
                    <div>
                        <dt class="text-sm text-slate">{{ __('support.tickets.counts.'.$key) }}</dt>
                        <dd class="font-mono text-2xl font-semibold">{{ $counts[$key] }}</dd>
                    </div>
                </div>
            @endforeach
        </dl>

        <div class="card mt-8">
            <div class="flex flex-col gap-4 border-b border-line p-4 sm:flex-row sm:items-center sm:justify-between">
                <nav class="flex gap-1" aria-label="{{ __('support.tickets.filter') }}">
                    @foreach (['active', 'closed'] as $filter)
                        <a href="{{ route('tickets.index', ['status' => $filter, 'search' => request('search')]) }}"
                           @class([
                               'rounded px-4 py-2 text-sm font-semibold transition',
                               'bg-navy-800 text-white' => $status === $filter,
                               'text-slate hover:bg-steel hover:text-navy-800' => $status !== $filter,
                           ])
                           @if ($status === $filter) aria-current="page" @endif>
                            {{ __('support.tickets.filters.'.$filter) }}
                        </a>
                    @endforeach
                </nav>
                <form method="GET" action="{{ route('tickets.index') }}" class="relative sm:w-72" role="search">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <label for="search" class="sr-only">{{ __('support.tickets.search') }}</label>
                    <x-site-icon name="search" class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate" />
                    <input id="search" name="search" type="search" value="{{ request('search') }}" placeholder="{{ __('support.tickets.search') }}" class="form-input py-2 pl-9 text-sm">
                </form>
            </div>

            @if ($tickets->isEmpty())
                <div class="flex flex-col items-center px-6 py-16 text-center">
                    <x-site-icon name="inbox" class="h-10 w-10 text-line" />
                    <p class="mt-4 font-semibold">{{ __('support.tickets.empty_title') }}</p>
                    <p class="mt-1 text-sm text-slate">{{ __('support.tickets.empty_text') }}</p>
                    <a href="{{ route('tickets.create') }}" class="btn btn-primary mt-6">{{ __('support.nav.new_ticket') }}</a>
                </div>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($tickets as $ticket)
                        <li>
                            <a href="{{ route('tickets.show', $ticket) }}" class="group grid gap-3 px-5 py-4 transition hover:bg-steel sm:grid-cols-[1fr_auto] sm:items-center">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                        <span class="font-mono text-xs font-semibold text-slate">{{ $ticket->reference }}</span>
                                        <span class="text-xs text-slate">{{ $ticket->helpTopic?->label() }}</span>
                                    </div>
                                    <p class="mt-1 truncate font-semibold group-hover:text-accent-hover">{{ $ticket->subject }}</p>
                                </div>
                                <div class="flex flex-wrap items-center gap-3 text-xs text-slate sm:justify-end">
                                    <span class="badge {{ $ticket->priority->badgeClasses() }}">{{ $ticket->priority->getLabel() }}</span>
                                    <x-status-badge :status="$ticket->status" />
                                    <span class="inline-flex items-center gap-1" title="{{ __('support.tickets.messages') }}">
                                        <x-site-icon name="chat" class="h-3.5 w-3.5" /> {{ $ticket->public_messages_count }}
                                    </span>
                                    <time datetime="{{ $ticket->last_activity_at?->toIso8601String() }}" class="w-28 text-right">{{ $ticket->last_activity_at?->diffForHumans() }}</time>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="mt-6">{{ $tickets->links() }}</div>
    </div>
</x-layouts.app>
