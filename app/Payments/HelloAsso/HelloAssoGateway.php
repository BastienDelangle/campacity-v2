<?php

namespace App\Payments\HelloAsso;

use App\Models\Payment;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\PaymentStart;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Paiement en ligne via HelloAsso Checkout. La redirection de retour ne vaut
 * jamais preuve de paiement : voir HelloAssoPaymentVerifier.
 */
class HelloAssoGateway implements PaymentGateway
{
    /** Durée de validité de l'URL de paiement selon la documentation (15 min), avec marge. */
    private const REDIRECT_TTL_SECONDS = 14 * 60;

    public function __construct(private readonly HelloAssoClient $client) {}

    public function key(): string
    {
        return 'helloasso';
    }

    public function label(): string
    {
        return (string) config('payments.gateways.helloasso.label');
    }

    public function start(Payment $payment): PaymentStart
    {
        $metadata = $payment->metadata ?? [];

        // Réutilise l'URL de paiement encore valide (évite les doublons de panier).
        if (! empty($metadata['redirect_url']) && ($metadata['redirect_created_at'] ?? 0) > time() - self::REDIRECT_TTL_SECONDS) {
            return new PaymentStart(redirectUrl: $metadata['redirect_url']);
        }

        $intent = $this->client->createCheckoutIntent($this->payload($payment));

        $payment->forceFill([
            'provider_checkout_id' => (string) $intent['id'],
            'metadata' => array_merge($metadata, [
                'redirect_url' => $intent['redirectUrl'],
                'redirect_created_at' => time(),
            ]),
        ])->save();

        return new PaymentStart(redirectUrl: $intent['redirectUrl']);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Payment $payment): array
    {
        $returnUrl = fn (string $type) => URL::temporarySignedRoute('payments.helloasso.return', now()->addDays(2), [
            'payment' => $payment->id,
            'type' => $type,
        ]);

        $payload = [
            'totalAmount' => $payment->amount_cents,
            'initialAmount' => $payment->amount_cents,
            'itemName' => Str::limit($payment->payable->paymentDescription(), 245),
            'backUrl' => $returnUrl('back'),
            'errorUrl' => $returnUrl('error'),
            'returnUrl' => $returnUrl('return'),
            'containsDonation' => false,
            'metadata' => [
                'payment_id' => $payment->id,
                'reference' => "CAMP-{$payment->id}",
            ],
        ];

        if ($user = $payment->user) {
            [$firstName, $lastName] = array_pad(explode(' ', trim($user->name), 2), 2, '');
            $payload['payer'] = array_filter([
                'firstName' => Str::limit($firstName, 250, ''),
                'lastName' => Str::limit($lastName ?: $firstName, 250, ''),
                'email' => $user->email,
            ]);
        }

        return $payload;
    }
}
