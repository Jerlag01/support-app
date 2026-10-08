<?php

namespace App\Models;

use App\Enums\TicketEventType;
use App\Enums\TicketPriority;
use App\Enums\TicketSource;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Something that happened to a ticket besides a message (assignment, transfer, status change...).
 * Shown in the agent's ticket thread, like osTicket's thread events.
 */
#[Fillable(['ticket_id', 'user_id', 'type', 'data'])]
class TicketEvent extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'type' => TicketEventType::class,
            'data' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function description(): string
    {
        return __('admin.events.'.$this->type->value, [
            'user' => $this->user?->name ?? __('admin.events.system'),
            ...collect($this->data ?? [])->map(fn (mixed $value, string $key): string => match ($key) {
                'status' => TicketStatus::tryFrom($value)?->getLabel() ?? (string) $value,
                'priority' => TicketPriority::tryFrom($value)?->getLabel() ?? (string) $value,
                'source' => TicketSource::tryFrom($value)?->getLabel() ?? (string) $value,
                default => (string) $value,
            })->all(),
        ]);
    }
}
