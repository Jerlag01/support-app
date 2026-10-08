<?php

namespace App\Filament\Admin\Resources\Departments;

use App\Enums\AdminNavigationGroup;
use App\Filament\Admin\Resources\Departments\Pages\CreateDepartment;
use App\Filament\Admin\Resources\Departments\Pages\EditDepartment;
use App\Filament\Admin\Resources\Departments\Pages\ListDepartments;
use App\Models\Department;
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
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Agents;

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('admin.resources.department.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.department.plural');
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
                        ->maxLength(100)
                        ->unique(ignoreRecord: true),
                    Select::make('sla_plan_id')
                        ->label(__('admin.fields.sla_plan'))
                        ->relationship('slaPlan', 'name')
                        ->helperText(__('admin.help.department_sla')),
                    Select::make('manager_id')
                        ->label(__('admin.fields.manager'))
                        ->options(fn (): array => User::staff()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable(),
                    Toggle::make('is_public')
                        ->label(__('admin.fields.is_public'))
                        ->helperText(__('admin.help.department_public'))
                        ->default(true)
                        ->inline(false),
                    Select::make('agents')
                        ->label(__('admin.fields.agents_with_access'))
                        ->helperText(__('admin.help.department_agents'))
                        ->relationship('agents', 'name', fn (Builder $query) => $query->staff())
                        ->multiple()
                        ->preload()
                        ->columnSpanFull(),
                    Textarea::make('signature')
                        ->label(__('admin.fields.signature'))
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['slaPlan', 'manager'])->withCount(['agents', 'tickets', 'tickets as active_tickets_count' => fn (Builder $query) => $query->active()]))
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->searchable(),
                TextColumn::make('slaPlan.name')
                    ->label(__('admin.fields.sla_plan'))
                    ->placeholder('—'),
                TextColumn::make('manager.name')
                    ->label(__('admin.fields.manager'))
                    ->placeholder('—'),
                TextColumn::make('agents_count')
                    ->label(__('admin.resources.agent.plural')),
                TextColumn::make('active_tickets_count')
                    ->label(__('admin.tabs.open')),
                IconColumn::make('is_public')
                    ->label(__('admin.fields.is_public'))
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->disabled(fn (Department $record): bool => $record->tickets()->exists() || $record->helpTopics()->exists())
                    ->tooltip(fn (Department $record): ?string => $record->tickets()->exists() || $record->helpTopics()->exists() ? __('admin.help.department_in_use') : null),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDepartments::route('/'),
            'create' => CreateDepartment::route('/create'),
            'edit' => EditDepartment::route('/{record}/edit'),
        ];
    }
}
