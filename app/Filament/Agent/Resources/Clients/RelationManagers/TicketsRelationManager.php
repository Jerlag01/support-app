<?php

namespace App\Filament\Agent\Resources\Clients\RelationManagers;

use App\Filament\Agent\Resources\Tickets\TicketResource;
use App\Models\Ticket;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TicketsRelationManager extends RelationManager
{
    protected static string $relationship = 'tickets';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.resources.ticket.plural');
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->visibleTo(auth()->user()))
            ->defaultSort('last_activity_at', 'desc')
            ->recordUrl(fn (Ticket $record): string => TicketResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('reference')
                    ->label(__('admin.fields.reference'))
                    ->fontFamily('mono'),
                TextColumn::make('subject')
                    ->label(__('admin.fields.subject'))
                    ->limit(60),
                TextColumn::make('status')
                    ->label(__('admin.fields.status'))
                    ->badge(),
                TextColumn::make('priority')
                    ->label(__('admin.fields.priority'))
                    ->badge(),
                TextColumn::make('last_activity_at')
                    ->label(__('admin.fields.last_activity'))
                    ->since(),
            ]);
    }
}
