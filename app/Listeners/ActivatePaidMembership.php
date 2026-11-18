<?php

namespace App\Listeners;

use App\Actions\Memberships\Memberships;
use App\Events\PaymentSucceeded;
use App\Models\Membership;

/**
 * Seul point d'activation d'une adhésion : la confirmation du paiement.
 */
class ActivatePaidMembership
{
    public function __construct(private readonly Memberships $memberships) {}

    public function handle(PaymentSucceeded $event): void
    {
        $payable = $event->payment->payable;

        if ($payable instanceof Membership) {
            $this->memberships->activate($payable, $event->payment->paid_at ?? now());
        }
    }
}
