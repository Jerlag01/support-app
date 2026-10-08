<?php

namespace Tests\Feature;

use App\Enums\TicketEventType;
use App\Enums\TicketStatus;
use App\Filament\Agent\Resources\Clients\Pages\EditClient;
use App\Filament\Agent\Resources\Tickets\Pages\CreateTicket;
use App\Filament\Agent\Resources\Tickets\Pages\ListTickets;
use App\Filament\Agent\Resources\Tickets\Pages\ViewTicket;
use App\Models\CannedResponse;
use App\Models\Department;
use App\Models\HelpTopic;
use App\Models\SlaPlan;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketAssigned;
use App\Notifications\TicketReplied;
use App\Notifications\TicketStatusChanged;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class AgentPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->agent = User::factory()->agent()->create(['name' => 'Tom Technieker']);
        $this->actingAs($this->agent);
        Filament::setCurrentPanel('agent');
    }

    public function test_dashboard_and_ticket_pages_render(): void
    {
        $ticket = Ticket::factory()->create();
        $ticket->messages()->create(['user_id' => $ticket->user_id, 'body' => 'Eerste bericht']);

        $this->get('/agent')->assertOk();
        $this->get('/agent/tickets')->assertOk();
        $this->get(route('filament.agent.resources.tickets.view', $ticket))->assertOk()->assertSee('Eerste bericht');
        $this->get('/agent/clients')->assertOk();
        $this->get('/agent/organizations')->assertOk();
        $this->get('/agent/canned-responses')->assertOk();
        $this->get('/agent/faqs')->assertOk();
    }

    public function test_agents_only_see_tickets_of_their_departments_teams_or_assignments(): void
    {
        $service = Department::factory()->create();
        $this->agent->departments()->attach($service);
        $team = Team::factory()->create();
        $team->members()->attach($this->agent);

        $own = Ticket::factory()->create(['department_id' => $service->id]);
        $assigned = Ticket::factory()->assignedTo($this->agent)->create();
        $teamTicket = Ticket::factory()->create(['team_id' => $team->id]);
        $hidden = Ticket::factory()->create();

        Livewire::test(ListTickets::class, ['activeTab' => 'all'])
            ->assertCanSeeTableRecords([$own, $assigned, $teamTicket])
            ->assertCanNotSeeTableRecords([$hidden]);

        $this->get(route('filament.agent.resources.tickets.view', $hidden))->assertNotFound();
    }

    public function test_queues_split_tickets_like_osticket(): void
    {
        $open = Ticket::factory()->create(['is_answered' => false]);
        $answered = Ticket::factory()->create(['is_answered' => true]);
        $overdue = Ticket::factory()->overdue()->create();
        $closed = Ticket::factory()->status(TicketStatus::Closed)->create();
        $mine = Ticket::factory()->assignedTo($this->agent)->create();

        Livewire::test(ListTickets::class, ['activeTab' => 'open'])->assertCanSeeTableRecords([$open, $overdue])->assertCanNotSeeTableRecords([$answered, $closed]);
        Livewire::test(ListTickets::class, ['activeTab' => 'answered'])->assertCanSeeTableRecords([$answered])->assertCanNotSeeTableRecords([$open]);
        Livewire::test(ListTickets::class, ['activeTab' => 'overdue'])->assertCanSeeTableRecords([$overdue])->assertCanNotSeeTableRecords([$open, $closed]);
        Livewire::test(ListTickets::class, ['activeTab' => 'mine'])->assertCanSeeTableRecords([$mine])->assertCanNotSeeTableRecords([$open]);
        Livewire::test(ListTickets::class, ['activeTab' => 'closed'])->assertCanSeeTableRecords([$closed])->assertCanNotSeeTableRecords([$open]);
    }

    public function test_reply_inserts_canned_response_and_signature_and_notifies_client(): void
    {
        $this->agent->update(['signature' => "Tom\nPower Installation"]);
        $ticket = Ticket::factory()->create(['subject' => 'Lamp kapot']);
        $canned = CannedResponse::factory()->create(['body' => 'Dag {client}, we bekijken {reference}.']);

        Livewire::test(ViewTicket::class, ['record' => $ticket->getRouteKey()])
            ->mountAction('reply')
            ->setActionData(['canned_response_id' => $canned->id])
            ->assertActionDataSet(['message' => "Dag {$ticket->user->name}, we bekijken {$ticket->reference}."])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $ticket->refresh();
        $message = $ticket->messages()->sole();
        $this->assertStringContainsString("we bekijken {$ticket->reference}.", $message->body);
        $this->assertStringEndsWith("Tom\nPower Installation", $message->body);
        $this->assertTrue($ticket->is_answered);
        $this->assertSame(TicketStatus::WaitingOnCustomer, $ticket->status);
        $this->assertTrue($ticket->assignee->is($this->agent), 'auto-assigned on first reply');
        Notification::assertSentTo($ticket->user, TicketReplied::class);
    }

    public function test_internal_note_is_private_and_silent(): void
    {
        $ticket = Ticket::factory()->status(TicketStatus::InProgress)->create();

        Livewire::test(ViewTicket::class, ['record' => $ticket->getRouteKey()])
            ->callAction('note', data: ['message' => 'Opletten: hond op terrein.'])
            ->assertHasNoActionErrors();

        $this->assertTrue($ticket->messages()->sole()->is_internal);
        $this->assertSame(TicketStatus::InProgress, $ticket->fresh()->status);
        Notification::assertNothingSent();
    }

    public function test_assigning_notifies_the_agent_and_team_and_logs_events(): void
    {
        $colleague = User::factory()->agent()->create(['name' => 'Collega']);
        $teamMember = User::factory()->agent()->create();
        $team = Team::factory()->create(['name' => 'Ploeg A']);
        $team->members()->attach([$teamMember->id, $this->agent->id]);
        $ticket = Ticket::factory()->create();

        Livewire::test(ViewTicket::class, ['record' => $ticket->getRouteKey()])
            ->callAction('assign', data: ['assigned_to' => $colleague->id, 'team_id' => $team->id])
            ->assertHasNoActionErrors();

        $ticket->refresh();
        $this->assertTrue($ticket->assignee->is($colleague));
        $this->assertTrue($ticket->team->is($team));
        Notification::assertSentTo([$colleague, $teamMember], TicketAssigned::class);
        Notification::assertNotSentTo($this->agent, TicketAssigned::class);

        $events = $ticket->events()->pluck('type');
        $this->assertContains(TicketEventType::Assigned, $events);
        $this->assertContains(TicketEventType::TeamAssigned, $events);
        $this->get(route('filament.agent.resources.tickets.view', $ticket))->assertSee('Tom Technieker wees het ticket toe aan Collega');
    }

    public function test_transfer_moves_department_adds_note_and_drops_agent_without_access(): void
    {
        $from = Department::factory()->create();
        $to = Department::factory()->create(['name' => 'Facturatie']);
        $restricted = User::factory()->agent()->create();
        $restricted->departments()->attach($from);
        $ticket = Ticket::factory()->assignedTo($restricted)->create(['department_id' => $from->id]);

        Livewire::test(ViewTicket::class, ['record' => $ticket->getRouteKey()])
            ->callAction('transfer', data: ['department_id' => $to->id, 'note' => 'Factuurvraag'])
            ->assertHasNoActionErrors();

        $ticket->refresh();
        $this->assertTrue($ticket->department->is($to));
        $this->assertNull($ticket->assigned_to);
        $this->assertTrue($ticket->messages()->sole()->is_internal);
        $this->assertContains(TicketEventType::Transferred, $ticket->events()->pluck('type'));
    }

    public function test_status_change_notifies_client_and_closes(): void
    {
        $ticket = Ticket::factory()->create();

        Livewire::test(ViewTicket::class, ['record' => $ticket->getRouteKey()])
            ->callAction('changeStatus', data: ['status' => TicketStatus::Resolved->value, 'notify' => true])
            ->assertHasNoActionErrors();

        $this->assertNotNull($ticket->fresh()->closed_at);
        Notification::assertSentTo($ticket->user, TicketStatusChanged::class);
    }

    public function test_agent_opens_a_phone_ticket_for_a_client(): void
    {
        $client = User::factory()->create();
        $sla = SlaPlan::factory()->create(['grace_hours' => 24]);
        $topic = HelpTopic::factory()->create(['sla_plan_id' => $sla->id]);

        Livewire::test(CreateTicket::class)
            ->fillForm([
                'user_id' => $client->id,
                'source' => 'phone',
                'help_topic_id' => $topic->id,
                'subject' => 'Telefonische melding',
                'priority' => 'high',
                'message' => 'Klant belde over jaarlijks onderhoud.',
            ])
            ->assertSchemaStateSet(['department_id' => $topic->department_id])
            ->call('create')
            ->assertHasNoFormErrors();

        $ticket = Ticket::sole();
        $this->assertTrue($ticket->user->is($client));
        $this->assertSame('phone', $ticket->source->value);
        $this->assertSame($topic->department_id, $ticket->department_id);
        $this->assertSame($sla->id, $ticket->sla_plan_id);
        $this->assertTrue($ticket->messages()->sole()->author->is($this->agent));
    }

    public function test_agents_manage_clients_but_not_roles(): void
    {
        $client = User::factory()->create();

        Livewire::test(EditClient::class, ['record' => $client->getRouteKey()])
            ->fillForm(['name' => 'Nieuwe naam'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Nieuwe naam', $client->fresh()->name);
        $this->get('/agent/clients/'.User::factory()->agent()->create()->id.'/edit')->assertNotFound();
    }
}
