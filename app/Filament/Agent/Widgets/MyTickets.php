<?php

namespace App\Filament\Agent\Widgets;

use App\Filament\Agent\Resources\Tickets\TicketResource;
use App\Models\Ticket;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class MyTickets extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('admin.widgets.my_tickets'))
            ->emptyStateHeading(__('admin.widgets.my_tickets_empty'))
            ->query(fn () => Ticket::active()->where('assigned_to', auth()->id())->with('user')->orderByRaw('due_at is null')->orderBy('due_at'))
            ->defaultPaginationPageOption(10)
            ->recordUrl(fn (Ticket $record): string => TicketResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('reference')
                    ->label(__('admin.fields.reference'))
                    ->fontFamily('mono'),
                TextColumn::make('subject')
                    ->label(__('admin.fields.subject'))
                    ->description(fn (Ticket $record): string => $record->user->displayName())
                    ->limit(60),
                TextColumn::make('priority')
                    ->label(__('admin.fields.priority'))
                    ->badge(),
                TextColumn::make('status')
                    ->label(__('admin.fields.status'))
                    ->badge(),
                TextColumn::make('due_at')
                    ->label(__('admin.fields.due_at'))
                    ->since()
                    ->color(fn (Ticket $record): ?string => $record->isOverdue() ? 'danger' : null)
                    ->placeholder('—'),
            ]);
    }
}
