<?php

namespace Database\Factories;

use App\Enums\VisibilityKind;
use App\Models\VisibilityProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VisibilityProduct>
 */
class VisibilityProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Mise en avant '.fake()->unique()->word(),
            'kind' => VisibilityKind::Featured,
            'price_cents' => 2000,
            'duration_days' => 30,
            'ranking_weight' => 10,
            'is_active' => true,
        ];
    }

    public function boost(): static
    {
        return $this->state(['name' => 'Boost '.fake()->unique()->word(), 'kind' => VisibilityKind::Boost, 'price_cents' => 5000, 'ranking_weight' => 20]);
    }

    public function premium(): static
    {
        return $this->state(['name' => 'Premium '.fake()->unique()->word(), 'kind' => VisibilityKind::PremiumPartner, 'price_cents' => 10000, 'ranking_weight' => 0]);
    }
}
