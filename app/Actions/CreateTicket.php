<?php

namespace App\Actions;

use App\Enums\TicketPriority;
use App\Enums\TicketSource;
use App\Models\Department;
use App\Models\HelpTopic;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketAssigned;
use App\Notifications\TicketCreated;
use App\Support\HelpdeskSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class CreateTicket
{
    public function __construct(private PostTicketMessage $postMessage, private HelpdeskSettings $settings) {}

    /**
     * Open a ticket. Department, SLA and priority follow the help topic unless given explicitly,
     * falling back to the helpdesk defaults (osTicket's routing rules).
     *
     * @param  array{subject: string, message: string, help_topic_id?: ?int, department_id?: ?int, priority?: TicketPriority|string|null, site_address?: ?string, source?: TicketSource|string|null, assigned_to?: ?int, team_id?: ?int}  $data
     * @param  array<int, UploadedFile>  $attachments
     * @param  User|null  $author  Agent opening the ticket on the client's behalf.
     */
    public function handle(User $client, array $data, array $attachments = [], ?User $author = null): Ticket
    {
        $author ??= $client;
        $topic = HelpTopic::find($data['help_topic_id'] ?? null);
        $department = Department::find($data['department_id'] ?? $topic?->department_id) ?? $this->settings->defaultDepartment();
        $slaPlan = $topic?->slaPlan ?? $department?->slaPlan ?? $this->settings->defaultSlaPlan();

        $ticket = DB::transaction(function () use ($client, $data, $attachments, $author, $topic, $department, $slaPlan): Ticket {
            $ticket = $client->tickets()->create([
                'subject' => $data['subject'],
                'help_topic_id' => $topic?->id,
                'department_id' => $department?->id,
                'sla_plan_id' => $slaPlan?->id,
                'priority' => $data['priority'] ?? $topic?->default_priority ?? TicketPriority::Normal,
                'site_address' => $data['site_address'] ?? null,
                'source' => $data['source'] ?? TicketSource::Web,
                'assigned_to' => $data['assigned_to'] ?? null,
                'team_id' => $data['team_id'] ?? null,
            ]);

            $this->postMessage->handle($ticket, $author, $data['message'], $attachments, notify: false);

            return $ticket;
        });

        Notification::send(
            User::withAccessToDepartment($ticket->department_id)->whereKeyNot($author->id)->get(),
            new TicketCreated($ticket),
        );

        if ($ticket->assigned_to && $ticket->assigned_to !== $author->id) {
            $ticket->assignee->notify(new TicketAssigned($ticket));
        }

        return $ticket;
    }
}
