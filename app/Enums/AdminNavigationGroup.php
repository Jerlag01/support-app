<?php

namespace App\Enums;

use Filament\Navigation\NavigationGroup;
use Filament\Support\Contracts\HasLabel;

enum AdminNavigationGroup implements HasLabel
{
    case Tickets;
    case Users;
    case KnowledgeBase;
    case Helpdesk;
    case Agents;
    case System;

    public function getLabel(): string
    {
        return __('admin.groups.'.str($this->name)->snake());
    }

    /**
     * Panel group order. Labels are closures so they're translated per request, not once at boot.
     *
     * @param  list<self>  $cases
     * @return list<NavigationGroup>
     */
    public static function navigationGroups(array $cases): array
    {
        return array_map(fn (self $case): NavigationGroup => NavigationGroup::make(fn (): string => $case->getLabel()), $cases);
    }
}
