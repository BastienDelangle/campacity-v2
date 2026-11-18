<?php

namespace App\Payments\HelloAsso;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Payments\PaymentManager;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Vérification serveur d'un paiement HelloAsso : seule source de vérité pour
 * activer une adhésion ou un achat. Appelée sur notification, au retour du
 * navigateur et par la tâche de rapprochement ; ne fait confiance qu'à l'API.
 */
class HelloAssoPaymentVerifier
{
    /** États HelloAsso définitivement négatifs. */
    private const FAILED_STATES = ['Refused', 'Canceled', 'Abandoned', 'Error'];

    public function __construct(
        private readonly HelloAssoClient $client,
        private readonly PaymentManager $payments,
    ) {}

    public function verify(Payment $payment): PaymentStatus
    {
        if ($payment->provider !== 'helloasso' || blank($payment->provider_checkout_id) || $payment->status !== PaymentStatus::Pending) {
            return $payment->status;
        }

        try {
            $intent = $this->client->getCheckoutIntent($payment->provider_checkout_id);
        } catch (RequestException $exception) {
            Log::warning('HelloAsso : vérification impossible', ['payment' => $payment->id, 'status' => $exception->response->status()]);

            return $payment->status;
        }

        $order = $intent['order'] ?? null;
        $states = collect($order['payments'] ?? [])->pluck('state');

        if ($order && $states->contains('Authorized')) {
            $paid = collect($order['payments'])->firstWhere('state', 'Authorized');
            $orderTotal = (int) ($order['amount']['total'] ?? $paid['amount'] ?? 0);

            // Contrôle du montant : un panier modifié ne doit pas valider un paiement partiel.
            if ($orderTotal !== $payment->amount_cents) {
                Log::error('HelloAsso : montant incohérent', ['payment' => $payment->id, 'expected' => $payment->amount_cents, 'received' => $orderTotal]);

                return $payment->status;
            }

            $this->payments->markSucceeded(
                $payment,
                reference: 'order-'.$order['id'],
                metadata: ['helloasso_order_id' => $order['id'], 'helloasso_payment_id' => $paid['id'] ?? null],
                paidAt: isset($order['date']) ? Carbon::parse($order['date'])->setTimezone(config('app.timezone')) : null,
            );

            return PaymentStatus::Succeeded;
        }

        if ($states->isNotEmpty() && $states->every(fn ($state) => in_array($state, self::FAILED_STATES, true))) {
            $this->payments->markFailed($payment, PaymentStatus::Failed, 'Paiement refusé par HelloAsso', ['helloasso_states' => $states->all()]);

            return PaymentStatus::Failed;
        }

        return PaymentStatus::Pending;
    }
}
