<?php

namespace App\Filament\Agent\Resources\Tickets\Pages;

use App\Enums\TicketStatus;
use App\Filament\Agent\Resources\Tickets\TicketResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

/**
 * osTicket-style queues: open (unanswered), answered, mine, team, unassigned, overdue, closed.
 */
class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $agent = auth()->user();
        $queues = [
            'open' => fn (Builder $query) => $query->active()->where('is_answered', false),
            'answered' => fn (Builder $query) => $query->active()->where('is_answered', true),
            'mine' => fn (Builder $query) => $query->active()->where('assigned_to', $agent->id),
            'team' => fn (Builder $query) => $query->active()->whereIn('team_id', $agent->teams()->select('teams.id')),
            'unassigned' => fn (Builder $query) => $query->active()->whereNull('assigned_to')->whereNull('team_id'),
            'overdue' => fn (Builder $query) => $query->overdue(),
            'closed' => fn (Builder $query) => $query->whereIn('status', [TicketStatus::Resolved, TicketStatus::Closed]),
        ];

        $tabs = collect($queues)->map(fn (callable $scope, string $key): Tab => Tab::make(__("admin.tabs.{$key}"))
            ->modifyQueryUsing($scope)
            ->badge($key === 'closed' ? null : ($scope(TicketResource::getEloquentQuery())->count() ?: null))
            ->badgeColor(match ($key) {
                'overdue' => 'danger',
                'unassigned' => 'warning',
                default => 'gray',
            }))->all();

        return [...$tabs, 'all' => Tab::make(__('admin.tabs.all'))];
    }
}
