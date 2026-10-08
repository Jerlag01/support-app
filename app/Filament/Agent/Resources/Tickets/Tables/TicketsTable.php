<?php

namespace App\Filament\Agent\Resources\Tickets\Tables;

use App\Enums\TicketPriority;
use App\Enums\TicketSource;
use App\Enums\TicketStatus;
use App\Filament\Agent\Resources\Tickets\TicketResource;
use App\Models\Department;
use App\Models\HelpTopic;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user.organization', 'assignee', 'team', 'department', 'helpTopic'])->withCount('messages'))
            ->defaultSort('last_activity_at', 'desc')
            ->recordUrl(fn (Ticket $record): string => TicketResource::getUrl('view', ['record' => $record]))
            ->recordClasses(fn (Ticket $record): ?string => $record->is_answered ? null : 'font-semibold')
            ->columns([
                TextColumn::make('reference')
                    ->label(__('admin.fields.reference'))
                    ->fontFamily('mono')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('source')
                    ->label('')
                    ->tooltip(fn (Ticket $record): string => $record->source->getLabel())
                    ->toggleable(),
                TextColumn::make('subject')
                    ->label(__('admin.fields.subject'))
                    ->description(fn (Ticket $record): string => $record->user->displayName())
                    ->searchable(['subject'])
                    ->limit(60)
                    ->wrap(),
                TextColumn::make('user.organization.name')
                    ->label(__('admin.fields.organization'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('helpTopic.name')
                    ->label(__('admin.fields.help_topic'))
                    ->state(fn (Ticket $record): ?string => $record->helpTopic?->label())
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('department.name')
                    ->label(__('admin.fields.department'))
                    ->toggleable(),
                TextColumn::make('priority')
                    ->label(__('admin.fields.priority'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('admin.fields.status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('assignee.name')
                    ->label(__('admin.fields.assignee'))
                    ->description(fn (Ticket $record): ?string => $record->team?->name)
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('due_at')
                    ->label(__('admin.fields.due_at'))
                    ->since()
                    ->dateTimeTooltip('d/m/Y H:i')
                    ->color(fn (Ticket $record): ?string => $record->isOverdue() ? 'danger' : null)
                    ->icon(fn (Ticket $record): ?Heroicon => $record->isOverdue() ? Heroicon::OutlinedExclamationTriangle : null)
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('last_activity_at')
                    ->label(__('admin.fields.last_activity'))
                    ->since()
                    ->dateTimeTooltip('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('messages_count')
                    ->label(__('admin.fields.messages'))
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('admin.fields.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.fields.status'))
                    ->options(TicketStatus::class)
                    ->multiple(),
                SelectFilter::make('priority')
                    ->label(__('admin.fields.priority'))
                    ->options(TicketPriority::class)
                    ->multiple(),
                SelectFilter::make('department_id')
                    ->label(__('admin.fields.department'))
                    ->options(fn (): array => Department::orderBy('name')->pluck('name', 'id')->all())
                    ->multiple(),
                SelectFilter::make('help_topic_id')
                    ->label(__('admin.fields.help_topic'))
                    ->options(fn (): array => HelpTopic::orderBy('sort_order')->get()->mapWithKeys(fn (HelpTopic $topic): array => [$topic->id => $topic->label()])->all())
                    ->multiple(),
                SelectFilter::make('assigned_to')
                    ->label(__('admin.fields.assignee'))
                    ->options(fn (): array => User::staff()->orderBy('name')->pluck('name', 'id')->all()),
                SelectFilter::make('team_id')
                    ->label(__('admin.fields.team'))
                    ->options(fn (): array => Team::orderBy('name')->pluck('name', 'id')->all()),
                SelectFilter::make('source')
                    ->label(__('admin.fields.source'))
                    ->options(TicketSource::class),
                Filter::make('overdue')
                    ->label(__('admin.tabs.overdue'))
                    ->toggle()
                    ->query(fn (Builder $query) => $query->overdue()),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
