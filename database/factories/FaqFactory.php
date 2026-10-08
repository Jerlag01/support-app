<?php

namespace Database\Factories;

use App\Models\Faq;
use App\Models\FaqCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
class FaqFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $question = rtrim(fake()->sentence(6), '.').'?';

        return [
            'faq_category_id' => FaqCategory::factory(),
            'question' => ['nl' => $question],
            'answer' => ['nl' => fake()->paragraph()],
            'is_published' => true,
        ];
    }
}
