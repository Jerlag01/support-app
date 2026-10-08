<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use App\Support\HelpdeskSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_and_auth_pages_render(): void
    {
        $this->get('/')->assertOk()->assertSee('Power Installation NV');
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
        $this->get('/forgot-password')->assertOk();
    }

    public function test_registration_always_creates_a_customer(): void
    {
        $this->post('/register', [
            'name' => 'Jan Peeters',
            'company' => 'Peeters BV',
            'email' => 'jan@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'role' => 'admin',
        ])->assertRedirect(route('tickets.index'));

        $user = User::where('email', 'jan@example.com')->firstOrFail();
        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertSame('Peeters BV', $user->company);
        $this->assertAuthenticatedAs($user);
    }

    public function test_customer_logs_in_to_the_portal(): void
    {
        $customer = User::factory()->create();

        $this->post('/login', ['email' => $customer->email, 'password' => 'password'])
            ->assertRedirect(route('tickets.index'));

        $this->assertAuthenticatedAs($customer);
    }

    public function test_agents_are_sent_to_the_agent_panel_after_login(): void
    {
        $agent = User::factory()->agent()->create();

        $this->post('/login', ['email' => $agent->email, 'password' => 'password'])
            ->assertRedirect('/agent');
    }

    public function test_inactive_users_cannot_log_in(): void
    {
        $user = User::factory()->inactive()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_panel_access_follows_the_role(): void
    {
        $client = User::factory()->create();
        $this->actingAs($client)->get('/agent')->assertForbidden();
        $this->actingAs($client)->get('/admin')->assertForbidden();

        $agent = User::factory()->agent()->create();
        $this->actingAs($agent)->get('/agent')->assertOk();
        $this->actingAs($agent)->get('/admin')->assertForbidden();

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get('/agent')->assertOk();
        $this->actingAs($admin)->get('/admin')->assertOk();

        $this->actingAs(User::factory()->admin()->inactive()->create())->get('/agent')->assertForbidden();
    }

    public function test_registration_can_be_switched_off(): void
    {
        app(HelpdeskSettings::class)->save(['allow_registration' => false]);

        $this->get('/register')->assertNotFound();
        $this->post('/register', ['name' => 'X', 'email' => 'x@example.com', 'password' => 'secret-password', 'password_confirmation' => 'secret-password'])->assertNotFound();
        $this->get('/login')->assertOk()->assertDontSee(route('register'));
        $this->assertDatabaseMissing('users', ['email' => 'x@example.com']);
    }

    public function test_new_clients_join_their_organization_by_email_domain(): void
    {
        $organization = Organization::factory()->create(['domain' => 'Peeters.be']);

        $this->post('/register', [
            'name' => 'An Peeters',
            'email' => 'an@peeters.be',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ]);

        $this->assertTrue(User::where('email', 'an@peeters.be')->sole()->organization->is($organization));
    }

    public function test_language_switch_is_remembered_on_the_account(): void
    {
        $customer = User::factory()->create(['locale' => 'nl']);

        $this->actingAs($customer)->get('/language/fr')->assertRedirect();

        $this->assertSame('fr', $customer->fresh()->locale);
        $this->get('/tickets')->assertSee('Mes tickets');
    }

    public function test_unknown_language_is_rejected(): void
    {
        $this->get('/language/de')->assertNotFound();
    }
}
