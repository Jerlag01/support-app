<?php

namespace App\Filament\Agent\Resources\Faqs;

use App\Enums\AdminNavigationGroup;
use App\Enums\FaqFormat;
use App\Filament\Agent\Resources\Faqs\Pages\CreateFaq;
use App\Filament\Agent\Resources\Faqs\Pages\EditFaq;
use App\Filament\Agent\Resources\Faqs\Pages\ListFaqs;
use App\Filament\Support\TranslatableField;
use App\Models\Faq;
use App\Models\FaqCategory;
use BackedEnum;
use Closure;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::KnowledgeBase;

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return __('admin.resources.faq.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.faq.plural');
    }

    /**
     * @return array<int, string>
     */
    private static function categoryOptions(): array
    {
        return FaqCategory::orderBy('sort_order')->get()
            ->mapWithKeys(fn (FaqCategory $category): array => [$category->id => $category->translate('name')])
            ->all();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Select::make('faq_category_id')
                        ->label(__('admin.fields.category'))
                        ->options(fn (): array => self::categoryOptions())
                        ->required(),
                    TextInput::make('sort_order')
                        ->label(__('admin.fields.sort_order'))
                        ->numeric()
                        ->default(0),
                    TranslatableField::make('question', __('admin.fields.question'), fn (string $path) => TextInput::make($path)->maxLength(255)),
                    ToggleButtons::make('format')
                        ->label(__('admin.fields.format'))
                        ->helperText(__('admin.help.faq_format'))
                        ->options(FaqFormat::class)
                        ->default(FaqFormat::RichText)
                        ->inline()
                        ->live()
                        ->required()
                        ->columnSpanFull(),
                    self::answerEditor(FaqFormat::RichText, fn (string $path) => RichEditor::make($path)
                        ->fileAttachmentsDisk(Faq::DISK)
                        ->fileAttachmentsDirectory('kb/images')
                        ->fileAttachmentsVisibility('public')
                        ->fileAttachmentsAcceptedFileTypes(['image/png', 'image/jpeg', 'image/gif', 'image/webp'])
                        ->fileAttachmentsMaxSize(5120)
                        ->extraInputAttributes(['style' => 'min-height: 16rem'])),
                    // Markdown image attachments are always public; that's fine, the knowledge base is public.
                    self::answerEditor(FaqFormat::Markdown, fn (string $path) => MarkdownEditor::make($path)
                        ->fileAttachmentsDisk(Faq::DISK)
                        ->fileAttachmentsDirectory('kb/images')
                        ->fileAttachmentsAcceptedFileTypes(['image/png', 'image/jpeg', 'image/gif', 'image/webp'])
                        ->fileAttachmentsMaxSize(5120)
                        ->minHeight('16rem')),
                    FileUpload::make('attachments')
                        ->label(__('admin.fields.downloads'))
                        ->helperText(__('admin.help.faq_downloads'))
                        ->disk(Faq::DISK)
                        ->directory('kb/files')
                        ->visibility('public')
                        ->multiple()
                        ->reorderable()
                        ->appendFiles()
                        ->downloadable()
                        ->openable()
                        ->maxFiles(10)
                        ->maxSize(10240)
                        ->rules(['extensions:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip,png,jpg,jpeg,gif,webp'])
                        ->storeFileNamesIn('attachment_names')
                        ->columnSpanFull(),
                    Toggle::make('is_published')
                        ->label(__('admin.fields.is_published'))
                        ->default(true),
                ]),
        ]);
    }

    /**
     * One editor per language; only the editor for the chosen format is shown and saved.
     *
     * @param  Closure(string $statePath): Field  $makeField
     */
    private static function answerEditor(FaqFormat $format, Closure $makeField): Tabs
    {
        $keyedField = fn (string $path): Field => $makeField($path)->key(str_replace('.', '-', $path)."-{$format->value}");

        return TranslatableField::make('answer', __('admin.fields.answer'), $keyedField)
            ->key("answer-{$format->value}")
            ->visible(fn (Get $get): bool => FaqFormat::tryFrom($get('format') instanceof FaqFormat ? $get('format')->value : (string) $get('format')) === $format);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->modifyQueryUsing(fn ($query) => $query->with('category'))
            ->columns([
                TextColumn::make('question')
                    ->label(__('admin.fields.question'))
                    ->state(fn (Faq $record): string => $record->translate('question'))
                    ->wrap(),
                TextColumn::make('format')
                    ->label(__('admin.fields.format'))
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('category.name')
                    ->label(__('admin.fields.category'))
                    ->state(fn (Faq $record): string => $record->category->translate('name')),
                ToggleColumn::make('is_published')
                    ->label(__('admin.fields.is_published')),
                TextColumn::make('updated_at')
                    ->label(__('admin.fields.updated_at'))
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('faq_category_id')
                    ->label(__('admin.fields.category'))
                    ->options(fn (): array => self::categoryOptions()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFaqs::route('/'),
            'create' => CreateFaq::route('/create'),
            'edit' => EditFaq::route('/{record}/edit'),
        ];
    }
}
