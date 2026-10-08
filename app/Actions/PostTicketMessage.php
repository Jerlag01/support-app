<?php

namespace App\Actions;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Notifications\TicketReplied;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PostTicketMessage
{
    /**
     * Add a message to the ticket, store its attachments and notify the other party.
     *
     * Attachments are either fresh uploads or files the admin panel already stored on the
     * local disk, given as ['path' => ..., 'name' => ...].
     *
     * @param  array<int, UploadedFile|array{path: string, name: string}>  $attachments
     */
    public function handle(Ticket $ticket, User $author, string $body, array $attachments = [], bool $isInternal = false, ?TicketStatus $newStatus = null, bool $notify = true): TicketMessage
    {
        $isInternal = $isInternal && $author->isStaff();

        $message = DB::transaction(function () use ($ticket, $author, $body, $attachments, $isInternal, $newStatus): TicketMessage {
            $message = $ticket->messages()->create([
                'user_id' => $author->id,
                'body' => trim($body),
                'is_internal' => $isInternal,
            ]);

            foreach ($attachments as $attachment) {
                $this->storeAttachment($ticket, $message, $attachment);
            }

            $ticket->status = $newStatus ?? $this->statusAfterReply($ticket, $author, $isInternal);
            $ticket->last_activity_at = now();

            if (! $isInternal) {
                $ticket->is_answered = $author->isStaff();
            }
            $ticket->save();

            return $message;
        });

        if ($notify && ! $isInternal) {
            $this->notify($ticket, $message, $author);
        }

        return $message;
    }

    private function statusAfterReply(Ticket $ticket, User $author, bool $isInternal): TicketStatus
    {
        if ($isInternal) {
            return $ticket->status;
        }

        if (! $author->isStaff()) {
            // A customer answering always puts the ticket back in the staff queue.
            return TicketStatus::Open;
        }

        return $ticket->status === TicketStatus::Open ? TicketStatus::InProgress : $ticket->status;
    }

    /**
     * @param  UploadedFile|array{path: string, name: string}  $attachment
     */
    private function storeAttachment(Ticket $ticket, TicketMessage $message, UploadedFile|array $attachment): void
    {
        $disk = Storage::disk('local');
        $directory = $ticket->attachmentDirectory();

        if ($attachment instanceof UploadedFile) {
            $path = $attachment->store($directory, 'local');
            $name = $attachment->getClientOriginalName();
        } else {
            $path = $directory.'/'.Str::random(40).'.'.pathinfo($attachment['name'], PATHINFO_EXTENSION);
            $disk->move($attachment['path'], $path);
            $name = $attachment['name'];
        }

        $message->attachments()->create([
            'disk' => 'local',
            'path' => $path,
            'original_name' => Str::limit(basename($name), 250, ''),
            'mime_type' => $disk->mimeType($path) ?: null,
            'size' => $disk->size($path),
        ]);
    }

    private function notify(Ticket $ticket, TicketMessage $message, User $author): void
    {
        if ($author->isStaff()) {
            $ticket->user->notify(new TicketReplied($ticket, $message));

            return;
        }

        Notification::send($this->agentsToNotify($ticket), new TicketReplied($ticket, $message));
    }

    /**
     * The assigned agent, else the assigned team, else everyone with access to the department.
     *
     * @return Collection<int, User>
     */
    private function agentsToNotify(Ticket $ticket): Collection
    {
        if ($ticket->assignee?->is_active) {
            return collect([$ticket->assignee]);
        }

        $teamMembers = $ticket->team?->members()->where('is_active', true)->get();

        return $teamMembers?->isNotEmpty() ? $teamMembers : User::withAccessToDepartment($ticket->department_id)->get();
    }
}
