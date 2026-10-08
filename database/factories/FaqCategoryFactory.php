<?php

namespace Database\Factories;

use App\Models\FaqCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FaqCategory>
 */
class FaqCategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ['nl' => $name, 'fr' => "{$name} (fr)", 'en' => "{$name} (en)"],
            'is_public' => true,
        ];
    }
}
