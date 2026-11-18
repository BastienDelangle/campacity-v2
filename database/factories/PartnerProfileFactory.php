<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\PartnerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PartnerProfile>
 */
class PartnerProfileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'headline' => fake()->sentence(6),
            'presentation' => fake()->paragraphs(2, true),
            'activity' => fake()->jobTitle(),
        ];
    }

    public function visible(): static
    {
        return $this->state(['visible_from' => now()->subDay(), 'visible_until' => now()->addMonth()]);
    }
}
