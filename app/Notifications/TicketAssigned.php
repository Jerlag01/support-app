<?php

namespace App\Notifications;

use App\Filament\Agent\Resources\Tickets\TicketResource;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to an agent (or the members of a team) when a ticket is assigned to them.
 */
class TicketAssigned extends Notification
{
    use Queueable;

    public function __construct(public Ticket $ticket) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $ticket = $this->ticket;

        return (new MailMessage)
            ->subject(__('support.mail.assigned.subject', ['reference' => $ticket->reference, 'subject' => $ticket->subject]))
            ->greeting(__('support.mail.greeting', ['name' => $notifiable->name]))
            ->line(__('support.mail.assigned.intro', ['client' => $ticket->user->displayName()]))
            ->line('**'.$ticket->subject.'** · '.$ticket->priority->getLabel())
            ->action(__('support.mail.view_ticket'), TicketResource::getUrl('view', ['record' => $ticket], panel: 'agent'));
    }
}
