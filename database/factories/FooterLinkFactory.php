<?php

namespace Database\Factories;

use App\Models\FooterLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FooterLink>
 */
class FooterLinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'label' => ['nl' => 'Algemene voorwaarden', 'fr' => 'Conditions générales', 'en' => 'Terms and conditions'],
            'url' => 'https://example.com/'.fake()->unique()->slug(2),
            'open_in_new_tab' => false,
            'is_visible' => true,
            'sort_order' => 0,
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => ['is_visible' => false]);
    }
}
