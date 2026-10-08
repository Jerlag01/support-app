<?php

namespace App\Filament\Admin\Resources\HelpTopics;

use App\Enums\AdminNavigationGroup;
use App\Enums\TicketPriority;
use App\Filament\Admin\Resources\HelpTopics\Pages\CreateHelpTopic;
use App\Filament\Admin\Resources\HelpTopics\Pages\EditHelpTopic;
use App\Filament\Admin\Resources\HelpTopics\Pages\ListHelpTopics;
use App\Filament\Support\TranslatableField;
use App\Models\HelpTopic;
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
use UnitEnum;

/**
 * What clients pick when opening a ticket; routes it to a department with an SLA and priority.
 */
class HelpTopicResource extends Resource
{
    protected static ?string $model = HelpTopic::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Helpdesk;

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return __('admin.resources.help_topic.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.help_topic.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TranslatableField::make('name', __('admin.fields.name'), fn (string $path) => TextInput::make($path)->maxLength(100)),
                    TranslatableField::make('description', __('admin.fields.description'), fn (string $path) => Textarea::make($path)->rows(2)->maxLength(255), required: false),
                    Select::make('department_id')
                        ->label(__('admin.fields.department'))
                        ->relationship('department', 'name')
                        ->required(),
                    Select::make('sla_plan_id')
                        ->label(__('admin.fields.sla_plan'))
                        ->relationship('slaPlan', 'name')
                        ->helperText(__('admin.help.topic_sla')),
                    Select::make('default_priority')
                        ->label(__('admin.fields.default_priority'))
                        ->options(TicketPriority::class)
                        ->default(TicketPriority::Normal)
                        ->required(),
                    Select::make('icon')
                        ->label(__('admin.fields.icon'))
                        ->options(collect(HelpTopic::ICONS)->mapWithKeys(fn (string $icon): array => [$icon => __("admin.icons.{$icon}")])->all())
                        ->default('chat')
                        ->required(),
                    Toggle::make('is_public')
                        ->label(__('admin.fields.is_public'))
                        ->helperText(__('admin.help.topic_public'))
                        ->default(true),
                    Toggle::make('is_active')
                        ->label(__('admin.fields.is_active'))
                        ->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->modifyQueryUsing(fn ($query) => $query->with(['department', 'slaPlan'])->withCount('tickets'))
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->state(fn (HelpTopic $record): string => $record->label()),
                TextColumn::make('department.name')
                    ->label(__('admin.fields.department')),
                TextColumn::make('slaPlan.name')
                    ->label(__('admin.fields.sla_plan'))
                    ->placeholder(__('admin.fields.department_default')),
                TextColumn::make('default_priority')
                    ->label(__('admin.fields.default_priority'))
                    ->badge(),
                TextColumn::make('tickets_count')
                    ->label(__('admin.fields.tickets')),
                ToggleColumn::make('is_public')
                    ->label(__('admin.fields.is_public')),
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
            'index' => ListHelpTopics::route('/'),
            'create' => CreateHelpTopic::route('/create'),
            'edit' => EditHelpTopic::route('/{record}/edit'),
        ];
    }
}
