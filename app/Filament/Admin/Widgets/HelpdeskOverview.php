<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HelpdeskOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make(__('admin.stats.open_tickets'), Ticket::active()->count())
                ->description(__('admin.stats.overdue', ['count' => Ticket::overdue()->count()]))
                ->descriptionColor(Ticket::overdue()->exists() ? 'danger' : 'gray')
                ->icon(Heroicon::OutlinedLifebuoy),
            Stat::make(__('admin.stats.created_this_month'), Ticket::where('created_at', '>=', now()->startOfMonth())->count())
                ->icon(Heroicon::OutlinedCalendar),
            Stat::make(__('admin.resources.agent.plural'), User::staff()->count())
                ->description(__('admin.stats.departments', ['count' => Department::count()]))
                ->icon(Heroicon::OutlinedIdentification),
            Stat::make(__('admin.resources.client.plural'), User::where('role', UserRole::Customer)->count())
                ->icon(Heroicon::OutlinedUsers),
        ];
    }
}
