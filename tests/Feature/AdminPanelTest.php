<?php

namespace Tests\Feature;

use App\Actions\CreateTicket;
use App\Enums\UserRole;
use App\Filament\Admin\Pages\Settings;
use App\Filament\Admin\Resources\Agents\Pages\CreateAgent;
use App\Filament\Admin\Resources\Agents\Pages\EditAgent;
use App\Filament\Admin\Resources\Departments\Pages\CreateDepartment;
use App\Filament\Admin\Resources\HelpTopics\Pages\CreateHelpTopic;
use App\Filament\Admin\Resources\SlaPlans\Pages\CreateSlaPlan;
use App\Models\Department;
use App\Models\HelpTopic;
use App\Models\SlaPlan;
use App\Models\User;
use App\Support\HelpdeskSettings;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin);
        Filament::setCurrentPanel('admin');
    }

    public function test_admin_pages_render(): void
    {
        foreach (['/admin', '/admin/settings', '/admin/departments', '/admin/help-topics', '/admin/sla-plans', '/admin/teams', '/admin/agents'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_admin_builds_the_helpdesk_structure(): void
    {
        Livewire::test(CreateSlaPlan::class)
            ->fillForm(['name' => 'Spoed', 'grace_hours' => 2])
            ->call('create')
            ->assertHasNoFormErrors();
        $sla = SlaPlan::where('name', 'Spoed')->sole();

        $agent = User::factory()->agent()->create();
        Livewire::test(CreateDepartment::class)
            ->fillForm(['name' => 'Storingsdienst', 'sla_plan_id' => $sla->id, 'agents' => [$agent->id]])
            ->call('create')
            ->assertHasNoFormErrors();
        $department = Department::where('name', 'Storingsdienst')->sole();
        $this->assertTrue($department->agents->first()->is($agent));

        Livewire::test(CreateHelpTopic::class)
            ->fillForm([
                'name' => ['nl' => 'Stroomuitval', 'fr' => 'Panne de courant', 'en' => 'Power outage'],
                'department_id' => $department->id,
                'default_priority' => 'urgent',
                'icon' => 'bolt',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $topic = HelpTopic::sole();
        $this->assertSame('Panne de courant', $topic->translate('name', 'fr'));
        $this->assertTrue($topic->department->is($department));
    }

    public function test_help_topic_requires_a_dutch_name(): void
    {
        Livewire::test(CreateHelpTopic::class)
            ->fillForm(['name' => ['nl' => '', 'en' => 'Only English'], 'department_id' => Department::factory()->create()->id])
            ->call('create')
            ->assertHasFormErrors(['name.nl' => 'required']);
    }

    public function test_admin_creates_agents_with_departments(): void
    {
        $department = Department::factory()->create();

        Livewire::test(CreateAgent::class)
            ->fillForm([
                'name' => 'Nieuwe agent',
                'email' => 'agent@example.com',
                'password' => 'secret-password',
                'role' => UserRole::Agent->value,
                'locale' => 'fr',
                'departments' => [$department->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $agent = User::where('email', 'agent@example.com')->sole();
        $this->assertSame(UserRole::Agent, $agent->role);
        $this->assertFalse($agent->hasAccessToAllDepartments());
    }

    public function test_admin_cannot_demote_or_deactivate_themselves(): void
    {
        Livewire::test(EditAgent::class, ['record' => $this->admin->getRouteKey()])
            ->fillForm(['role' => UserRole::Agent->value, 'is_active' => false])
            ->call('save');

        $this->admin->refresh();
        $this->assertSame(UserRole::Admin, $this->admin->role);
        $this->assertTrue($this->admin->is_active);
    }

    public function test_settings_are_saved(): void
    {
        $department = Department::factory()->create();

        Livewire::test(Settings::class)
            ->fillForm(['default_department_id' => $department->id, 'allow_registration' => false, 'show_knowledge_base' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = app(HelpdeskSettings::class);
        $this->assertSame($department->id, (int) $settings->get('default_department_id'));
        $this->assertFalse($settings->get('allow_registration'));
        $this->get('/kb')->assertNotFound();
    }

    public function test_tickets_without_topic_use_the_default_department_and_sla(): void
    {
        $department = Department::factory()->create();
        $sla = SlaPlan::factory()->create();
        app(HelpdeskSettings::class)->save(['default_department_id' => $department->id, 'default_sla_plan_id' => $sla->id]);

        $ticket = app(CreateTicket::class)->handle(User::factory()->create(), ['subject' => 'X', 'message' => 'Y']);

        $this->assertSame($department->id, $ticket->department_id);
        $this->assertSame($sla->id, $ticket->sla_plan_id);
    }
}
