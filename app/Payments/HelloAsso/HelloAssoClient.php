<?php

namespace App\Payments\HelloAsso;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Client minimal de l'API HelloAsso v5 (Checkout).
 *
 * Authentification OAuth2 client_credentials ; le jeton (~30 min) est mis en
 * cache et renouvelé via le refresh_token, comme le recommande la documentation.
 */
class HelloAssoClient
{
    private const TOKEN_CACHE_KEY = 'helloasso.token';

    public function isConfigured(): bool
    {
        return filled(config('services.helloasso.client_id'))
            && filled(config('services.helloasso.client_secret'))
            && filled(config('services.helloasso.organization_slug'));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{id: int, redirectUrl: string}
     *
     * @throws RequestException
     */
    public function createCheckoutIntent(array $payload): array
    {
        return $this->api()
            ->post($this->organizationPath('/checkout-intents'), $payload)
            ->throw()
            ->json();
    }

    /**
     * @return array<string, mixed>
     *
     * @throws RequestException
     */
    public function getCheckoutIntent(int|string $checkoutIntentId): array
    {
        return $this->api()
            ->get($this->organizationPath('/checkout-intents/'.rawurlencode((string) $checkoutIntentId)))
            ->throw()
            ->json();
    }

    private function api(): PendingRequest
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('HelloAsso n\'est pas configuré (HELLOASSO_CLIENT_ID, HELLOASSO_CLIENT_SECRET, HELLOASSO_ORGANIZATION_SLUG).');
        }

        return Http::baseUrl($this->baseUrl().'/v5')
            ->acceptJson()
            ->asJson()
            ->timeout(15)
            ->withToken($this->accessToken());
    }

    private function accessToken(): string
    {
        $cached = Cache::get(self::TOKEN_CACHE_KEY);

        if (is_array($cached) && ($cached['expires_at'] ?? 0) > time() + 60) {
            return $cached['access_token'];
        }

        $params = is_array($cached) && ! empty($cached['refresh_token'])
            ? ['grant_type' => 'refresh_token', 'refresh_token' => $cached['refresh_token']]
            : ['grant_type' => 'client_credentials', 'client_id' => config('services.helloasso.client_id'), 'client_secret' => config('services.helloasso.client_secret')];

        $response = Http::asForm()->acceptJson()->timeout(15)->post($this->baseUrl().'/oauth2/token', $params);

        // Refresh token expiré ou révoqué : nouvelle authentification complète.
        if ($response->failed() && $params['grant_type'] === 'refresh_token') {
            Cache::forget(self::TOKEN_CACHE_KEY);

            return $this->accessToken();
        }

        $data = $response->throw()->json();

        Cache::put(self::TOKEN_CACHE_KEY, [
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? null,
            'expires_at' => time() + (int) ($data['expires_in'] ?? 1799),
        ], now()->addDays(29));

        return $data['access_token'];
    }

    private function baseUrl(): string
    {
        $env = config('services.helloasso.env') === 'production' ? 'production' : 'sandbox';

        return config("services.helloasso.base_urls.{$env}");
    }

    private function organizationPath(string $path): string
    {
        return '/organizations/'.rawurlencode((string) config('services.helloasso.organization_slug')).$path;
    }
}
