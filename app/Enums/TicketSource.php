<?php

namespace App\Enums;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum TicketSource: string implements HasIcon, HasLabel
{
    case Web = 'web';
    case Phone = 'phone';
    case Email = 'email';
    case Other = 'other';

    public function getLabel(): string
    {
        return __('admin.sources.'.$this->value);
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Web => Heroicon::OutlinedGlobeAlt,
            self::Phone => Heroicon::OutlinedPhone,
            self::Email => Heroicon::OutlinedEnvelope,
            self::Other => Heroicon::OutlinedEllipsisHorizontalCircle,
        };
    }
}
