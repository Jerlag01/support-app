<?php

namespace App\Filament\Admin\Resources\SlaPlans;

use App\Enums\AdminNavigationGroup;
use App\Filament\Admin\Resources\SlaPlans\Pages\CreateSlaPlan;
use App\Filament\Admin\Resources\SlaPlans\Pages\EditSlaPlan;
use App\Filament\Admin\Resources\SlaPlans\Pages\ListSlaPlans;
use App\Models\SlaPlan;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
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
use UnitEnum;

class SlaPlanResource extends Resource
{
    protected static ?string $model = SlaPlan::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Helpdesk;

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('admin.resources.sla_plan.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.sla_plan.plural');
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
                    TextInput::make('grace_hours')
                        ->label(__('admin.fields.grace_hours'))
                        ->helperText(__('admin.help.grace_hours'))
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(8760)
                        ->suffix(__('admin.fields.hours'))
                        ->required(),
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
            ->defaultSort('grace_hours')
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->searchable(),
                TextColumn::make('grace_hours')
                    ->label(__('admin.fields.grace_hours'))
                    ->suffix(' '.__('admin.fields.hours'))
                    ->sortable(),
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
            'index' => ListSlaPlans::route('/'),
            'create' => CreateSlaPlan::route('/create'),
            'edit' => EditSlaPlan::route('/{record}/edit'),
        ];
    }
}
