<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Payments\HelloAsso\HelloAssoPaymentVerifier;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Vérification asynchrone déclenchée par une notification HelloAsso
 * (dédoublonnée : plusieurs notifications pour un même paiement = une vérification).
 */
class VerifyHelloAssoPayment implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $uniqueFor = 60;

    public function __construct(public readonly Payment $payment) {}

    public function uniqueId(): string
    {
        return (string) $this->payment->id;
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [10, 30, 120, 600];
    }

    public function handle(HelloAssoPaymentVerifier $verifier): void
    {
        $verifier->verify($this->payment->fresh());
    }
}
