<?php

namespace App\Models\Concerns;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Modèle pouvant faire l'objet d'un paiement (achat de visibilité, adhésion…).
 */
trait IsPayable
{
    /**
     * @return MorphMany<Payment, $this>
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function latestPayment(): ?Payment
    {
        return $this->payments()->latest('id')->first();
    }

    public function isPaid(): bool
    {
        return $this->payments()->where('status', PaymentStatus::Succeeded)->exists();
    }

    abstract public function paymentDescription(): string;
}
