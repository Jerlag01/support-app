<?php

namespace App\Filament\Agent\Resources\Tickets;

use App\Enums\AdminNavigationGroup;
use App\Filament\Agent\Resources\Tickets\Pages\CreateTicket;
use App\Filament\Agent\Resources\Tickets\Pages\EditTicket;
use App\Filament\Agent\Resources\Tickets\Pages\ListTickets;
use App\Filament\Agent\Resources\Tickets\Pages\ViewTicket;
use App\Filament\Agent\Resources\Tickets\Schemas\TicketForm;
use App\Filament\Agent\Resources\Tickets\Schemas\TicketInfolist;
use App\Filament\Agent\Resources\Tickets\Tables\TicketsTable;
use App\Models\Ticket;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLifebuoy;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Tickets;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function getModelLabel(): string
    {
        return __('admin.resources.ticket.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.ticket.plural');
    }

    /**
     * Agents only ever see tickets of their departments, teams or assigned to them.
     *
     * @return Builder<Ticket>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->visibleTo(auth()->user());
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['reference', 'subject', 'user.name', 'user.company', 'user.email'];
    }

    public static function getNavigationBadge(): ?string
    {
        $overdue = static::getEloquentQuery()->overdue()->count();

        return $overdue > 0 ? (string) $overdue : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return __('admin.tabs.overdue');
    }

    public static function form(Schema $schema): Schema
    {
        return TicketForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TicketInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TicketsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTickets::route('/'),
            'create' => CreateTicket::route('/create'),
            'view' => ViewTicket::route('/{record}'),
            'edit' => EditTicket::route('/{record}/edit'),
        ];
    }
}
