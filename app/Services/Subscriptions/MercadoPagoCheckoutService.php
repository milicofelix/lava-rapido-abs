<?php

namespace App\Services\Subscriptions;

use App\Models\Subscription;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class MercadoPagoCheckoutService
{
    public function isConfigured(): bool
    {
        return filled(config('services.mercado_pago.access_token'));
    }

    public function environmentLabel(): string
    {
        if (! $this->isConfigured()) {
            return 'manual';
        }

        return $this->isLiveToken() ? 'producao' : 'teste';
    }

    public function isLiveCheckoutAllowed(): bool
    {
        return ! $this->isLiveToken() || (bool) config('services.mercado_pago.live_enabled');
    }

    public function createPreference(Subscription $subscription): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Mercado Pago nao configurado.');
        }

        if (! $this->isLiveCheckoutAllowed()) {
            throw new RuntimeException('Checkout real bloqueado. Defina MERCADO_PAGO_LIVE_ENABLED=true para liberar cobrancas em producao.');
        }

        $subscription->loadMissing(['plan', 'washLocation']);

        $externalReference = $subscription->external_reference ?: 'subscription-'.$subscription->id.'-'.Str::lower(Str::random(10));

        if ($subscription->external_reference !== $externalReference) {
            $subscription->forceFill([
                'payment_provider' => 'mercado_pago',
                'external_reference' => $externalReference,
            ])->save();
        }

        $subscriptionUrl = route('subscriptions.show');
        $payload = [
            'items' => [[
                'id' => (string) $subscription->plan_id,
                'title' => 'AutoFlow - '.$subscription->plan->name,
                'quantity' => 1,
                'currency_id' => 'BRL',
                'unit_price' => (float) $subscription->plan->price,
            ]],
            'payer' => [
                'name' => $subscription->washLocation->name,
            ],
            'external_reference' => $externalReference,
            'notification_url' => route('webhooks.mercado-pago'),
            'back_urls' => [
                'success' => $subscriptionUrl,
                'failure' => $subscriptionUrl,
                'pending' => $subscriptionUrl,
            ],
        ];

        if (Str::startsWith($subscriptionUrl, 'https://')) {
            $payload['auto_return'] = 'approved';
        }

        $response = $this->client()
            ->post('/checkout/preferences', $payload)
            ->throw()
            ->json();

        $subscription->forceFill([
            'payment_provider' => 'mercado_pago',
            'external_reference' => $externalReference,
            'provider_preference_id' => $response['id'] ?? null,
            'checkout_url' => $response['init_point'] ?? $response['sandbox_init_point'] ?? null,
            'provider_payload' => $response,
        ])->save();

        return $response;
    }

    public function findPayment(string|int $paymentId): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Mercado Pago nao configurado.');
        }

        return $this->client()
            ->get('/v1/payments/'.$paymentId)
            ->throw()
            ->json();
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.mercado_pago.base_url'), '/'))
            ->acceptJson()
            ->asJson()
            ->withToken((string) config('services.mercado_pago.access_token'));
    }

    private function isLiveToken(): bool
    {
        return Str::startsWith((string) config('services.mercado_pago.access_token'), 'APP_USR-');
    }
}
