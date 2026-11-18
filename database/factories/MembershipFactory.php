<?php

namespace Database\Factories;

use App\Enums\MembershipStatus;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Membership>
 */
class MembershipFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'amount_cents' => 2000,
            'status' => MembershipStatus::PendingPayment,
        ];
    }

    public function activeUntil(\DateTimeInterface $endsAt): static
    {
        return $this->state(fn () => [
            'status' => MembershipStatus::Active,
            'paid_at' => now()->subMonths(6),
            'starts_at' => Carbon::instance($endsAt)->subYear(),
            'ends_at' => $endsAt,
            'activated_at' => now()->subMonths(6),
        ]);
    }
}
