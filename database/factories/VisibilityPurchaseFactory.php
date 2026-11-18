<?php

namespace Database\Factories;

use App\Enums\PurchaseStatus;
use App\Models\Publication;
use App\Models\User;
use App\Models\VisibilityProduct;
use App\Models\VisibilityPurchase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VisibilityPurchase>
 */
class VisibilityPurchaseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $product = VisibilityProduct::factory()->create();

        return [
            'user_id' => User::factory(),
            'visibility_product_id' => $product->id,
            'publication_id' => Publication::factory()->published(),
            'product_name' => $product->name,
            'kind' => $product->kind,
            'amount_cents' => $product->price_cents,
            'duration_days' => $product->duration_days,
            'ranking_weight' => $product->ranking_weight,
            'status' => PurchaseStatus::PendingPayment,
        ];
    }

    public function active(?int $days = 30): static
    {
        return $this->state(fn () => [
            'status' => PurchaseStatus::Active,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays($days),
            'activated_at' => now()->subDay(),
        ]);
    }
}
