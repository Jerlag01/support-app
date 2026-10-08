<?php

namespace App\Actions;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketAssigned;
use Illuminate\Support\Facades\Notification;

class AssignTicket
{
    /**
     * Assign to an agent and/or a team and tell whoever got it (except the agent doing the assigning).
     */
    public function handle(Ticket $ticket, ?int $agentId, ?int $teamId, User $assignedBy): void
    {
        $ticket->update(['assigned_to' => $agentId, 'team_id' => $teamId]);

        $recipients = collect();

        if ($ticket->wasChanged('assigned_to') && $ticket->assignee) {
            $recipients->push($ticket->assignee);
        }

        if ($ticket->wasChanged('team_id') && $ticket->team) {
            $recipients = $recipients->merge($ticket->team->members()->where('is_active', true)->get());
        }

        $recipients = $recipients->unique('id')->reject(fn (User $user): bool => $user->is($assignedBy));

        Notification::send($recipients, new TicketAssigned($ticket));
    }
}
