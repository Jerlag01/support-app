<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\Settings;
use App\Models\User;
use App\Support\HelpdeskSettings;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BrandingAndMailSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel('admin');
    }

    public function test_another_company_can_rebrand_the_helpdesk(): void
    {
        Livewire::test(Settings::class)
            ->fillForm([
                'branding.company_name' => 'Acme Facility BV',
                'branding.tagline' => ['nl' => 'Servicedesk', 'fr' => 'Service', 'en' => 'Service desk'],
                'branding.phone' => '+32 9 123 45 67',
                'branding.vat_number' => 'BE 0987.654.321',
                'branding.social.linkedin' => 'https://www.linkedin.com/company/acme',
                'branding.social.instagram' => 'https://www.instagram.com/acme',
                'branding.street' => 'Kerkstraat 1',
                'branding.about' => ['nl' => 'Acme helpt je verder.'],
                'branding.main_site_url' => 'https://acme.example',
                'appearance.primary' => '#123456',
                'appearance.accent' => '#00a86b',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        auth()->logout();
        $this->get('/')
            ->assertOk()
            ->assertSee('Acme Facility BV')
            ->assertSee('Servicedesk')
            ->assertSee('Acme helpt je verder.')
            ->assertSee('+32 9 123 45 67')
            ->assertSee('Btw BE 0987.654.321')
            ->assertSee('href="https://www.linkedin.com/company/acme"', false)
            ->assertSee('href="https://www.instagram.com/acme"', false)
            ->assertDontSee('title="Facebook"', false)
            ->assertSee('--color-accent:#00a86b', false)
            ->assertDontSee('Power Installation NV');

        $this->get('/agent/login')->assertSee('Acme Facility BV');
    }

    public function test_logo_and_favicon_uploads_are_used_and_removed_files_deleted(): void
    {
        Livewire::test(Settings::class)
            ->fillForm([
                'appearance.logo' => UploadedFile::fake()->image('logo.png', 200, 200),
                'appearance.favicon' => UploadedFile::fake()->image('icon.png', 64, 64),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = app(HelpdeskSettings::class);
        $logo = $settings->get('appearance.logo');
        Storage::disk('public')->assertExists($logo);
        $this->get('/')->assertSee(Storage::disk('public')->url($logo))->assertSee($settings->faviconUrl());

        // Removing the logo deletes the file and brings back the default mark.
        Livewire::test(Settings::class)
            ->fillForm(['appearance.logo' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        Storage::disk('public')->assertMissing($logo);
        $this->assertNull(app(HelpdeskSettings::class)->logoUrl());
        $this->get('/')->assertDontSee(Storage::disk('public')->url($logo));
    }

    public function test_social_links_must_be_web_addresses(): void
    {
        Livewire::test(Settings::class)
            ->fillForm(['branding.social.facebook' => 'javascript:alert(1)'])
            ->call('save')
            ->assertHasFormErrors(['branding.social.facebook']);
    }

    public function test_svg_uploads_are_refused(): void
    {
        Livewire::test(Settings::class)
            ->fillForm(['appearance.favicon' => UploadedFile::fake()->create('icon.svg', 1, 'image/svg+xml')])
            ->call('save')
            ->assertHasFormErrors(['appearance.favicon']);
    }

    public function test_mail_settings_are_encrypted_kept_and_applied(): void
    {
        Livewire::test(Settings::class)
            ->fillForm([
                'mail.mailer' => 'smtp',
                'mail.host' => 'smtp.acme.example',
                'mail.port' => 465,
                'mail.scheme' => 'smtps',
                'mail.username' => 'helpdesk@acme.example',
                'mail.password' => 'geheim-wachtwoord',
                'mail.from_address' => 'helpdesk@acme.example',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertSet('data.mail.password', null);

        $settings = app(HelpdeskSettings::class);
        $stored = $settings->get('mail.password');
        $this->assertNotSame('geheim-wachtwoord', $stored);
        $this->assertSame('geheim-wachtwoord', Crypt::decryptString($stored));

        // Saving again with an empty password field keeps the stored one.
        Livewire::test(Settings::class)->fillForm(['mail.host' => 'smtp2.acme.example'])->call('save');
        $this->assertSame('geheim-wachtwoord', Crypt::decryptString(app(HelpdeskSettings::class)->get('mail.password')));

        $settings->applyMailConfig();
        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp2.acme.example', config('mail.mailers.smtp.host'));
        $this->assertSame('geheim-wachtwoord', config('mail.mailers.smtp.password'));
        $this->assertSame('smtps', config('mail.mailers.smtp.scheme'));
        $this->assertSame('Power Installation NV', config('mail.from.name'));
    }

    public function test_test_mail_is_sent_with_the_log_mailer(): void
    {
        app(HelpdeskSettings::class)->save(['mail' => ['mailer' => 'log', 'from_address' => 'helpdesk@example.com']]);

        Livewire::test(Settings::class)
            ->callAction('sendTestMail')
            ->assertNotified(__('admin.settings.test_mail_sent'));
    }
}
