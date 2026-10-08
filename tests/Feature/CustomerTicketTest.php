<?php

namespace Tests\Feature;

use App\Enums\TicketEventType;
use App\Enums\TicketStatus;
use App\Models\Department;
use App\Models\HelpTopic;
use App\Models\SlaPlan;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Notifications\TicketCreated;
use App\Notifications\TicketReplied;
use App\Support\HelpdeskSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerTicketTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Notification::fake();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/tickets')->assertRedirect('/login');
        $this->get('/tickets/create')->assertRedirect('/login');
    }

    public function test_customer_creates_a_ticket_with_attachments(): void
    {
        $customer = User::factory()->create();
        $sla = SlaPlan::factory()->create(['grace_hours' => 4]);
        $topic = HelpTopic::factory()->create(['sla_plan_id' => $sla->id]);
        $departmentAgent = User::factory()->agent()->create();
        $departmentAgent->departments()->attach($topic->department_id);
        $unrestrictedAgent = User::factory()->agent()->create();
        $otherDepartmentAgent = User::factory()->agent()->create();
        $otherDepartmentAgent->departments()->attach(Department::factory()->create());
        $inactiveAgent = User::factory()->agent()->inactive()->create();

        $this->freezeSecond();

        $response = $this->actingAs($customer)->post('/tickets', [
            'subject' => 'Stroomuitval in hal 3',
            'help_topic_id' => $topic->id,
            'priority' => 'urgent',
            'site_address' => 'Industrieweg 1, Gent',
            'message' => 'Sinds vanochtend geen stroom meer.',
            'attachments' => [UploadedFile::fake()->image('kast.jpg'), UploadedFile::fake()->create('schema.pdf', 200, 'application/pdf')],
        ]);

        $ticket = Ticket::sole();
        $response->assertRedirect(route('tickets.show', $ticket));

        $this->assertSame(sprintf('PI-%06d', $ticket->id), $ticket->reference);
        $this->assertSame(TicketStatus::Open, $ticket->status);
        $this->assertTrue($ticket->user->is($customer));
        $this->assertSame($topic->department_id, $ticket->department_id);
        $this->assertSame($sla->id, $ticket->sla_plan_id);
        $this->assertTrue($ticket->due_at->equalTo(now()->addHours(4)));
        $this->assertFalse($ticket->is_answered);
        $this->assertSame(TicketEventType::Created, $ticket->events()->sole()->type);
        $this->assertSame('Sinds vanochtend geen stroom meer.', $ticket->messages()->sole()->body);
        $this->assertCount(2, $ticket->attachments);
        Storage::disk('local')->assertExists($ticket->attachments->first()->path);

        Notification::assertSentTo([$departmentAgent, $unrestrictedAgent], TicketCreated::class);
        Notification::assertNotSentTo([$customer, $inactiveAgent, $otherDepartmentAgent], TicketCreated::class);
    }

    public function test_hidden_or_inactive_help_topics_cannot_be_chosen(): void
    {
        $customer = User::factory()->create();
        $internal = HelpTopic::factory()->create(['is_public' => false]);

        $this->actingAs($customer)->get('/tickets/create')->assertDontSee($internal->label());
        $this->post('/tickets', [
            'subject' => 'Test',
            'help_topic_id' => $internal->id,
            'priority' => 'normal',
            'message' => 'Test',
        ])->assertSessionHasErrors('help_topic_id');
    }

    public function test_ticket_creation_is_validated(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->post('/tickets', [
            'subject' => '',
            'help_topic_id' => 999,
            'priority' => 'normal',
            'message' => '',
            'attachments' => [UploadedFile::fake()->create('virus.exe', 10)],
        ])->assertSessionHasErrors(['subject', 'help_topic_id', 'message', 'attachments.0']);

        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_customer_only_sees_their_own_tickets(): void
    {
        $customer = User::factory()->create();
        $own = Ticket::factory()->for($customer)->create(['subject' => 'Mijn eigen ticket']);
        $other = Ticket::factory()->create(['subject' => 'Ticket van iemand anders']);

        $this->actingAs($customer)->get('/tickets')
            ->assertOk()
            ->assertSee('Mijn eigen ticket')
            ->assertDontSee('Ticket van iemand anders');

        $this->get(route('tickets.show', $own))->assertOk();
        $this->get(route('tickets.show', $other))->assertForbidden();
        $this->post(route('tickets.messages.store', $other), ['message' => 'Hallo'])->assertForbidden();
    }

    public function test_ticket_list_filters_on_status_and_search(): void
    {
        $customer = User::factory()->create();
        Ticket::factory()->for($customer)->create(['subject' => 'Lopende storing']);
        Ticket::factory()->for($customer)->status(TicketStatus::Closed)->create(['subject' => 'Oude vraag']);

        $this->actingAs($customer)->get('/tickets')->assertSee('Lopende storing')->assertDontSee('Oude vraag');
        $this->get('/tickets?status=closed')->assertSee('Oude vraag')->assertDontSee('Lopende storing');
        $this->get('/tickets?search=storing')->assertSee('Lopende storing');
        $this->get('/tickets?search=onbestaand')->assertDontSee('Lopende storing');
    }

    public function test_internal_notes_are_hidden_from_the_customer(): void
    {
        $customer = User::factory()->create();
        $agent = User::factory()->agent()->create();
        $ticket = Ticket::factory()->for($customer)->create();
        TicketMessage::factory()->for($ticket)->for($agent, 'author')->create(['body' => 'Publiek antwoord']);
        $note = TicketMessage::factory()->for($ticket)->for($agent, 'author')->internal()->create(['body' => 'Geheime interne notitie']);
        $attachment = $note->attachments()->create(['path' => 'tickets/x/secret.pdf', 'original_name' => 'secret.pdf', 'size' => 10]);

        $this->actingAs($customer)->get(route('tickets.show', $ticket))
            ->assertSee('Publiek antwoord')
            ->assertDontSee('Geheime interne notitie');

        $this->get(route('attachments.show', $attachment))->assertNotFound();
    }

    public function test_customer_downloads_their_own_attachment_only(): void
    {
        Storage::disk('local')->put('tickets/1/foto.jpg', 'image-bytes');
        $customer = User::factory()->create();
        $message = TicketMessage::factory()->for(Ticket::factory()->for($customer))->for($customer, 'author')->create();
        $attachment = $message->attachments()->create(['path' => 'tickets/1/foto.jpg', 'original_name' => 'foto.jpg', 'size' => 11]);

        $this->actingAs($customer)->get(route('attachments.show', $attachment))
            ->assertOk()
            ->assertDownload('foto.jpg');

        $this->actingAs(User::factory()->create())->get(route('attachments.show', $attachment))->assertForbidden();
    }

    public function test_customer_reply_reopens_the_ticket_and_notifies_the_assignee(): void
    {
        $customer = User::factory()->create();
        $assignee = User::factory()->agent()->create();
        $otherAgent = User::factory()->agent()->create();
        $ticket = Ticket::factory()->for($customer)->assignedTo($assignee)->status(TicketStatus::WaitingOnCustomer)->create(['is_answered' => true]);

        $this->actingAs($customer)
            ->post(route('tickets.messages.store', $ticket), ['message' => 'Hier is de extra info.'])
            ->assertRedirect();

        $this->assertSame(TicketStatus::Open, $ticket->fresh()->status);
        $this->assertFalse($ticket->fresh()->is_answered);
        $this->assertSame('Hier is de extra info.', $ticket->messages()->latest('id')->first()->body);
        Notification::assertSentTo($assignee, TicketReplied::class);
        Notification::assertNotSentTo($otherAgent, TicketReplied::class);
    }

    public function test_reply_on_unassigned_ticket_notifies_the_team_or_department(): void
    {
        $customer = User::factory()->create();
        $teamMember = User::factory()->agent()->create();
        $team = Team::factory()->create();
        $team->members()->attach($teamMember);
        $departmentAgent = User::factory()->agent()->create();
        $teamTicket = Ticket::factory()->for($customer)->create(['team_id' => $team->id]);
        $departmentTicket = Ticket::factory()->for($customer)->create();
        $departmentAgent->departments()->attach($departmentTicket->department_id);
        $teamMember->departments()->attach(Department::factory()->create());

        $this->actingAs($customer)->post(route('tickets.messages.store', $teamTicket), ['message' => 'Nog nieuws?']);
        Notification::assertSentTo($teamMember, TicketReplied::class);
        Notification::assertNotSentTo($departmentAgent, TicketReplied::class);

        $this->post(route('tickets.messages.store', $departmentTicket), ['message' => 'En hier?']);
        Notification::assertSentTo($departmentAgent, TicketReplied::class);
    }

    public function test_reopening_can_be_disabled(): void
    {
        app(HelpdeskSettings::class)->save(['clients_can_reopen' => false]);
        $customer = User::factory()->create();
        $ticket = Ticket::factory()->for($customer)->status(TicketStatus::Closed)->create();

        $this->actingAs($customer)->post(route('tickets.reopen', $ticket))->assertForbidden();
    }

    public function test_reopening_restarts_the_sla(): void
    {
        $customer = User::factory()->create();
        $sla = SlaPlan::factory()->create(['grace_hours' => 8]);
        $ticket = Ticket::factory()->for($customer)->status(TicketStatus::Closed)->create(['sla_plan_id' => $sla->id, 'due_at' => now()->subWeek()]);
        $this->freezeSecond();

        $this->actingAs($customer)->post(route('tickets.reopen', $ticket));

        $this->assertTrue($ticket->fresh()->due_at->equalTo(now()->addHours(8)));
    }

    public function test_customer_closes_and_reopens_a_ticket(): void
    {
        $customer = User::factory()->create();
        $ticket = Ticket::factory()->for($customer)->create();

        $this->actingAs($customer)->post(route('tickets.close', $ticket))->assertRedirect();
        $ticket->refresh();
        $this->assertSame(TicketStatus::Closed, $ticket->status);
        $this->assertNotNull($ticket->closed_at);

        $this->post(route('tickets.messages.store', $ticket), ['message' => 'Toch nog iets'])->assertForbidden();

        $this->post(route('tickets.reopen', $ticket))->assertRedirect();
        $ticket->refresh();
        $this->assertSame(TicketStatus::Open, $ticket->status);
        $this->assertNull($ticket->closed_at);
    }

    public function test_deleting_a_ticket_removes_its_files(): void
    {
        $ticket = Ticket::factory()->create();
        Storage::disk('local')->put($ticket->attachmentDirectory().'/a.pdf', 'x');

        $ticket->delete();

        Storage::disk('local')->assertMissing($ticket->attachmentDirectory().'/a.pdf');
    }

    public function test_customer_updates_profile_and_password(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->put('/profile', [
            'name' => 'Nieuwe Naam',
            'company' => 'Nieuw BV',
            'email' => $customer->email,
            'phone' => '+32 2 123 45 67',
            'locale' => 'en',
            'current_password' => 'password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertSessionHasNoErrors();

        $customer->refresh();
        $this->assertSame('Nieuwe Naam', $customer->name);
        $this->assertSame('en', $customer->locale);
        $this->assertTrue(password_verify('new-password-123', $customer->password));
    }

    public function test_password_change_requires_current_password(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->put('/profile', [
            'name' => $customer->name,
            'email' => $customer->email,
            'locale' => 'nl',
            'current_password' => 'wrong',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertSessionHasErrors('current_password');
    }
}
