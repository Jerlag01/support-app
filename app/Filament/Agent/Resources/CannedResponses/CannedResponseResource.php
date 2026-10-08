<?php

namespace App\Filament\Agent\Resources\CannedResponses;

use App\Enums\AdminNavigationGroup;
use App\Filament\Agent\Resources\CannedResponses\Pages\CreateCannedResponse;
use App\Filament\Agent\Resources\CannedResponses\Pages\EditCannedResponse;
use App\Filament\Agent\Resources\CannedResponses\Pages\ListCannedResponses;
use App\Models\CannedResponse;
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

class CannedResponseResource extends Resource
{
    protected static ?string $model = CannedResponse::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Tickets;

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return __('admin.resources.canned_response.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.canned_response.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('title')
                        ->label(__('admin.fields.title'))
                        ->required()
                        ->maxLength(150),
                    Select::make('department_id')
                        ->label(__('admin.fields.department'))
                        ->relationship('department', 'name')
                        ->placeholder(__('admin.fields.all_departments')),
                    Textarea::make('body')
                        ->label(__('admin.fields.response'))
                        ->helperText(__('admin.help.placeholders', ['placeholders' => implode(', ', CannedResponse::PLACEHOLDERS)]))
                        ->required()
                        ->rows(10)
                        ->columnSpanFull(),
                    Toggle::make('is_active')
                        ->label(__('admin.fields.is_active'))
                        ->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('title')
            ->columns([
                TextColumn::make('title')
                    ->label(__('admin.fields.title'))
                    ->description(fn (CannedResponse $record): string => str($record->body)->limit(90))
                    ->searchable(['title', 'body']),
                TextColumn::make('department.name')
                    ->label(__('admin.fields.department'))
                    ->placeholder(__('admin.fields.all_departments')),
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
            'index' => ListCannedResponses::route('/'),
            'create' => CreateCannedResponse::route('/create'),
            'edit' => EditCannedResponse::route('/{record}/edit'),
        ];
    }
}
