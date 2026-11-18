<?php

namespace App\Payments\Contracts;

use App\Models\Payment;
use App\Payments\PaymentStart;

interface PaymentGateway
{
    /**
     * Identifiant stocké dans payments.provider.
     */
    public function key(): string;

    public function label(): string;

    /**
     * Démarre le paiement : renvoie une URL de redirection (paiement en ligne)
     * ou des instructions (paiement hors ligne).
     */
    public function start(Payment $payment): PaymentStart;
}
