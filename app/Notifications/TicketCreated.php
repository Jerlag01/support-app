<?php

namespace App\Notifications;

use App\Filament\Agent\Resources\Tickets\TicketResource;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Sent to staff when a customer opens a new ticket.
 */
class TicketCreated extends Notification
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
        $firstMessage = $ticket->messages()->first();

        return (new MailMessage)
            ->subject(__('support.mail.created.subject', ['reference' => $ticket->reference, 'subject' => $ticket->subject]))
            ->greeting(__('support.mail.greeting', ['name' => $notifiable->name]))
            ->line(__('support.mail.created.intro', ['customer' => $ticket->user->displayName()]))
            ->line('**'.$ticket->subject.'** · '.($ticket->helpTopic?->label() ?? '-').' · '.$ticket->priority->getLabel())
            ->line(Str::limit((string) $firstMessage?->body, 500))
            ->action(__('support.mail.view_ticket'), TicketResource::getUrl('view', ['record' => $ticket], panel: 'agent'));
    }
}
