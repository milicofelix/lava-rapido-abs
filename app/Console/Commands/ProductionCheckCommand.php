<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ProductionCheckCommand extends Command
{
    protected $signature = 'app:production-check {--strict : Trata avisos como falha para release}';

    protected $description = 'Valida configuracoes essenciais antes de publicar em producao.';

    public function handle(): int
    {
        $checks = $this->checks();
        $hasFailures = false;
        $hasWarnings = false;

        foreach ($checks as $check) {
            if ($check['level'] === 'ok') {
                $this->info('[OK] '.$check['message']);
                continue;
            }

            if ($check['level'] === 'warning') {
                $hasWarnings = true;
                $this->warn('[ATENCAO] '.$check['message']);
                continue;
            }

            $hasFailures = true;
            $this->error('[FALHA] '.$check['message']);
        }

        if ($hasFailures || ($this->option('strict') && $hasWarnings)) {
            $this->newLine();
            $this->error('Ambiente ainda nao esta pronto para liberacao.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Checklist tecnico de producao aprovado.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{level: string, message: string}>
     */
    private function checks(): array
    {
        $appUrl = (string) config('app.url');
        $logLevel = strtolower((string) config('logging.level'));
        $acceptedProductionLogLevels = ['warning', 'error', 'critical', 'alert', 'emergency'];

        return [
            $this->check(config('app.env') === 'production', 'APP_ENV=production.', 'APP_ENV deve ser production.'),
            $this->check(config('app.debug') === false, 'APP_DEBUG=false.', 'APP_DEBUG precisa ficar false.'),
            $this->check(filled(config('app.key')), 'APP_KEY configurado.', 'APP_KEY nao pode ficar vazio.'),
            $this->check(Str::startsWith($appUrl, 'https://'), 'APP_URL usa HTTPS.', 'APP_URL deve usar HTTPS em producao.'),
            $this->check(config('app.locale') === 'pt_BR', 'APP_LOCALE=pt_BR.', 'APP_LOCALE deve ser pt_BR.'),
            $this->check(config('app.fallback_locale') === 'pt_BR', 'APP_FALLBACK_LOCALE=pt_BR.', 'APP_FALLBACK_LOCALE deve ser pt_BR.'),
            $this->check(config('app.faker_locale') === 'pt_BR', 'APP_FAKER_LOCALE=pt_BR.', 'APP_FAKER_LOCALE deve ser pt_BR.'),
            $this->check(config('app.timezone') === 'America/Sao_Paulo', 'Timezone America/Sao_Paulo.', 'Timezone deve ser America/Sao_Paulo.'),
            $this->check(in_array(config('session.driver'), ['database', 'redis', 'memcached'], true), 'Sessao persistente configurada.', 'SESSION_DRIVER deve ser database, redis ou memcached.'),
            $this->check(! in_array(config('cache.default'), ['array', 'null'], true), 'Cache persistente configurado.', 'CACHE_STORE nao deve ser array/null em producao.'),
            $this->warnIf(config('queue.default') === 'sync', 'QUEUE_CONNECTION esta sync; use database ou redis se houver tarefas assincronas.', 'Fila nao esta em sync.'),
            $this->warnIf(! in_array($logLevel, $acceptedProductionLogLevels, true), 'LOG_LEVEL deve ser warning ou superior em producao.', 'LOG_LEVEL adequado para producao.'),
            $this->warnIf(config('session.secure') !== true, 'SESSION_SECURE_COOKIE=true recomendado em HTTPS.', 'Cookie de sessao seguro habilitado.'),
            $this->check(is_writable(storage_path()), 'Diretorio storage gravavel.', 'Diretorio storage precisa estar gravavel.'),
            $this->check(is_writable(base_path('bootstrap/cache')), 'Diretorio bootstrap/cache gravavel.', 'Diretorio bootstrap/cache precisa estar gravavel.'),
            $this->mercadoPagoCheck(),
        ];
    }

    /**
     * @return array{level: string, message: string}
     */
    private function mercadoPagoCheck(): array
    {
        if (config('services.mercado_pago.environment') !== 'production') {
            return [
                'level' => 'warning',
                'message' => 'Mercado Pago esta em sandbox; correto para homologacao, nao para cobranca real.',
            ];
        }

        if (! (bool) config('services.mercado_pago.live_enabled')) {
            return [
                'level' => 'warning',
                'message' => 'Mercado Pago esta em producao, mas cobranca real segue bloqueada por MERCADO_PAGO_LIVE_ENABLED=false.',
            ];
        }

        return $this->check(
            filled(config('services.mercado_pago.access_token')) && filled(config('services.mercado_pago.public_key')),
            'Mercado Pago em producao com credenciais configuradas.',
            'Mercado Pago em producao exige access token e public key.',
        );
    }

    /**
     * @return array{level: string, message: string}
     */
    private function check(bool $condition, string $okMessage, string $failureMessage): array
    {
        return [
            'level' => $condition ? 'ok' : 'failure',
            'message' => $condition ? $okMessage : $failureMessage,
        ];
    }

    /**
     * @return array{level: string, message: string}
     */
    private function warnIf(bool $condition, string $warningMessage, string $okMessage): array
    {
        return [
            'level' => $condition ? 'warning' : 'ok',
            'message' => $condition ? $warningMessage : $okMessage,
        ];
    }
}
