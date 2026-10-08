<?php

namespace App\Filament\Admin\Resources\FooterLinks;

use App\Enums\AdminNavigationGroup;
use App\Filament\Admin\Resources\FooterLinks\Pages\CreateFooterLink;
use App\Filament\Admin\Resources\FooterLinks\Pages\EditFooterLink;
use App\Filament\Admin\Resources\FooterLinks\Pages\ListFooterLinks;
use App\Filament\Support\TranslatableField;
use App\Models\FooterLink;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Extra links in their own row in the portal footer.
 */
class FooterLinkResource extends Resource
{
    protected static ?string $model = FooterLink::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::System;

    protected static ?int $navigationSort = 20;

    public static function getModelLabel(): string
    {
        return __('admin.resources.footer_link.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.footer_link.plural');
    }

    public static function getRecordTitle(?Model $record): string
    {
        return $record instanceof FooterLink ? $record->translate('label') : static::getModelLabel();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TranslatableField::make('label', __('admin.fields.link_text'), fn (string $path) => TextInput::make($path)->maxLength(80)),
                    TextInput::make('url')
                        ->label(__('admin.fields.link_url'))
                        ->helperText(__('admin.help.footer_link_url'))
                        ->placeholder('https://…')
                        ->prefixIcon(Heroicon::OutlinedLink)
                        ->required()
                        ->maxLength(2048)
                        ->regex(FooterLink::URL_PATTERN)
                        ->validationMessages(['regex' => __('admin.help.footer_link_url_invalid')])
                        ->columnSpanFull(),
                    Toggle::make('open_in_new_tab')
                        ->label(__('admin.fields.open_in_new_tab')),
                    Toggle::make('is_visible')
                        ->label(__('admin.fields.is_visible'))
                        ->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->emptyStateHeading(__('admin.help.footer_links_empty'))
            ->emptyStateDescription(__('admin.help.footer_links_empty_description'))
            ->emptyStateIcon(Heroicon::OutlinedLink)
            ->columns([
                TextColumn::make('label')
                    ->label(__('admin.fields.link_text'))
                    ->state(fn (FooterLink $record): string => $record->translate('label'))
                    ->weight('bold'),
                TextColumn::make('url')
                    ->label(__('admin.fields.link_url'))
                    ->limit(60)
                    ->tooltip(fn (FooterLink $record): string => $record->url)
                    ->fontFamily('mono')
                    ->size('xs')
                    ->searchable(),
                IconColumn::make('open_in_new_tab')
                    ->label(__('admin.fields.open_in_new_tab'))
                    ->boolean(),
                ToggleColumn::make('is_visible')
                    ->label(__('admin.fields.is_visible')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFooterLinks::route('/'),
            'create' => CreateFooterLink::route('/create'),
            'edit' => EditFooterLink::route('/{record}/edit'),
        ];
    }
}
