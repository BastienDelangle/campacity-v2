<?php

namespace Database\Factories;

use App\Models\Territory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Territory>
 */
class TerritoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Territoire '.fake()->unique()->city(),
            'center_latitude' => fake()->latitude(43, 50),
            'center_longitude' => fake()->longitude(-1, 7),
            'default_zoom' => 10,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
