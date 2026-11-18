<?php

namespace Tests\Concerns;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

trait FakesHelloAsso
{
    /** Commande renvoyée par l'API simulée (null = aucun paiement autorisé). */
    protected ?array $helloAssoOrder = null;

    private bool $helloAssoFaked = false;

    protected function configureHelloAsso(): void
    {
        config([
            'services.helloasso.env' => 'sandbox',
            'services.helloasso.client_id' => 'test-client',
            'services.helloasso.client_secret' => 'test-secret',
            'services.helloasso.organization_slug' => 'asso-test',
            'payments.membership_gateway' => 'helloasso',
        ]);
    }

    /**
     * Simule l'API HelloAsso. Peut être rappelée pour changer la commande renvoyée.
     *
     * @param  array<string, mixed>|null  $order
     */
    protected function fakeHelloAsso(?array $order = null, int $checkoutId = 9001): void
    {
        $this->configureHelloAsso();
        $this->helloAssoOrder = $order;

        if ($this->helloAssoFaked) {
            return;
        }

        $this->helloAssoFaked = true;

        Http::fake(function (Request $request) use ($checkoutId) {
            $url = $request->url();

            return match (true) {
                str_ends_with($url, '/oauth2/token') => Http::response(['access_token' => 'tok', 'refresh_token' => 'ref', 'expires_in' => 1799, 'token_type' => 'bearer']),
                str_ends_with($url, '/checkout-intents') && $request->method() === 'POST' => Http::response(['id' => $checkoutId, 'redirectUrl' => 'https://www.helloasso-sandbox.com/checkout/abc']),
                str_ends_with($url, "/checkout-intents/{$checkoutId}") => Http::response(array_filter([
                    'id' => $checkoutId,
                    'metadata' => ['payment_id' => 1],
                    'order' => $this->helloAssoOrder,
                ])),
                default => Http::response([], 404),
            };
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function authorizedOrder(int $amountCents = 2000, string $date = '2026-11-18T10:00:00+01:00'): array
    {
        return [
            'id' => 555,
            'date' => $date,
            'amount' => ['total' => $amountCents, 'vat' => 0, 'discount' => 0],
            'payments' => [['id' => 777, 'amount' => $amountCents, 'state' => 'Authorized']],
        ];
    }
}
