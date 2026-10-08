<?php

namespace App\Filament\Agent\Resources\Organizations;

use App\Enums\AdminNavigationGroup;
use App\Filament\Agent\Resources\Organizations\Pages\CreateOrganization;
use App\Filament\Agent\Resources\Organizations\Pages\EditOrganization;
use App\Filament\Agent\Resources\Organizations\Pages\ListOrganizations;
use App\Filament\Agent\Resources\Organizations\RelationManagers\UsersRelationManager;
use App\Models\Organization;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class OrganizationResource extends Resource
{
    protected static ?string $model = Organization::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Users;

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('admin.resources.organization.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.organization.plural');
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
                        ->maxLength(150),
                    TextInput::make('domain')
                        ->label(__('admin.fields.domain'))
                        ->helperText(__('admin.help.organization_domain'))
                        ->placeholder('voorbeeld.be')
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    TextInput::make('phone')
                        ->label(__('admin.fields.phone'))
                        ->maxLength(50),
                    TextInput::make('address')
                        ->label(__('admin.fields.address'))
                        ->maxLength(255),
                    Textarea::make('notes')
                        ->label(__('admin.fields.internal_notes'))
                        ->rows(4)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['users', 'tickets']))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('domain')
                    ->label(__('admin.fields.domain'))
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('users_count')
                    ->label(__('admin.resources.client.plural'))
                    ->sortable(),
                TextColumn::make('tickets_count')
                    ->label(__('admin.fields.tickets'))
                    ->sortable(),
                TextColumn::make('phone')
                    ->label(__('admin.fields.phone'))
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            UsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrganizations::route('/'),
            'create' => CreateOrganization::route('/create'),
            'edit' => EditOrganization::route('/{record}/edit'),
        ];
    }
}
