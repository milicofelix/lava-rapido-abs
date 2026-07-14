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

        return $this->isProductionEnvironment() ? 'producao' : 'teste';
    }

    public function isLiveCheckoutAllowed(): bool
    {
        return ! $this->isProductionEnvironment() || (bool) config('services.mercado_pago.live_enabled');
    }

    public function checkoutUrls(): array
    {
        $defaultSubscriptionUrl = route('subscriptions.show');

        return [
            'notification' => config('services.mercado_pago.notification_url') ?: route('webhooks.mercado-pago'),
            'success' => config('services.mercado_pago.success_url') ?: $defaultSubscriptionUrl,
            'failure' => config('services.mercado_pago.failure_url') ?: $defaultSubscriptionUrl,
            'pending' => config('services.mercado_pago.pending_url') ?: $defaultSubscriptionUrl,
        ];
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

        $urls = $this->checkoutUrls();
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
            'notification_url' => $urls['notification'],
            'back_urls' => [
                'success' => $urls['success'],
                'failure' => $urls['failure'],
                'pending' => $urls['pending'],
            ],
        ];

        if (Str::startsWith($urls['success'], 'https://')) {
            $payload['auto_return'] = 'approved';
        }

        if ($this->environmentLabel() === 'teste' && filled(config('services.mercado_pago.sandbox_payer_email'))) {
            $payload['payer']['email'] = config('services.mercado_pago.sandbox_payer_email');
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

    private function isProductionEnvironment(): bool
    {
        return config('services.mercado_pago.environment') === 'production';
    }
}
