<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\Settings as SettingsPage;
use App\Filament\Admin\Resources\Employees\Pages\CreateEmployee;
use App\Filament\Admin\Resources\FooterLinks\Pages\CreateFooterLink;
use App\Models\Employee;
use App\Models\FooterLink;
use App\Models\User;
use App\Support\HelpdeskSettings;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Footer links, the homepage background picture and the "who is who" page.
 */
class PortalExtrasTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAsAdmin(): User
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        Filament::setCurrentPanel('admin');

        return $admin;
    }

    public function test_the_footer_links_row_only_appears_with_visible_links(): void
    {
        $this->get('/')->assertOk()->assertDontSee('aria-label="Meer links"', escape: false);

        FooterLink::factory()->hidden()->create(['label' => ['nl' => 'Verborgen link']]);
        $this->get('/')->assertDontSee('aria-label="Meer links"', escape: false);

        FooterLink::factory()->create(['label' => ['nl' => 'Tweede'], 'url' => 'https://example.com/2', 'sort_order' => 2]);
        FooterLink::factory()->create(['label' => ['nl' => 'Eerste'], 'url' => '/kb', 'sort_order' => 1, 'open_in_new_tab' => true]);

        $html = $this->get('/')
            ->assertSee('aria-label="Meer links"', escape: false)
            ->assertSeeInOrder(['Eerste', 'Tweede'])
            ->assertDontSee('Verborgen link')
            ->getContent();

        $this->assertMatchesRegularExpression('/href="\/kb"[^>]*target="_blank" rel="noopener noreferrer"/', $html);
    }

    public function test_footer_links_are_managed_by_admins_and_only_safe_addresses_are_accepted(): void
    {
        $this->actingAsAdmin();

        $this->get('/admin/footer-links')->assertOk();

        Livewire::test(CreateFooterLink::class)
            ->fillForm(['label' => ['nl' => 'Script'], 'url' => 'javascript:alert(1)'])
            ->call('create')
            ->assertHasFormErrors(['url' => 'regex']);

        Livewire::test(CreateFooterLink::class)
            ->fillForm(['label' => ['nl' => 'Hoofdsite', 'fr' => 'Site principal'], 'url' => 'https://www.example.be'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('Site principal', FooterLink::sole()->translate('label', 'fr'));
    }

    public function test_agents_and_clients_cannot_manage_portal_content(): void
    {
        $this->actingAs(User::factory()->agent()->create())->get('/admin/footer-links')->assertForbidden();
        $this->actingAs(User::factory()->agent()->create())->get('/admin/who-is-who')->assertForbidden();
        $this->actingAs(User::factory()->create())->get('/admin/who-is-who')->assertForbidden();
    }

    public function test_the_homepage_keeps_its_default_background_without_a_picture(): void
    {
        $this->get('/')->assertOk()->assertDontSee('data-hero-image', escape: false)
            ->assertSee('from-navy-950 via-navy-800 to-navy-700', escape: false);
    }

    public function test_an_uploaded_background_picture_is_shown_and_can_be_removed(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();

        Livewire::test(SettingsPage::class)
            ->fillForm(['appearance.hero_image' => UploadedFile::fake()->image('helpdesk.jpg', 1920, 1080)])
            ->call('save')
            ->assertHasNoFormErrors();

        $path = app(HelpdeskSettings::class)->get('appearance.hero_image');
        $this->assertNotNull($path);

        $this->get('/')
            ->assertSee(Storage::disk('public')->url($path))
            ->assertSee('data-hero-image', escape: false)
            ->assertDontSee('from-navy-950 via-navy-800 to-navy-700', escape: false);

        Livewire::test(SettingsPage::class)
            ->fillForm(['appearance.hero_image' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        Storage::disk('public')->assertMissing($path);
        $this->get('/')->assertDontSee('data-hero-image', escape: false);
    }

    public function test_the_who_is_who_page_lists_visible_people_with_the_logo_as_fallback(): void
    {
        Storage::fake('public');
        Employee::factory()->create(['name' => 'Eva Peeters', 'job_title' => ['nl' => 'Servicetechnicus', 'fr' => 'Technicienne'], 'email' => 'eva@example.be', 'mobile' => '0470 12 34 56', 'photo' => 'employees/eva.jpg']);
        Employee::factory()->create(['name' => 'Zonder Foto', 'photo' => null]);
        Employee::factory()->hidden()->create(['name' => 'Verborgen Persoon']);

        $this->get('/who-is-who')
            ->assertOk()
            ->assertSee('Wie is wie')
            ->assertSee('Eva Peeters')
            ->assertSee('Servicetechnicus')
            ->assertSee('href="mailto:eva@example.be"', escape: false)
            ->assertSee('href="tel:0470123456"', escape: false)
            ->assertSee(Storage::disk('public')->url('employees/eva.jpg'))
            ->assertSee('data-photo-fallback', escape: false)
            ->assertDontSee('Verborgen Persoon');

        $this->get('/')->assertSee(route('who-is-who'));
    }

    public function test_the_who_is_who_page_disappears_when_switched_off_or_empty(): void
    {
        $this->get('/who-is-who')->assertNotFound();
        $this->get('/')->assertDontSee(route('who-is-who'));

        Employee::factory()->create();
        $this->get('/who-is-who')->assertOk();

        app(HelpdeskSettings::class)->save(['show_who_is_who' => false]);

        $this->get('/who-is-who')->assertNotFound();
        $this->get('/')->assertDontSee(route('who-is-who'));
    }

    public function test_people_are_added_in_the_admin_panel(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();

        Livewire::test(CreateEmployee::class)
            ->fillForm([
                'name' => 'Jan Janssens',
                'job_title' => ['nl' => 'Helpdeskmedewerker'],
                'phone' => '+32 2 123 45 67',
                'photo' => UploadedFile::fake()->image('jan.jpg', 400, 400),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $employee = Employee::sole();
        Storage::disk('public')->assertExists($employee->photo);

        $employee->update(['photo' => null]);
        $this->assertNull($employee->fresh()->photo);
    }
}
