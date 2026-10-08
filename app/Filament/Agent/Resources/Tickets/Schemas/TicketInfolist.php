<?php

namespace App\Filament\Agent\Resources\Tickets\Schemas;

use App\Models\Ticket;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TicketInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make(__('admin.sections.thread'))
                    ->schema([
                        ViewEntry::make('thread')
                            ->hiddenLabel()
                            ->view('filament.agent.ticket-thread'),
                    ])
                    ->columnSpan(['lg' => 2]),
                Group::make([
                    Section::make(__('admin.sections.ticket'))
                        ->schema([
                            TextEntry::make('status')
                                ->label(__('admin.fields.status'))
                                ->badge(),
                            TextEntry::make('priority')
                                ->label(__('admin.fields.priority'))
                                ->badge(),
                            TextEntry::make('department.name')
                                ->label(__('admin.fields.department')),
                            TextEntry::make('helpTopic.name')
                                ->label(__('admin.fields.help_topic'))
                                ->state(fn (Ticket $record): ?string => $record->helpTopic?->label())
                                ->placeholder('—'),
                            TextEntry::make('slaPlan.name')
                                ->label(__('admin.fields.sla_plan'))
                                ->placeholder('—'),
                            TextEntry::make('due_at')
                                ->label(__('admin.fields.due_at'))
                                ->dateTime('d/m/Y H:i')
                                ->color(fn (Ticket $record): ?string => $record->isOverdue() ? 'danger' : null)
                                ->helperText(fn (Ticket $record): ?string => $record->isOverdue() ? __('admin.tabs.overdue') : null)
                                ->placeholder('—'),
                            TextEntry::make('source')
                                ->label(__('admin.fields.source'))
                                ->badge()
                                ->color('gray'),
                            TextEntry::make('site_address')
                                ->label(__('admin.fields.site_address'))
                                ->placeholder('—'),
                            TextEntry::make('created_at')
                                ->label(__('admin.fields.created_at'))
                                ->dateTime('d/m/Y H:i'),
                            TextEntry::make('last_activity_at')
                                ->label(__('admin.fields.last_activity'))
                                ->since(),
                        ]),
                    Section::make(__('admin.sections.assignment'))
                        ->schema([
                            TextEntry::make('assignee.name')
                                ->label(__('admin.fields.assignee'))
                                ->placeholder(__('admin.fields.unassigned')),
                            TextEntry::make('team.name')
                                ->label(__('admin.fields.team'))
                                ->placeholder('—'),
                        ]),
                    Section::make(__('admin.sections.client'))
                        ->schema([
                            TextEntry::make('user.name')
                                ->label(__('admin.fields.name')),
                            TextEntry::make('user.organization.name')
                                ->label(__('admin.fields.organization'))
                                ->placeholder(fn (Ticket $record): string => $record->user->company ?? '—'),
                            TextEntry::make('user.email')
                                ->label(__('admin.fields.email'))
                                ->url(fn (Ticket $record): string => "mailto:{$record->user->email}")
                                ->copyable(),
                            TextEntry::make('user.phone')
                                ->label(__('admin.fields.phone'))
                                ->placeholder('—')
                                ->url(fn (?string $state): ?string => $state ? 'tel:'.preg_replace('/[^+\d]/', '', $state) : null),
                            TextEntry::make('user.locale')
                                ->label(__('admin.fields.locale'))
                                ->formatStateUsing(fn (?string $state): string => config("app.locales.{$state}", '—')),
                        ]),
                ])->columnSpan(['lg' => 1]),
            ]);
    }
}
