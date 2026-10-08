<?php

namespace Database\Factories;

use App\Models\CannedResponse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CannedResponse>
 */
class CannedResponseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'body' => 'Beste {client}, bedankt voor uw melding {reference}. Groeten, {agent}',
            'is_active' => true,
        ];
    }
}
