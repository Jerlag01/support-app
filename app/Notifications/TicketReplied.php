<?php

namespace App\Notifications;

use App\Filament\Agent\Resources\Tickets\TicketResource;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Sent to the customer when staff answers, or to staff when the customer answers.
 */
class TicketReplied extends Notification
{
    use Queueable;

    public function __construct(public Ticket $ticket, public TicketMessage $message) {}

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
        $url = $notifiable->isStaff()
            ? TicketResource::getUrl('view', ['record' => $ticket], panel: 'agent')
            : route('tickets.show', $ticket);

        return (new MailMessage)
            ->subject(__('support.mail.replied.subject', ['reference' => $ticket->reference, 'subject' => $ticket->subject]))
            ->greeting(__('support.mail.greeting', ['name' => $notifiable->name]))
            ->line(__('support.mail.replied.intro', ['author' => $this->message->author?->name ?? '-']))
            ->line(Str::limit($this->message->body, 1000))
            ->action(__('support.mail.view_ticket'), $url);
    }
}
