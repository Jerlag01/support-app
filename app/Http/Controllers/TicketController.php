<?php

namespace App\Http\Controllers;

use App\Actions\CreateTicket;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Requests\StoreTicketRequest;
use App\Models\HelpTopic;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->validate([
            'status' => ['nullable', Rule::in(['active', 'closed'])],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $customer = $request->user();

        $tickets = $customer->tickets()
            ->with('helpTopic')
            ->withCount('publicMessages')
            ->when(($filter['status'] ?? 'active') === 'active', fn ($query) => $query->active())
            ->when(($filter['status'] ?? null) === 'closed', fn ($query) => $query->whereIn('status', [TicketStatus::Resolved, TicketStatus::Closed]))
            ->when($filter['search'] ?? null, fn ($query, string $search) => $query->where(
                fn ($query) => $query->where('subject', 'like', "%{$search}%")->orWhere('reference', 'like', "%{$search}%"),
            ))
            ->latest('last_activity_at')
            ->paginate(15)
            ->withQueryString();

        return view('tickets.index', [
            'tickets' => $tickets,
            'status' => $filter['status'] ?? 'active',
            'counts' => [
                'active' => $customer->tickets()->active()->count(),
                'waiting' => $customer->tickets()->where('status', TicketStatus::WaitingOnCustomer)->count(),
                'closed' => $customer->tickets()->whereIn('status', [TicketStatus::Resolved, TicketStatus::Closed])->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('tickets.create', [
            'topics' => HelpTopic::availableToClients()->get(),
            'priorities' => TicketPriority::cases(),
        ]);
    }

    public function store(StoreTicketRequest $request, CreateTicket $createTicket): RedirectResponse
    {
        $ticket = $createTicket->handle($request->user(), $request->safe()->except('attachments'), $request->file('attachments', []));

        return redirect()->route('tickets.show', $ticket)->with('status', __('support.flash.created', ['reference' => $ticket->reference]));
    }

    public function show(Ticket $ticket): View
    {
        $this->authorize('view', $ticket);

        $ticket->load(['assignee', 'helpTopic', 'department', 'publicMessages.author', 'publicMessages.attachments']);

        return view('tickets.show', ['ticket' => $ticket]);
    }

    public function close(Ticket $ticket): RedirectResponse
    {
        $this->authorize('close', $ticket);

        $ticket->update(['status' => TicketStatus::Closed]);

        return back()->with('status', __('support.flash.closed'));
    }

    public function reopen(Ticket $ticket): RedirectResponse
    {
        $this->authorize('reopen', $ticket);

        $ticket->update(['status' => TicketStatus::Open, 'is_answered' => false, 'last_activity_at' => now()]);

        return back()->with('status', __('support.flash.reopened'));
    }
}
