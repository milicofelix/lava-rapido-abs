<?php

namespace App\Services\Subscriptions;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class MercadoPagoDiagnosticsService
{
    public function __construct(private readonly MercadoPagoCheckoutService $checkoutService)
    {
    }

    public function report(bool $checkApi = false): array
    {
        $accessToken = (string) config('services.mercado_pago.access_token');
        $urls = $this->checkoutService->checkoutUrls();

        $checks = [
            'configured' => [
                'ok' => filled($accessToken),
                'message' => filled($accessToken)
                    ? 'Access token configurado.'
                    : 'Configure MERCADO_PAGO_ACCESS_TOKEN.',
            ],
            'mode' => [
                'ok' => true,
                'message' => $this->modeMessage($accessToken),
            ],
            'notification_url' => [
                'ok' => Str::startsWith($urls['notification'], 'https://'),
                'message' => 'Webhook: '.$urls['notification'],
            ],
            'success_url' => [
                'ok' => Str::startsWith($urls['success'], 'https://'),
                'message' => 'Retorno aprovado: '.$urls['success'],
            ],
            'failure_url' => [
                'ok' => Str::startsWith($urls['failure'], 'https://'),
                'message' => 'Retorno recusado: '.$urls['failure'],
            ],
            'pending_url' => [
                'ok' => Str::startsWith($urls['pending'], 'https://'),
                'message' => 'Retorno pendente: '.$urls['pending'],
            ],
        ];

        if ($checkApi) {
            $checks['api'] = $this->apiCheck($accessToken);
        }

        return $checks;
    }

    private function modeMessage(string $accessToken): string
    {
        if ($accessToken === '') {
            return 'Modo manual: Mercado Pago sem token.';
        }

        if (config('services.mercado_pago.environment') === 'production' && ! (bool) config('services.mercado_pago.live_enabled')) {
            return 'Ambiente de producao configurado, mas checkout real esta bloqueado.';
        }

        if (config('services.mercado_pago.environment') === 'production') {
            return 'Ambiente de producao com checkout real habilitado.';
        }

        return 'Ambiente sandbox/teste.';
    }

    private function apiCheck(string $accessToken): array
    {
        if ($accessToken === '') {
            return [
                'ok' => false,
                'message' => 'API nao testada porque o token nao esta configurado.',
            ];
        }

        try {
            $response = Http::baseUrl(rtrim((string) config('services.mercado_pago.base_url'), '/'))
                ->acceptJson()
                ->withToken($accessToken)
                ->get('/users/me');

            return [
                'ok' => $response->successful(),
                'message' => $response->successful()
                    ? 'API autenticou com sucesso.'
                    : 'API respondeu '.$response->status().'. Verifique o token.',
            ];
        } catch (Throwable $exception) {
            return [
                'ok' => false,
                'message' => 'Falha ao chamar API: '.$exception->getMessage(),
            ];
        }
    }
}
