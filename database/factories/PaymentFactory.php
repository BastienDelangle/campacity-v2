<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\VisibilityPurchase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payable_type' => 'visibility_purchase',
            'payable_id' => VisibilityPurchase::factory(),
            'provider' => 'manual',
            'amount_cents' => 2000,
            'status' => 'pending',
        ];
    }
}
