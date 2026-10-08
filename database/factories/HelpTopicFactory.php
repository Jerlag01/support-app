<?php

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Models\Department;
use App\Models\HelpTopic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HelpTopic>
 */
class HelpTopicFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ['nl' => $name, 'fr' => "{$name} (fr)", 'en' => "{$name} (en)"],
            'icon' => 'chat',
            'department_id' => Department::factory(),
            'default_priority' => TicketPriority::Normal,
            'is_public' => true,
            'is_active' => true,
        ];
    }
}
