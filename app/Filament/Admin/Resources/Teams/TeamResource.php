<?php

namespace App\Filament\Admin\Resources\Teams;

use App\Enums\AdminNavigationGroup;
use App\Filament\Admin\Resources\Teams\Pages\CreateTeam;
use App\Filament\Admin\Resources\Teams\Pages\EditTeam;
use App\Filament\Admin\Resources\Teams\Pages\ListTeams;
use App\Models\Team;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Agents;

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('admin.resources.team.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.team.plural');
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
                    Select::make('lead_id')
                        ->label(__('admin.fields.team_lead'))
                        ->options(fn (): array => User::staff()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable(),
                    Select::make('members')
                        ->label(__('admin.fields.members'))
                        ->relationship('members', 'name', fn (Builder $query) => $query->staff())
                        ->multiple()
                        ->preload()
                        ->columnSpanFull(),
                    Toggle::make('is_active')
                        ->label(__('admin.fields.is_active'))
                        ->default(true),
                    Textarea::make('notes')
                        ->label(__('admin.fields.internal_notes'))
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('lead')->withCount(['members', 'tickets' => fn (Builder $query) => $query->active()]))
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->searchable(),
                TextColumn::make('lead.name')
                    ->label(__('admin.fields.team_lead'))
                    ->placeholder('—'),
                TextColumn::make('members_count')
                    ->label(__('admin.fields.members')),
                TextColumn::make('tickets_count')
                    ->label(__('admin.tabs.open')),
                ToggleColumn::make('is_active')
                    ->label(__('admin.fields.is_active')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTeams::route('/'),
            'create' => CreateTeam::route('/create'),
            'edit' => EditTeam::route('/{record}/edit'),
        ];
    }
}
