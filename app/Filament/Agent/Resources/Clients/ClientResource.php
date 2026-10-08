<?php

namespace App\Filament\Agent\Resources\Clients;

use App\Enums\AdminNavigationGroup;
use App\Enums\UserRole;
use App\Filament\Agent\Resources\Clients\Pages\CreateClient;
use App\Filament\Agent\Resources\Clients\Pages\EditClient;
use App\Filament\Agent\Resources\Clients\Pages\ListClients;
use App\Filament\Agent\Resources\Clients\RelationManagers\TicketsRelationManager;
use App\Filament\Support\ResetTwoFactorAction;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Password;
use UnitEnum;

/**
 * Client accounts of the portal (osTicket's "Users" directory in the agent panel).
 */
class ClientResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'clients';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Users;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('admin.resources.client.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.client.plural');
    }

    /**
     * @return Builder<User>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', UserRole::Customer);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'company', 'email'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('name')
                        ->label(__('admin.fields.name'))
                        ->required()
                        ->maxLength(100),
                    TextInput::make('email')
                        ->label(__('admin.fields.email'))
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    Select::make('organization_id')
                        ->label(__('admin.fields.organization'))
                        ->relationship('organization', 'name')
                        ->searchable()
                        ->preload(),
                    TextInput::make('company')
                        ->label(__('admin.fields.company'))
                        ->maxLength(150),
                    TextInput::make('phone')
                        ->label(__('admin.fields.phone'))
                        ->tel()
                        ->maxLength(50),
                    Select::make('locale')
                        ->label(__('admin.fields.locale'))
                        ->options(config('app.locales'))
                        ->default(config('app.locale'))
                        ->required(),
                    TextInput::make('password')
                        ->label(__('admin.fields.password'))
                        ->password()
                        ->revealable()
                        ->rule(Password::defaults())
                        ->required(fn ($livewire): bool => $livewire instanceof CreateRecord)
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->helperText(fn ($livewire): ?string => $livewire instanceof CreateRecord ? null : __('admin.help.password')),
                    Toggle::make('is_active')
                        ->label(__('admin.fields.is_active'))
                        ->helperText(__('admin.help.is_active'))
                        ->default(true)
                        ->inline(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('organization')->withCount(['tickets', 'tickets as active_tickets_count' => fn (Builder $query) => $query->active()]))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->description(fn (User $record): ?string => $record->organization?->name ?? $record->company)
                    ->searchable(['name', 'company'])
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('admin.fields.email'))
                    ->searchable()
                    ->copyable(),
                TextColumn::make('phone')
                    ->label(__('admin.fields.phone'))
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('tickets_count')
                    ->label(__('admin.fields.tickets'))
                    ->description(fn (User $record): string => __('admin.tabs.open').': '.$record->active_tickets_count)
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(__('admin.fields.is_active'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label(__('admin.fields.created_at'))
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('organization_id')
                    ->label(__('admin.fields.organization'))
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_active')
                    ->label(__('admin.fields.is_active')),
            ])
            ->recordActions([
                EditAction::make(),
                ResetTwoFactorAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            TicketsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClients::route('/'),
            'create' => CreateClient::route('/create'),
            'edit' => EditClient::route('/{record}/edit'),
        ];
    }
}
