<?php

namespace App\Payments;

use App\Enums\PaymentStatus;
use App\Events\PaymentFailed;
use App\Events\PaymentSucceeded;
use App\Models\Payment;
use App\Models\User;
use App\Payments\Contracts\PaymentGateway;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Point d'entrée unique des paiements : création, démarrage et changements
 * de statut. Les transitions sont idempotentes (une notification reçue deux
 * fois ne déclenche qu'une activation).
 */
class PaymentManager
{
    public function gateway(string $key): PaymentGateway
    {
        $class = config("payments.gateways.{$key}.class");

        if (! $class) {
            throw new InvalidArgumentException("Prestataire de paiement inconnu : {$key}");
        }

        return app($class);
    }

    public function create(Model $payable, ?User $user, int $amountCents, string $gatewayKey): Payment
    {
        $gateway = $this->gateway($gatewayKey);

        return $payable->payments()->create([
            'user_id' => $user?->id,
            'provider' => $gateway->key(),
            'amount_cents' => $amountCents,
            'status' => PaymentStatus::Pending,
        ]);
    }

    public function start(Payment $payment): PaymentStart
    {
        return $this->gateway($payment->provider)->start($payment);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function markSucceeded(Payment $payment, ?string $reference = null, ?User $confirmedBy = null, ?string $note = null, array $metadata = []): Payment
    {
        return DB::transaction(function () use ($payment, $reference, $confirmedBy, $note, $metadata) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($payment->status === PaymentStatus::Succeeded) {
                return $payment;
            }

            if ($payment->status !== PaymentStatus::Pending) {
                throw new InvalidArgumentException("Le paiement #{$payment->id} est déjà au statut « {$payment->status->getLabel()} ».");
            }

            $payment->forceFill([
                'status' => PaymentStatus::Succeeded,
                'paid_at' => now(),
                'provider_reference' => $reference ?? $payment->provider_reference,
                'confirmed_by' => $confirmedBy?->id,
                'note' => $note ?? $payment->note,
                'metadata' => array_merge($payment->metadata ?? [], $metadata),
            ])->save();

            PaymentSucceeded::dispatch($payment);

            return $payment;
        });
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function markFailed(Payment $payment, PaymentStatus $status = PaymentStatus::Failed, ?string $note = null, array $metadata = []): Payment
    {
        return DB::transaction(function () use ($payment, $status, $note, $metadata) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($payment->status !== PaymentStatus::Pending) {
                return $payment;
            }

            $payment->forceFill([
                'status' => $status,
                'note' => $note ?? $payment->note,
                'metadata' => array_merge($payment->metadata ?? [], $metadata),
            ])->save();

            PaymentFailed::dispatch($payment);

            return $payment;
        });
    }
}
