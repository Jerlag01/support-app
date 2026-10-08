<?php

namespace App\Enums;

use Filament\Support\Icons\Heroicon;

enum TicketEventType: string
{
    case Created = 'created';
    case Assigned = 'assigned';
    case TeamAssigned = 'team_assigned';
    case Transferred = 'transferred';
    case StatusChanged = 'status_changed';
    case PriorityChanged = 'priority_changed';
    case Overdue = 'overdue';

    public function icon(): Heroicon
    {
        return match ($this) {
            self::Created => Heroicon::OutlinedPlusCircle,
            self::Assigned, self::TeamAssigned => Heroicon::OutlinedUserPlus,
            self::Transferred => Heroicon::OutlinedArrowsRightLeft,
            self::StatusChanged => Heroicon::OutlinedArrowPath,
            self::PriorityChanged => Heroicon::OutlinedFlag,
            self::Overdue => Heroicon::OutlinedClock,
        };
    }
}
