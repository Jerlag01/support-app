<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TicketStatus: string implements HasColor, HasLabel
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case WaitingOnCustomer = 'waiting_on_customer';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function getLabel(): string
    {
        return __('support.statuses.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Open => 'warning',
            self::InProgress => 'info',
            self::WaitingOnCustomer => 'primary',
            self::Resolved => 'success',
            self::Closed => 'gray',
        };
    }

    /**
     * Tailwind classes for the status badge in the customer portal.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Open => 'bg-amber-100 text-amber-800',
            self::InProgress => 'bg-sky-100 text-sky-800',
            self::WaitingOnCustomer => 'bg-accent/15 text-accent-hover',
            self::Resolved => 'bg-emerald-100 text-emerald-800',
            self::Closed => 'bg-steel text-slate',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Resolved, self::Closed], true);
    }

    /**
     * @return list<self>
     */
    public static function active(): array
    {
        return [self::Open, self::InProgress, self::WaitingOnCustomer];
    }
}
