<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketMessage>
 */
class TicketMessageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'user_id' => User::factory(),
            'body' => fake()->paragraphs(2, true),
            'is_internal' => false,
        ];
    }

    public function internal(): static
    {
        return $this->state(fn (array $attributes) => ['is_internal' => true]);
    }
}
