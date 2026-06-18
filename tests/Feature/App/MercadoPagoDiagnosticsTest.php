<?php

namespace Tests\Feature\App;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MercadoPagoDiagnosticsTest extends TestCase
{
    public function test_diagnostico_alerta_quando_urls_ainda_nao_sao_https(): void
    {
        config([
            'services.mercado_pago.access_token' => 'TEST-token',
            'services.mercado_pago.notification_url' => null,
            'services.mercado_pago.success_url' => null,
            'services.mercado_pago.failure_url' => null,
            'services.mercado_pago.pending_url' => null,
        ]);

        $this->artisan('mercado-pago:diagnose')
            ->expectsOutputToContain('[OK] Access token configurado.')
            ->expectsOutputToContain('[OK] Ambiente sandbox/teste.')
            ->expectsOutputToContain('[ATENCAO] Webhook: http://localhost/webhooks/mercado-pago')
            ->assertExitCode(1);
    }

    public function test_diagnostico_aprova_urls_https_e_api_autenticada(): void
    {
        config([
            'services.mercado_pago.access_token' => 'TEST-token',
            'services.mercado_pago.notification_url' => 'https://sandbox.test/webhooks/mercado-pago',
            'services.mercado_pago.success_url' => 'https://sandbox.test/configuracoes/assinatura?status=approved',
            'services.mercado_pago.failure_url' => 'https://sandbox.test/configuracoes/assinatura?status=failure',
            'services.mercado_pago.pending_url' => 'https://sandbox.test/configuracoes/assinatura?status=pending',
        ]);

        Http::fake([
            'https://api.mercadopago.com/users/me' => Http::response(['id' => 123], 200),
        ]);

        $this->artisan('mercado-pago:diagnose --api')
            ->expectsOutputToContain('[OK] Access token configurado.')
            ->expectsOutputToContain('[OK] Ambiente sandbox/teste.')
            ->expectsOutputToContain('[OK] Webhook: https://sandbox.test/webhooks/mercado-pago')
            ->expectsOutputToContain('[OK] API autenticou com sucesso.')
            ->expectsOutputToContain('Mercado Pago pronto para teste sandbox.')
            ->assertExitCode(0);
    }
}
