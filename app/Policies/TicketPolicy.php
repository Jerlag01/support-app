<?php

namespace App\Policies;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Support\HelpdeskSettings;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $ticket->user_id === $user->id || $this->agentCanAccess($user, $ticket);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $this->agentCanAccess($user, $ticket);
    }

    public function reply(User $user, Ticket $ticket): bool
    {
        if ($user->isStaff()) {
            return $this->agentCanAccess($user, $ticket);
        }

        return $ticket->user_id === $user->id && $ticket->status !== TicketStatus::Closed;
    }

    public function close(User $user, Ticket $ticket): bool
    {
        return $ticket->user_id === $user->id && $ticket->status !== TicketStatus::Closed;
    }

    public function reopen(User $user, Ticket $ticket): bool
    {
        return $ticket->user_id === $user->id
            && $ticket->isClosed()
            && app(HelpdeskSettings::class)->get('clients_can_reopen');
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }

    private function agentCanAccess(User $user, Ticket $ticket): bool
    {
        if (! $user->isStaff()) {
            return false;
        }

        if ($user->hasAccessToAllDepartments() || $ticket->assigned_to === $user->id) {
            return true;
        }

        return $user->departments()->whereKey($ticket->department_id)->exists()
            || ($ticket->team_id && $user->teams()->whereKey($ticket->team_id)->exists());
    }
}
