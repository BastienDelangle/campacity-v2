<?php

namespace Database\Factories;

use App\Enums\ReportReason;
use App\Models\Publication;
use App\Models\PublicationReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PublicationReport>
 */
class PublicationReportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'publication_id' => Publication::factory()->published(),
            'reason' => fake()->randomElement(ReportReason::cases()),
            'comment' => fake()->sentence(),
            'ip_hash' => hash('sha256', fake()->ipv4()),
        ];
    }
}
