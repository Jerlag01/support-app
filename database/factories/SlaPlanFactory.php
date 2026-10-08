<?php

namespace Database\Factories;

use App\Models\SlaPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SlaPlan>
 */
class SlaPlanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'grace_hours' => fake()->randomElement([4, 8, 24, 48]),
            'is_active' => true,
        ];
    }
}
