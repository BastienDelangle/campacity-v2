<?php

namespace App\Payments\Gateways;

use App\Models\Payment;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\PaymentStart;

/**
 * Paiement hors ligne (virement, chèque) confirmé par un administrateur.
 */
class ManualGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'manual';
    }

    public function label(): string
    {
        return (string) config('payments.gateways.manual.label');
    }

    public function start(Payment $payment): PaymentStart
    {
        return new PaymentStart(instructions: (string) config('payments.gateways.manual.instructions'));
    }
}
