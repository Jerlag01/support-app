<?php

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subject' => fake()->sentence(5),
            'department_id' => Department::factory(),
            'priority' => TicketPriority::Normal,
            'status' => TicketStatus::Open,
            'site_address' => fake()->optional()->address(),
            'last_activity_at' => now(),
        ];
    }

    public function status(TicketStatus $status): static
    {
        return $this->state(fn (array $attributes) => ['status' => $status]);
    }

    public function assignedTo(User $agent): static
    {
        return $this->state(fn (array $attributes) => ['assigned_to' => $agent->id]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => ['due_at' => now()->subHour()]);
    }
}
