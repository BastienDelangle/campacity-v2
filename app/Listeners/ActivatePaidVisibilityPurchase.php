<?php

namespace App\Listeners;

use App\Actions\Visibility\VisibilityPurchases;
use App\Events\PaymentSucceeded;
use App\Models\VisibilityPurchase;

/**
 * Seul point d'activation d'une offre de visibilité : la confirmation du paiement.
 */
class ActivatePaidVisibilityPurchase
{
    public function __construct(private readonly VisibilityPurchases $purchases) {}

    public function handle(PaymentSucceeded $event): void
    {
        $payable = $event->payment->payable;

        if ($payable instanceof VisibilityPurchase) {
            $this->purchases->activate($payable);
        }
    }
}
