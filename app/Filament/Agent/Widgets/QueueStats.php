<?php

namespace App\Filament\Agent\Widgets;

use App\Filament\Agent\Resources\Tickets\TicketResource;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class QueueStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $tickets = fn () => TicketResource::getEloquentQuery();
        $url = fn (string $tab): string => TicketResource::getUrl('index', ['tab' => $tab]);

        return [
            Stat::make(__('admin.tabs.open'), $tickets()->active()->where('is_answered', false)->count())
                ->icon(Heroicon::OutlinedInbox)
                ->url($url('open')),
            Stat::make(__('admin.tabs.mine'), $tickets()->active()->where('assigned_to', auth()->id())->count())
                ->icon(Heroicon::OutlinedUser)
                ->color('primary')
                ->url($url('mine')),
            Stat::make(__('admin.tabs.unassigned'), $tickets()->active()->whereNull('assigned_to')->whereNull('team_id')->count())
                ->icon(Heroicon::OutlinedUserPlus)
                ->color('warning')
                ->url($url('unassigned')),
            Stat::make(__('admin.tabs.overdue'), $tickets()->overdue()->count())
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color('danger')
                ->url($url('overdue')),
        ];
    }
}
