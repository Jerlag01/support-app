<?php

namespace App\Models;

use Database\Factories\CannedResponseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['title', 'body', 'department_id', 'is_active'])]
class CannedResponse extends Model
{
    /** @use HasFactory<CannedResponseFactory> */
    use HasFactory;

    /**
     * Placeholders agents can use in a canned response.
     */
    public const PLACEHOLDERS = ['{client}', '{reference}', '{subject}', '{agent}'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Active responses for all departments plus the ticket's own department.
     *
     * @param  Builder<CannedResponse>  $query
     */
    public function scopeUsableFor(Builder $query, Ticket $ticket): void
    {
        $query->where('is_active', true)
            ->where(fn (Builder $query) => $query->whereNull('department_id')->orWhere('department_id', $ticket->department_id))
            ->orderBy('title');
    }

    public function renderFor(Ticket $ticket, User $agent): string
    {
        return strtr($this->body, [
            '{client}' => $ticket->user->name,
            '{reference}' => (string) $ticket->reference,
            '{subject}' => $ticket->subject,
            '{agent}' => $agent->name,
        ]);
    }
}
