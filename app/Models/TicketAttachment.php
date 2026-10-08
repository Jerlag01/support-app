<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Number;

#[Fillable(['ticket_message_id', 'disk', 'path', 'original_name', 'mime_type', 'size'])]
class TicketAttachment extends Model
{
    /**
     * @return BelongsTo<TicketMessage, $this>
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(TicketMessage::class, 'ticket_message_id');
    }

    public function humanSize(): string
    {
        return Number::fileSize($this->size, precision: 1);
    }
}
