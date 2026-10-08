<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TicketPriority: string implements HasColor, HasLabel
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Urgent = 'urgent';

    public function getLabel(): string
    {
        return __('support.priorities.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Low => 'gray',
            self::Normal => 'info',
            self::High => 'warning',
            self::Urgent => 'danger',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Low => 'bg-steel text-slate',
            self::Normal => 'bg-sky-50 text-sky-800',
            self::High => 'bg-amber-100 text-amber-800',
            self::Urgent => 'bg-red-100 text-red-800',
        };
    }
}
