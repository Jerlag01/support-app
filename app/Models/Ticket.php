<?php

namespace App\Models;

use App\Enums\TicketEventType;
use App\Enums\TicketPriority;
use App\Enums\TicketSource;
use App\Enums\TicketStatus;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Storage;

#[Fillable(['user_id', 'department_id', 'help_topic_id', 'sla_plan_id', 'assigned_to', 'team_id', 'subject', 'priority', 'status', 'is_answered', 'due_at', 'site_address', 'source', 'last_activity_at', 'closed_at'])]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    protected $attributes = [
        'priority' => 'normal',
        'status' => 'open',
        'source' => 'web',
        'is_answered' => false,
    ];

    protected function casts(): array
    {
        return [
            'priority' => TicketPriority::class,
            'status' => TicketStatus::class,
            'source' => TicketSource::class,
            'is_answered' => 'boolean',
            'due_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket): void {
            $ticket->last_activity_at ??= now();
            $ticket->due_at ??= $ticket->slaPlan?->dueFrom(now());
        });

        static::created(function (Ticket $ticket): void {
            $ticket->forceFill(['reference' => sprintf('PI-%06d', $ticket->id)])->saveQuietly();
            $ticket->recordEvent(TicketEventType::Created, ['source' => $ticket->source->value]);
        });

        static::saving(function (Ticket $ticket): void {
            if ($ticket->isDirty('status')) {
                $ticket->closed_at = $ticket->status->isFinal() ? ($ticket->closed_at ?? now()) : null;

                // Reopening starts a new SLA period.
                $wasFinal = $ticket->getOriginal('status')?->isFinal();
                if ($ticket->exists && $wasFinal && ! $ticket->status->isFinal()) {
                    $ticket->due_at = $ticket->slaPlan?->dueFrom(now());
                }
            }

            if ($ticket->isDirty('sla_plan_id') && $ticket->exists && ! $ticket->isDirty('due_at')) {
                $ticket->due_at = $ticket->slaPlan()->first()?->dueFrom($ticket->created_at);
            }
        });

        static::updated(fn (Ticket $ticket) => $ticket->recordChangeEvents());

        static::deleted(function (Ticket $ticket): void {
            Storage::disk('local')->deleteDirectory($ticket->attachmentDirectory());
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo<HelpTopic, $this>
     */
    public function helpTopic(): BelongsTo
    {
        return $this->belongsTo(HelpTopic::class);
    }

    /**
     * @return BelongsTo<SlaPlan, $this>
     */
    public function slaPlan(): BelongsTo
    {
        return $this->belongsTo(SlaPlan::class);
    }

    /**
     * @return HasMany<TicketMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->oldest()->oldest('id');
    }

    /**
     * Messages the client is allowed to see (internal notes excluded).
     *
     * @return HasMany<TicketMessage, $this>
     */
    public function publicMessages(): HasMany
    {
        return $this->messages()->where('is_internal', false);
    }

    /**
     * @return HasMany<TicketEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(TicketEvent::class)->oldest()->oldest('id');
    }

    /**
     * @return HasManyThrough<TicketAttachment, TicketMessage, $this>
     */
    public function attachments(): HasManyThrough
    {
        return $this->hasManyThrough(TicketAttachment::class, TicketMessage::class);
    }

    /**
     * @param  Builder<Ticket>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', TicketStatus::active());
    }

    /**
     * @param  Builder<Ticket>  $query
     */
    public function scopeOverdue(Builder $query): void
    {
        $query->active()->whereNotNull('due_at')->where('due_at', '<', now());
    }

    /**
     * Tickets an agent may work on: their departments, plus anything assigned to them or their teams.
     *
     * @param  Builder<Ticket>  $query
     */
    public function scopeVisibleTo(Builder $query, User $agent): void
    {
        if ($agent->hasAccessToAllDepartments()) {
            return;
        }

        $query->where(fn (Builder $query) => $query
            ->whereIn('department_id', $agent->departments()->select('departments.id'))
            ->orWhere('assigned_to', $agent->id)
            ->orWhereIn('team_id', $agent->teams()->select('teams.id')));
    }

    public function isClosed(): bool
    {
        return $this->status->isFinal();
    }

    public function isOverdue(): bool
    {
        return ! $this->isClosed() && $this->due_at?->isPast() === true;
    }

    public function attachmentDirectory(): string
    {
        return "tickets/{$this->id}";
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function recordEvent(TicketEventType $type, array $data = []): TicketEvent
    {
        return $this->events()->create([
            'user_id' => auth()->id(),
            'type' => $type,
            'data' => $data,
        ]);
    }

    private function recordChangeEvents(): void
    {
        if ($this->wasChanged('assigned_to') && $this->assigned_to) {
            $this->recordEvent(TicketEventType::Assigned, ['agent' => $this->assignee()->value('name')]);
        }

        if ($this->wasChanged('team_id') && $this->team_id) {
            $this->recordEvent(TicketEventType::TeamAssigned, ['team' => $this->team()->value('name')]);
        }

        if ($this->wasChanged('department_id') && $this->department_id) {
            $this->recordEvent(TicketEventType::Transferred, ['department' => $this->department()->value('name')]);
        }

        if ($this->wasChanged('status')) {
            $this->recordEvent(TicketEventType::StatusChanged, ['status' => $this->status->value]);
        }

        if ($this->wasChanged('priority')) {
            $this->recordEvent(TicketEventType::PriorityChanged, ['priority' => $this->priority->value]);
        }
    }
}
