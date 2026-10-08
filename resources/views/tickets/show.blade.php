<x-layouts.app :title="$ticket->reference">
    <x-page-hero :eyebrow="$ticket->reference" :title="$ticket->subject">
        <div class="flex flex-wrap items-center gap-3 text-sm">
            <x-status-badge :status="$ticket->status" />
            <span class="badge {{ $ticket->priority->badgeClasses() }}">{{ $ticket->priority->getLabel() }}</span>
            <span>{{ $ticket->helpTopic?->label() }}</span>
        </div>
        <x-slot:actions>
            <a href="{{ route('tickets.index') }}" class="btn btn-outline-light">
                <x-site-icon name="arrow-left" class="h-4 w-4" /> {{ __('support.tickets.back') }}
            </a>
        </x-slot:actions>
    </x-page-hero>

    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-[1fr_320px] lg:px-8">
        <div class="min-w-0 space-y-6">
            <x-flash />

            <ol class="space-y-5" aria-label="{{ __('support.tickets.conversation') }}">
                @foreach ($ticket->publicMessages as $message)
                    @php($fromStaff = $message->isFromStaff())
                    <li @class(['card overflow-hidden', 'border-l-4 border-l-accent' => $fromStaff])>
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line bg-steel/60 px-5 py-3 text-sm">
                            <span class="flex items-center gap-3">
                                @if ($fromStaff)
                                    <x-logo class="h-7 w-7" />
                                @else
                                    <span class="flex h-7 w-7 items-center justify-center rounded bg-navy-800 text-xs font-bold text-white">{{ mb_strtoupper(mb_substr($message->author?->name ?? '?', 0, 1)) }}</span>
                                @endif
                                <span class="font-semibold">{{ $message->author?->name ?? __('support.tickets.deleted_user') }}</span>
                                @if ($fromStaff)
                                    <span class="badge bg-accent/15 text-accent-hover">{{ helpdesk()->companyName() }}</span>
                                @endif
                            </span>
                            <time datetime="{{ $message->created_at->toIso8601String() }}" class="font-mono text-xs text-slate">{{ $message->created_at->format('d/m/Y H:i') }}</time>
                        </div>
                        <div class="px-5 py-5 leading-relaxed break-words whitespace-pre-line">{{ $message->body }}</div>
                        @if ($message->attachments->isNotEmpty())
                            <ul class="flex flex-wrap gap-2 border-t border-line px-5 py-4">
                                @foreach ($message->attachments as $attachment)
                                    <li>
                                        <a href="{{ route('attachments.show', $attachment) }}" class="inline-flex items-center gap-2 rounded border border-line px-3 py-1.5 text-sm transition hover:border-accent hover:text-accent-hover">
                                            <x-site-icon name="paperclip" class="h-4 w-4" />
                                            <span class="max-w-56 truncate">{{ $attachment->original_name }}</span>
                                            <span class="font-mono text-xs text-slate">{{ $attachment->humanSize() }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ol>

            @can('reply', $ticket)
                <form id="reply" method="POST" action="{{ route('tickets.messages.store', $ticket) }}" enctype="multipart/form-data" class="card space-y-5 p-6" data-submit-once>
                    @csrf
                    <h2 class="text-lg font-bold">{{ __('support.tickets.reply_title') }}</h2>
                    @if ($ticket->isClosed())
                        <p class="rounded bg-steel px-4 py-3 text-sm text-slate">{{ __('support.tickets.reply_reopens') }}</p>
                    @endif
                    <x-form-field name="message" type="textarea" :label="__('support.fields.message')" required maxlength="10000" rows="5" />
                    <x-attachments-field />
                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary">{{ __('support.tickets.send') }} <x-site-icon name="arrow-right" class="h-4 w-4" /></button>
                    </div>
                </form>
            @else
                <div class="card flex flex-col items-start gap-4 p-6 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-slate">{{ __('support.tickets.closed_notice') }}</p>
                    @can('reopen', $ticket)
                        <form method="POST" action="{{ route('tickets.reopen', $ticket) }}">
                            @csrf
                            <button type="submit" class="btn btn-outline">{{ __('support.tickets.reopen') }}</button>
                        </form>
                    @endcan
                </div>
            @endcan
        </div>

        <aside class="space-y-6">
            <div class="card p-6">
                <p class="eyebrow">{{ __('support.tickets.details') }}</p>
                <dl class="mt-4 divide-y divide-line text-sm">
                    <div class="flex justify-between gap-4 py-3">
                        <dt class="text-slate">{{ __('support.fields.reference') }}</dt>
                        <dd class="font-mono font-semibold">{{ $ticket->reference }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 py-3">
                        <dt class="text-slate">{{ __('support.fields.status') }}</dt>
                        <dd><x-status-badge :status="$ticket->status" /></dd>
                    </div>
                    @if ($ticket->helpTopic)
                        <div class="flex justify-between gap-4 py-3">
                            <dt class="text-slate">{{ __('support.fields.help_topic_id') }}</dt>
                            <dd class="text-right font-semibold">{{ $ticket->helpTopic->label() }}</dd>
                        </div>
                    @endif
                    @if ($ticket->department?->is_public)
                        <div class="flex justify-between gap-4 py-3">
                            <dt class="text-slate">{{ __('support.fields.department') }}</dt>
                            <dd class="text-right font-semibold">{{ $ticket->department->name }}</dd>
                        </div>
                    @endif
                    @if ($ticket->site_address)
                        <div class="flex justify-between gap-4 py-3">
                            <dt class="text-slate">{{ __('support.fields.site_address') }}</dt>
                            <dd class="text-right font-semibold">{{ $ticket->site_address }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between gap-4 py-3">
                        <dt class="text-slate">{{ __('support.fields.assignee') }}</dt>
                        <dd class="text-right font-semibold">{{ $ticket->assignee?->name ?? __('support.tickets.unassigned') }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 py-3">
                        <dt class="text-slate">{{ __('support.fields.created_at') }}</dt>
                        <dd class="font-mono">{{ $ticket->created_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    @if ($ticket->closed_at)
                        <div class="flex justify-between gap-4 py-3">
                            <dt class="text-slate">{{ __('support.fields.closed_at') }}</dt>
                            <dd class="font-mono">{{ $ticket->closed_at->format('d/m/Y H:i') }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            @can('close', $ticket)
                <form method="POST" action="{{ route('tickets.close', $ticket) }}" class="card p-6">
                    @csrf
                    <p class="text-sm text-slate">{{ __('support.tickets.close_hint') }}</p>
                    <button type="submit" class="btn btn-outline mt-4 w-full">{{ __('support.tickets.close') }}</button>
                </form>
            @endcan
        </aside>
    </div>
</x-layouts.app>
