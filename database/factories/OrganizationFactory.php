<?php

namespace Database\Factories;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationType;
use App\Models\Commune;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'type' => fake()->randomElement(OrganizationType::cases()),
            'description' => fake()->paragraph(),
            'address' => fake()->streetAddress(),
            'commune_id' => Commune::factory(),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'website' => fake()->url(),
            'show_email' => false,
            'show_phone' => false,
        ];
    }

    public function ownedBy(User $user): static
    {
        return $this->hasAttached($user, ['role' => OrganizationRole::Owner->value], 'members');
    }

    public function editedBy(User $user): static
    {
        return $this->hasAttached($user, ['role' => OrganizationRole::Editor->value], 'members');
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
