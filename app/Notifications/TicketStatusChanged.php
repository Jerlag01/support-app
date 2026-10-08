<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the customer when staff changes the status of their ticket.
 */
class TicketStatusChanged extends Notification
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
            ->subject(__('support.mail.status.subject', ['reference' => $ticket->reference, 'status' => $ticket->status->getLabel()]))
            ->greeting(__('support.mail.greeting', ['name' => $notifiable->name]))
            ->line(__('support.mail.status.intro', ['subject' => $ticket->subject, 'status' => $ticket->status->getLabel()]))
            ->action(__('support.mail.view_ticket'), route('tickets.show', $ticket));
    }
}
