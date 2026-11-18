<?php

namespace Database\Factories;

use App\Models\PublicationType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PublicationType>
 */
class PublicationTypeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->words(2, true)),
            'is_event' => false,
            'is_active' => true,
        ];
    }

    public function event(): static
    {
        return $this->state(['name' => 'Événement '.fake()->unique()->word(), 'is_event' => true]);
    }
}
