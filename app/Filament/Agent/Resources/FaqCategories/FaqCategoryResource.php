<?php

namespace App\Filament\Agent\Resources\FaqCategories;

use App\Enums\AdminNavigationGroup;
use App\Filament\Agent\Resources\FaqCategories\Pages\CreateFaqCategory;
use App\Filament\Agent\Resources\FaqCategories\Pages\EditFaqCategory;
use App\Filament\Agent\Resources\FaqCategories\Pages\ListFaqCategories;
use App\Filament\Support\TranslatableField;
use App\Models\FaqCategory;
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

class FaqCategoryResource extends Resource
{
    protected static ?string $model = FaqCategory::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::KnowledgeBase;

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return __('admin.resources.faq_category.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.faq_category.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TranslatableField::make('name', __('admin.fields.name'), fn (string $path) => TextInput::make($path)->maxLength(150)),
                    TranslatableField::make('description', __('admin.fields.description'), fn (string $path) => Textarea::make($path)->rows(3), required: false),
                    Toggle::make('is_public')
                        ->label(__('admin.fields.is_public'))
                        ->helperText(__('admin.help.faq_public'))
                        ->default(true),
                    TextInput::make('sort_order')
                        ->label(__('admin.fields.sort_order'))
                        ->numeric()
                        ->default(0),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->modifyQueryUsing(fn ($query) => $query->withCount('faqs'))
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->state(fn (FaqCategory $record): string => $record->translate('name')),
                TextColumn::make('faqs_count')
                    ->label(__('admin.resources.faq.plural')),
                ToggleColumn::make('is_public')
                    ->label(__('admin.fields.is_public')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFaqCategories::route('/'),
            'create' => CreateFaqCategory::route('/create'),
            'edit' => EditFaqCategory::route('/{record}/edit'),
        ];
    }
}
