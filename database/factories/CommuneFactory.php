<?php

namespace Database\Factories;

use App\Models\Commune;
use App\Models\Territory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Commune>
 */
class CommuneFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'territory_id' => Territory::factory(),
            'name' => fake()->unique()->city(),
            'insee_code' => fake()->unique()->numerify('#####'),
            'postal_code' => fake()->numerify('#####'),
            'latitude' => fake()->latitude(43, 50),
            'longitude' => fake()->longitude(-1, 7),
        ];
    }
}
