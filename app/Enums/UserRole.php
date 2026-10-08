<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasColor, HasLabel
{
    case Customer = 'customer';
    case Agent = 'agent';
    case Admin = 'admin';

    public function getLabel(): string
    {
        return __('support.roles.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Customer => 'gray',
            self::Agent => 'info',
            self::Admin => 'primary',
        };
    }

    public function isStaff(): bool
    {
        return $this !== self::Customer;
    }
}
