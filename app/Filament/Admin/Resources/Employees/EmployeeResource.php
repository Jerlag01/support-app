<?php

namespace App\Filament\Admin\Resources\Employees;

use App\Enums\AdminNavigationGroup;
use App\Filament\Admin\Resources\Employees\Pages\CreateEmployee;
use App\Filament\Admin\Resources\Employees\Pages\EditEmployee;
use App\Filament\Admin\Resources\Employees\Pages\ListEmployees;
use App\Filament\Support\TranslatableField;
use App\Models\Employee;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * The people on the portal's "who is who" page.
 */
class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $slug = 'who-is-who';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::System;

    protected static ?int $navigationSort = 19;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('admin.resources.employee.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.employee.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make()
                ->columns(2)
                ->columnSpan(['lg' => 2])
                ->schema([
                    TextInput::make('name')
                        ->label(__('admin.fields.name'))
                        ->required()
                        ->maxLength(150)
                        ->columnSpanFull(),
                    TextInput::make('phone')
                        ->label(__('admin.fields.phone'))
                        ->tel()
                        ->prefixIcon(Heroicon::OutlinedPhone)
                        ->maxLength(30),
                    TextInput::make('mobile')
                        ->label(__('admin.fields.mobile'))
                        ->tel()
                        ->prefixIcon(Heroicon::OutlinedDevicePhoneMobile)
                        ->maxLength(30),
                    TextInput::make('email')
                        ->label(__('admin.fields.email'))
                        ->email()
                        ->prefixIcon(Heroicon::OutlinedEnvelope)
                        ->maxLength(150)
                        ->columnSpanFull(),
                    TranslatableField::make('job_title', __('admin.fields.job_title'), fn (string $path) => TextInput::make($path)->maxLength(100), required: false),
                ]),
            Section::make(__('admin.fields.photo'))
                ->columnSpan(['lg' => 1])
                ->schema([
                    // No SVG uploads: an SVG on the public disk could carry script.
                    FileUpload::make('photo')
                        ->hiddenLabel()
                        ->helperText(__('admin.help.employee_photo'))
                        ->image()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(4096)
                        ->imageEditor()
                        ->imageEditorAspectRatios(['1:1'])
                        ->disk(Employee::DISK)
                        ->directory('employees')
                        ->visibility('public'),
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
            ->emptyStateHeading(__('admin.help.employees_empty'))
            ->emptyStateIcon(Heroicon::OutlinedUserGroup)
            ->columns([
                ImageColumn::make('photo')
                    ->label(__('admin.fields.photo'))
                    ->disk(Employee::DISK)
                    ->circular()
                    ->defaultImageUrl(fn (): string => helpdesk()->logoUrl() ?? asset('favicon.svg'))
                    ->imageHeight(40),
                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->description(fn (Employee $record): ?string => $record->translate('job_title') ?: null)
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label(__('admin.fields.phone'))
                    ->description(fn (Employee $record): ?string => $record->mobile)
                    ->placeholder('-'),
                TextColumn::make('email')
                    ->label(__('admin.fields.email'))
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(),
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
            'index' => ListEmployees::route('/'),
            'create' => CreateEmployee::route('/create'),
            'edit' => EditEmployee::route('/{record}/edit'),
        ];
    }
}
