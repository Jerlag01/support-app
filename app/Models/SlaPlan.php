<?php

namespace App\Models;

use Database\Factories\SlaPlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

#[Fillable(['name', 'grace_hours', 'is_active', 'notes'])]
class SlaPlan extends Model
{
    /** @use HasFactory<SlaPlanFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'grace_hours' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function dueFrom(Carbon $start): Carbon
    {
        return $start->copy()->addHours($this->grace_hours);
    }
}
