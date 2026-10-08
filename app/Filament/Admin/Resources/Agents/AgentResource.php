<?php

namespace App\Filament\Admin\Resources\Agents;

use App\Enums\AdminNavigationGroup;
use App\Enums\UserRole;
use App\Filament\Admin\Resources\Agents\Pages\CreateAgent;
use App\Filament\Admin\Resources\Agents\Pages\EditAgent;
use App\Filament\Admin\Resources\Agents\Pages\ListAgents;
use App\Filament\Support\ResetTwoFactorAction;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Password;
use UnitEnum;

/**
 * Agent and admin accounts with their department access and teams.
 */
class AgentResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'agents';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Agents;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('admin.resources.agent.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.agent.plural');
    }

    /**
     * @return Builder<User>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereIn('role', [UserRole::Agent, UserRole::Admin]);
    }

    public static function form(Schema $schema): Schema
    {
        $isSelf = fn (?User $record): bool => $record?->is(auth()->user()) ?? false;

        return $schema->components([
            Section::make(__('admin.sections.account'))
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
                    TextInput::make('phone')
                        ->label(__('admin.fields.phone'))
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
                        ->inline(false)
                        // Admins can't lock themselves out.
                        ->disabled($isSelf)
                        ->dehydrated(fn (?User $record): bool => ! $isSelf($record)),
                ]),
            Section::make(__('admin.sections.access'))
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Select::make('role')
                        ->label(__('admin.fields.role'))
                        ->options([
                            UserRole::Agent->value => UserRole::Agent->getLabel(),
                            UserRole::Admin->value => UserRole::Admin->getLabel(),
                        ])
                        ->helperText(__('admin.help.role'))
                        ->default(UserRole::Agent->value)
                        ->required()
                        ->disabled($isSelf)
                        ->dehydrated(fn (?User $record): bool => ! $isSelf($record)),
                    Select::make('departments')
                        ->label(__('admin.fields.departments'))
                        ->helperText(__('admin.help.agent_departments'))
                        ->relationship('departments', 'name')
                        ->multiple()
                        ->preload(),
                    Select::make('teams')
                        ->label(__('admin.resources.team.plural'))
                        ->relationship('teams', 'name')
                        ->multiple()
                        ->preload(),
                    Textarea::make('signature')
                        ->label(__('admin.fields.signature'))
                        ->helperText(__('admin.help.signature'))
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['departments', 'teams'])->withCount(['assignedTickets' => fn (Builder $query) => $query->active()]))
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->description(fn (User $record): string => $record->email)
                    ->searchable(['name', 'email'])
                    ->sortable(),
                TextColumn::make('role')
                    ->label(__('admin.fields.role'))
                    ->badge(),
                TextColumn::make('departments.name')
                    ->label(__('admin.fields.departments'))
                    ->badge()
                    ->color('gray')
                    ->placeholder(__('admin.fields.all_departments')),
                TextColumn::make('teams.name')
                    ->label(__('admin.resources.team.plural'))
                    ->badge()
                    ->color('gray')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('assigned_tickets_count')
                    ->label(__('admin.tabs.mine')),
                IconColumn::make('is_active')
                    ->label(__('admin.fields.is_active'))
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label(__('admin.fields.role'))
                    ->options([
                        UserRole::Agent->value => UserRole::Agent->getLabel(),
                        UserRole::Admin->value => UserRole::Admin->getLabel(),
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                ResetTwoFactorAction::make(),
                DeleteAction::make()
                    ->hidden(fn (User $record): bool => $record->is(auth()->user())),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAgents::route('/'),
            'create' => CreateAgent::route('/create'),
            'edit' => EditAgent::route('/{record}/edit'),
        ];
    }
}
