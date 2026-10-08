<?php

namespace App\Filament\Admin\Pages;

use App\Enums\AdminNavigationGroup;
use App\Filament\Support\TranslatableField;
use App\Models\Department;
use App\Models\SlaPlan;
use App\Support\HelpdeskSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;
use UnitEnum;

/**
 * Helpdesk-wide behaviour, outgoing mail and appearance (osTicket's Admin Panel > Settings).
 *
 * @property-read Schema $form
 */
class Settings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::System;

    protected static ?int $navigationSort = 1;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('admin.settings.title');
    }

    public function getTitle(): string
    {
        return __('admin.settings.title');
    }

    public function mount(HelpdeskSettings $settings): void
    {
        $values = $settings->all();
        // The stored password is never sent to the browser; leaving the field empty keeps it.
        $values['mail']['password'] = null;

        $this->form->fill($values);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make()
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make(__('admin.settings.tabs.organization'))
                            ->icon(Heroicon::OutlinedBuildingOffice2)
                            ->schema($this->organizationFields()),
                        Tab::make(__('admin.settings.tabs.helpdesk'))
                            ->icon(Heroicon::OutlinedLifebuoy)
                            ->schema($this->helpdeskFields()),
                        Tab::make(__('admin.settings.tabs.mail'))
                            ->icon(Heroicon::OutlinedEnvelope)
                            ->schema($this->mailFields()),
                        Tab::make(__('admin.settings.tabs.appearance'))
                            ->icon(Heroicon::OutlinedPaintBrush)
                            ->schema($this->appearanceFields()),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label(__('admin.actions.save'))
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                        ]),
                    ]),
            ]);
    }

    public function save(HelpdeskSettings $settings): void
    {
        $data = $this->form->getState();

        $data['mail']['password'] = filled($data['mail']['password'] ?? null)
            ? Crypt::encryptString($data['mail']['password'])
            : $settings->get('mail.password');

        foreach (['logo', 'favicon', 'hero_image'] as $image) {
            $old = $settings->get("appearance.{$image}");
            if (filled($old) && $old !== ($data['appearance'][$image] ?? null)) {
                Storage::disk('public')->delete($old);
            }
        }

        $settings->save($data);
        $this->data['mail']['password'] = null;

        Notification::make()
            ->title(__('admin.settings.saved'))
            ->success()
            ->send();
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendTestMail')
                ->label(__('admin.settings.test_mail'))
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription(fn (): string => __('admin.settings.test_mail_description', ['email' => auth()->user()->email]))
                ->action(function (HelpdeskSettings $settings): void {
                    $settings->applyMailConfig();
                    Mail::purge();

                    try {
                        Mail::raw(__('admin.settings.test_mail_body', ['company' => $settings->companyName()]), function ($message) use ($settings): void {
                            $message->to(auth()->user()->email)->subject($settings->companyName().' - test');
                        });

                        Notification::make()->title(__('admin.settings.test_mail_sent'))->success()->send();
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->title(__('admin.settings.test_mail_failed'))
                            ->body($exception->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),
        ];
    }

    /**
     * Who runs this helpdesk: shown in the portal header and footer, panels and e-mails.
     *
     * @return array<Section>
     */
    private function organizationFields(): array
    {
        return [
            Section::make(__('admin.settings.organization'))
                ->description(__('admin.settings.organization_help'))
                ->columns(2)
                ->schema([
                    TextInput::make('branding.company_name')
                        ->label(__('admin.settings.company_name'))
                        ->required()
                        ->maxLength(100),
                    TextInput::make('branding.main_site_url')
                        ->label(__('admin.settings.main_site_url'))
                        ->url()
                        ->rule('url:http,https')
                        ->placeholder('https://'),
                    TranslatableField::make('branding.tagline', __('admin.settings.tagline'), fn (string $path) => TextInput::make($path)->maxLength(60)),
                    TranslatableField::make('branding.about', __('admin.settings.about'), fn (string $path) => Textarea::make($path)->rows(3)->maxLength(500), required: false),
                ]),
            Section::make(__('admin.settings.contact'))
                ->columns(2)
                ->schema([
                    TextInput::make('branding.phone')
                        ->label(__('admin.fields.phone'))
                        ->helperText(__('admin.settings.phone_help'))
                        ->maxLength(50),
                    TextInput::make('branding.email')
                        ->label(__('admin.fields.email'))
                        ->email(),
                    TextInput::make('branding.vat_number')
                        ->label(__('admin.settings.vat_number'))
                        ->placeholder('BE 0123.456.789')
                        ->maxLength(30),
                    TextInput::make('branding.street')
                        ->label(__('admin.settings.street'))
                        ->maxLength(150),
                    TextInput::make('branding.postal_code')
                        ->label(__('admin.settings.postal_code'))
                        ->maxLength(20),
                    TranslatableField::make('branding.city', __('admin.settings.city'), fn (string $path) => TextInput::make($path)->maxLength(100), required: false),
                    TranslatableField::make('branding.opening_hours', __('admin.settings.opening_hours'), fn (string $path) => TextInput::make($path)->maxLength(150), required: false),
                ]),
            Section::make(__('admin.settings.social'))
                ->description(__('admin.settings.social_help'))
                ->columns(2)
                ->schema($this->socialFields()),
        ];
    }

    /**
     * @return list<TextInput>
     */
    private function socialFields(): array
    {
        return collect(['instagram' => 'Instagram', 'facebook' => 'Facebook', 'linkedin' => 'LinkedIn', 'twitter' => 'X (Twitter)'])
            ->map(fn (string $label, string $network): TextInput => TextInput::make("branding.social.{$network}")
                ->label($label)
                ->url()
                ->rule('url:http,https')
                ->maxLength(255)
                ->placeholder("https://{$network}.com/…")
                ->prefixIcon(Heroicon::OutlinedLink))
            ->values()
            ->all();
    }

    /**
     * @return array<Section>
     */
    private function helpdeskFields(): array
    {
        return [
            Section::make(__('admin.settings.tickets'))
                ->columns(2)
                ->schema([
                    Select::make('default_department_id')
                        ->label(__('admin.settings.default_department'))
                        ->helperText(__('admin.settings.default_department_help'))
                        ->options(fn (): array => Department::orderBy('name')->pluck('name', 'id')->all()),
                    Select::make('default_sla_plan_id')
                        ->label(__('admin.settings.default_sla'))
                        ->helperText(__('admin.settings.default_sla_help'))
                        ->options(fn (): array => SlaPlan::where('is_active', true)->pluck('name', 'id')->all()),
                    Toggle::make('auto_assign_on_reply')
                        ->label(__('admin.settings.auto_assign_on_reply'))
                        ->helperText(__('admin.settings.auto_assign_on_reply_help')),
                    Toggle::make('clients_can_reopen')
                        ->label(__('admin.settings.clients_can_reopen')),
                ]),
            Section::make(__('admin.settings.portal'))
                ->columns(2)
                ->schema([
                    Toggle::make('allow_registration')
                        ->label(__('admin.settings.allow_registration'))
                        ->helperText(__('admin.settings.allow_registration_help')),
                    Toggle::make('show_knowledge_base')
                        ->label(__('admin.settings.show_knowledge_base')),
                    Toggle::make('show_who_is_who')
                        ->label(__('admin.settings.show_who_is_who'))
                        ->helperText(__('admin.settings.show_who_is_who_help')),
                ]),
        ];
    }

    /**
     * @return array<Section>
     */
    private function mailFields(): array
    {
        $usesSmtp = fn (Get $get): bool => $get('mail.mailer') === 'smtp';

        return [
            Section::make(__('admin.settings.mail'))
                ->description(__('admin.settings.mail_help'))
                ->columns(2)
                ->schema([
                    Select::make('mail.mailer')
                        ->label(__('admin.settings.mailer'))
                        ->options([
                            'smtp' => 'SMTP',
                            'sendmail' => 'Sendmail',
                            'log' => __('admin.settings.mailer_log'),
                        ])
                        ->placeholder(__('admin.settings.mailer_env'))
                        ->live(),
                    TextInput::make('mail.host')
                        ->label(__('admin.settings.host'))
                        ->placeholder('smtp.example.com')
                        ->required($usesSmtp)
                        ->visible($usesSmtp),
                    TextInput::make('mail.port')
                        ->label(__('admin.settings.port'))
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(65535)
                        ->required($usesSmtp)
                        ->visible($usesSmtp),
                    Select::make('mail.scheme')
                        ->label(__('admin.settings.scheme'))
                        ->options([
                            'smtp' => 'STARTTLS (587)',
                            'smtps' => 'SSL/TLS (465)',
                        ])
                        ->placeholder(__('admin.settings.scheme_auto'))
                        ->visible($usesSmtp),
                    TextInput::make('mail.username')
                        ->label(__('admin.settings.username'))
                        ->autocomplete('off')
                        ->visible($usesSmtp),
                    TextInput::make('mail.password')
                        ->label(__('admin.fields.password'))
                        ->password()
                        ->revealable()
                        ->autocomplete('new-password')
                        ->helperText(fn (HelpdeskSettings $settings): string => filled($settings->get('mail.password'))
                            ? __('admin.settings.mail_password_saved')
                            : __('admin.settings.mail_password_help'))
                        ->visible($usesSmtp),
                    TextInput::make('mail.from_address')
                        ->label(__('admin.settings.from_address'))
                        ->email()
                        ->required(fn (Get $get): bool => filled($get('mail.mailer'))),
                    TextInput::make('mail.from_name')
                        ->label(__('admin.settings.from_name'))
                        ->placeholder(fn (HelpdeskSettings $settings): string => $settings->companyName()),
                ]),
        ];
    }

    /**
     * @return array<Section>
     */
    private function appearanceFields(): array
    {
        return [
            Section::make(__('admin.settings.colors'))
                ->description(__('admin.settings.colors_help'))
                ->columns(2)
                ->schema([
                    ColorPicker::make('appearance.primary')
                        ->label(__('admin.settings.primary_color'))
                        ->regex('/^#[0-9a-fA-F]{6}$/')
                        ->required(),
                    ColorPicker::make('appearance.accent')
                        ->label(__('admin.settings.accent_color'))
                        ->helperText(__('admin.settings.accent_color_help'))
                        ->regex('/^#[0-9a-fA-F]{6}$/')
                        ->required(),
                ]),
            Section::make(__('admin.settings.logo'))
                ->description(__('admin.settings.logo_help'))
                ->schema([
                    FileUpload::make('appearance.logo')
                        ->hiddenLabel()
                        ->image()
                        ->acceptedFileTypes(['image/png', 'image/webp', 'image/jpeg'])
                        ->maxSize(1024)
                        ->disk('public')
                        ->directory('branding')
                        ->visibility('public')
                        ->imagePreviewHeight('80'),
                ]),
            Section::make(__('admin.settings.hero_image'))
                ->description(__('admin.settings.hero_image_help'))
                ->schema([
                    // No SVG uploads: an SVG on the public disk could carry script.
                    FileUpload::make('appearance.hero_image')
                        ->hiddenLabel()
                        ->image()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(5120)
                        ->disk('public')
                        ->directory('branding')
                        ->visibility('public')
                        ->imageEditor()
                        ->imagePreviewHeight('160'),
                ]),
            Section::make(__('admin.settings.favicon'))
                ->description(__('admin.settings.favicon_help'))
                ->schema([
                    // No SVG uploads: an SVG on the public disk could carry script.
                    FileUpload::make('appearance.favicon')
                        ->hiddenLabel()
                        ->image()
                        ->acceptedFileTypes(['image/png', 'image/x-icon', 'image/vnd.microsoft.icon', 'image/webp'])
                        ->maxSize(512)
                        ->disk('public')
                        ->directory('branding')
                        ->visibility('public')
                        ->imagePreviewHeight('64'),
                ]),
        ];
    }
}
