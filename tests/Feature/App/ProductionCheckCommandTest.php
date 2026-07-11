<?php

namespace Tests\Feature\App;

use Tests\TestCase;

class ProductionCheckCommandTest extends TestCase
{
    public function test_production_check_fails_with_critical_misconfiguration(): void
    {
        config([
            'app.env' => 'production',
            'app.debug' => true,
            'app.key' => null,
            'app.url' => 'http://autoflow.test',
        ]);

        $this->artisan('app:production-check')
            ->expectsOutputToContain('[FALHA] APP_DEBUG precisa ficar false.')
            ->expectsOutputToContain('[FALHA] APP_KEY nao pode ficar vazio.')
            ->expectsOutputToContain('[FALHA] APP_URL deve usar HTTPS em producao.')
            ->assertExitCode(1);
    }

    public function test_production_check_accepts_safe_configuration_with_warnings(): void
    {
        $this->configureProductionReadyApp();

        config([
            'services.mercado_pago.environment' => 'sandbox',
            'services.mercado_pago.live_enabled' => false,
        ]);

        $this->artisan('app:production-check')
            ->expectsOutputToContain('[OK] APP_ENV=production.')
            ->expectsOutputToContain('[ATENCAO] Mercado Pago esta em sandbox; correto para homologacao, nao para cobranca real.')
            ->expectsOutputToContain('Checklist tecnico de producao aprovado.')
            ->assertExitCode(0);
    }

    public function test_production_check_strict_mode_fails_on_warnings(): void
    {
        $this->configureProductionReadyApp();

        config([
            'services.mercado_pago.environment' => 'sandbox',
            'services.mercado_pago.live_enabled' => false,
        ]);

        $this->artisan('app:production-check --strict')
            ->expectsOutputToContain('[ATENCAO] Mercado Pago esta em sandbox; correto para homologacao, nao para cobranca real.')
            ->expectsOutputToContain('Ambiente ainda nao esta pronto para liberacao.')
            ->assertExitCode(1);
    }

    private function configureProductionReadyApp(): void
    {
        config([
            'app.env' => 'production',
            'app.debug' => false,
            'app.key' => 'base64:'.str_repeat('A', 44),
            'app.url' => 'https://autoflow.test',
            'app.locale' => 'pt_BR',
            'app.fallback_locale' => 'pt_BR',
            'app.faker_locale' => 'pt_BR',
            'app.timezone' => 'America/Sao_Paulo',
            'session.driver' => 'database',
            'session.secure' => true,
            'cache.default' => 'database',
            'queue.default' => 'database',
            'logging.level' => 'warning',
        ]);
    }
}
