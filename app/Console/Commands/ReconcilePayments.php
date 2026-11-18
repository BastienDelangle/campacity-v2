<?php

namespace App\Console\Commands;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Payments\HelloAsso\HelloAssoPaymentVerifier;
use App\Payments\PaymentManager;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('payments:reconcile')]
#[Description('Rapproche les paiements HelloAsso en attente avec l\'API (notification manquée, paiement abandonné)')]
class ReconcilePayments extends Command
{
    public function handle(HelloAssoPaymentVerifier $verifier, PaymentManager $payments): int
    {
        $abandonAfter = (int) config('services.helloasso.abandon_after_minutes', 120);
        $checked = 0;
        $abandoned = 0;

        Payment::query()
            ->where('provider', 'helloasso')
            ->where('status', PaymentStatus::Pending)
            ->where('created_at', '>', now()->subDays(3))
            ->each(function (Payment $payment) use ($verifier, $payments, $abandonAfter, &$checked, &$abandoned) {
                $status = filled($payment->provider_checkout_id) ? $verifier->verify($payment) : PaymentStatus::Pending;
                $checked++;

                if ($status === PaymentStatus::Pending && $payment->created_at->lt(now()->subMinutes($abandonAfter))) {
                    $payments->markFailed($payment, PaymentStatus::Cancelled, 'Paiement abandonné (aucune autorisation reçue)');
                    $abandoned++;
                }
            });

        $this->info("Paiements vérifiés : {$checked} · abandonnés : {$abandoned}");

        return self::SUCCESS;
    }
}
