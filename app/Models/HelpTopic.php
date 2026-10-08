<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Models\Concerns\HasTranslations;
use Database\Factories\HelpTopicFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'icon', 'department_id', 'sla_plan_id', 'default_priority', 'is_public', 'is_active', 'sort_order'])]
class HelpTopic extends Model
{
    /** @use HasFactory<HelpTopicFactory> */
    use HasFactory, HasTranslations;

    /**
     * Icons available in the client portal's icon set.
     */
    public const ICONS = ['bolt', 'wrench', 'panel', 'file', 'receipt', 'chat', 'shield', 'light', 'solar', 'network'];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
            'default_priority' => TicketPriority::class,
            'is_public' => 'boolean',
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
     * @return BelongsTo<SlaPlan, $this>
     */
    public function slaPlan(): BelongsTo
    {
        return $this->belongsTo(SlaPlan::class);
    }

    /**
     * @return HasMany<Ticket, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Topics clients can choose in the portal.
     *
     * @param  Builder<HelpTopic>  $query
     */
    public function scopeAvailableToClients(Builder $query): void
    {
        $query->where('is_active', true)->where('is_public', true)->orderBy('sort_order')->orderBy('id');
    }

    public function label(): string
    {
        return $this->translate('name');
    }
}
