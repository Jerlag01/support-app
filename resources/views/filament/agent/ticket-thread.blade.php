@php
    /** @var \App\Models\Ticket $ticket */
    $ticket = $getRecord();

    // Messages and events in one timeline, like osTicket's ticket thread.
    $thread = $ticket->messages()->with(['author', 'attachments'])->get()
        ->concat($ticket->events()->with('user')->get())
        ->sortBy(fn ($item) => sprintf('%s-%d-%010d',
            $item->created_at->format('YmdHis'),
            match (true) {
                $item instanceof \App\Models\TicketMessage => 1,
                $item->type === \App\Enums\TicketEventType::Created => 0,
                default => 2,
            },
            $item->id,
        ));
@endphp

{{-- Filament's precompiled CSS only ships its own classes, so layout here uses inline styles. --}}
<div style="display: flex; flex-direction: column; gap: .875rem;">
    @forelse ($thread as $item)
        @if ($item instanceof \App\Models\TicketEvent)
            <div style="display: flex; align-items: center; gap: .5rem; padding: 0 .25rem; font-size: .8125rem; opacity: .75;">
                <x-filament::icon :icon="$item->type->icon()" style="width: 1rem; height: 1rem; flex-shrink: 0;" />
                <span>{{ $item->description() }}</span>
                <time datetime="{{ $item->created_at->toIso8601String() }}" style="margin-left: auto; font-family: ui-monospace, monospace; font-size: .75rem; white-space: nowrap;">{{ $item->created_at->format('d/m/Y H:i') }}</time>
            </div>
        @else
            @php($fromStaff = $item->isFromStaff())
            <article style="border: 1px solid {{ $item->is_internal ? 'rgb(250 204 21 / .6)' : 'rgb(148 163 184 / .35)' }}; {{ $item->is_internal ? 'background: rgb(250 204 21 / .08);' : '' }} {{ $fromStaff && ! $item->is_internal ? 'border-left: 4px solid #f7941d;' : '' }} border-radius: .75rem; overflow: hidden;">
                <header style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .5rem; padding: .625rem 1rem; border-bottom: 1px solid rgb(148 163 184 / .25);">
                    <div style="display: flex; align-items: center; gap: .5rem; font-weight: 600; font-size: .875rem;">
                        <span>{{ $item->author?->name ?? __('support.tickets.deleted_user') }}</span>
                        @if ($item->is_internal)
                            <x-filament::badge color="warning" size="sm" icon="heroicon-m-lock-closed">{{ __('admin.thread.internal') }}</x-filament::badge>
                        @elseif ($fromStaff)
                            <x-filament::badge color="primary" size="sm">{{ __('admin.thread.agent') }}</x-filament::badge>
                        @else
                            <x-filament::badge color="gray" size="sm">{{ __('admin.thread.client') }}</x-filament::badge>
                        @endif
                    </div>
                    <time datetime="{{ $item->created_at->toIso8601String() }}" title="{{ $item->created_at->diffForHumans() }}" style="font-size: .75rem; opacity: .7; font-family: ui-monospace, monospace;">
                        {{ $item->created_at->format('d/m/Y H:i') }}
                    </time>
                </header>
                <div style="padding: 1rem; white-space: pre-line; overflow-wrap: anywhere; font-size: .875rem; line-height: 1.6;">{{ $item->body }}</div>
                @if ($item->attachments->isNotEmpty())
                    <footer style="display: flex; flex-wrap: wrap; gap: .75rem; padding: .75rem 1rem; border-top: 1px solid rgb(148 163 184 / .25);">
                        @foreach ($item->attachments as $attachment)
                            <x-filament::link :href="route('attachments.show', $attachment)" icon="heroicon-m-paper-clip" size="sm">
                                {{ $attachment->original_name }} ({{ $attachment->humanSize() }})
                            </x-filament::link>
                        @endforeach
                    </footer>
                @endif
            </article>
        @endif
    @empty
        <p style="font-size: .875rem; opacity: .7;">{{ __('admin.thread.empty') }}</p>
    @endforelse
</div>
