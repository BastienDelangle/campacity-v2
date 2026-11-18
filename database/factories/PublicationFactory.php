<?php

namespace Database\Factories;

use App\Enums\LocationPrecision;
use App\Enums\PublicationNature;
use App\Enums\PublicationOrigin;
use App\Enums\PublicationStatus;
use App\Models\Category;
use App\Models\Commune;
use App\Models\Publication;
use App\Models\PublicationType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Publication>
 */
class PublicationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'publication_type_id' => PublicationType::factory(),
            'category_id' => Category::factory(),
            'nature' => fake()->randomElement(PublicationNature::cases()),
            'origin' => fake()->randomElement(PublicationOrigin::cases()),
            'title' => rtrim(fake()->sentence(5), '.'),
            'description' => fake()->paragraphs(2, true),
            'commune_id' => Commune::factory(),
            'location_precision' => LocationPrecision::Commune,
            'status' => PublicationStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => PublicationStatus::Published,
            'submitted_at' => now()->subDay(),
            'published_at' => now()->subDay(),
            'expires_at' => now()->addDays(30),
        ]);
    }

    public function pending(): static
    {
        return $this->state(['status' => PublicationStatus::Pending, 'submitted_at' => now()]);
    }

    public function withStatus(PublicationStatus $status): static
    {
        return $this->state(['status' => $status]);
    }
}
