<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'job_title' => ['nl' => 'Projectleider', 'fr' => 'Chef de projet', 'en' => 'Project manager'],
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+32 2 123 45 67',
            'is_visible' => true,
            'sort_order' => 0,
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => ['is_visible' => false]);
    }
}
