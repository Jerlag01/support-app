<?php

namespace Database\Seeders;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Department;
use App\Models\HelpTopic;
use App\Models\Organization;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Local demo data only: `php artisan db:seed --class=DemoSeeder`. All demo accounts use the password "password".
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(HelpdeskDefaultsSeeder::class);

        $service = Department::where('name', 'Service & onderhoud')->firstOrFail();
        $admin = User::factory()->admin()->create(['name' => 'Demo Admin', 'email' => 'admin@example.com']);
        $technician = User::factory()->agent()->create(['name' => 'Demo Technieker', 'email' => 'tech@example.com']);
        $technician->departments()->attach($service);
        Team::factory()->create(['name' => 'Interventieploeg', 'lead_id' => $technician->id])->members()->attach($technician);

        $organization = Organization::factory()->create(['name' => 'Demo BV', 'domain' => 'example.com']);
        $client = User::factory()->for($organization)->create(['name' => 'Demo Klant', 'company' => 'Demo BV', 'email' => 'klant@example.com']);
        $topics = HelpTopic::orderBy('sort_order')->get();

        $tickets = [
            [TicketStatus::Open, TicketPriority::Urgent, null, 'Differentieel slaat af in technische ruimte', 0],
            [TicketStatus::InProgress, TicketPriority::High, $technician, 'Noodverlichting gang 2e verdieping defect', 1],
            [TicketStatus::WaitingOnCustomer, TicketPriority::Normal, $admin, 'Offerte uitbreiding laadpalen parking', 3],
            [TicketStatus::Resolved, TicketPriority::Low, $technician, 'Vraag over factuur onderhoudscontract', 4],
        ];

        foreach ($tickets as [$status, $priority, $assignee, $subject, $topicIndex]) {
            $topic = $topics[$topicIndex];
            $ticket = Ticket::factory()->for($client)->create([
                'subject' => $subject,
                'status' => $status,
                'priority' => $priority,
                'help_topic_id' => $topic->id,
                'department_id' => $topic->department_id,
                'sla_plan_id' => $topic->sla_plan_id ?? $topic->department->sla_plan_id,
                'assigned_to' => $assignee?->id,
            ]);

            TicketMessage::factory()->for($ticket)->for($client, 'author')->create();

            if ($assignee) {
                TicketMessage::factory()->for($ticket)->for($assignee, 'author')->create();
            }
        }
    }
}
