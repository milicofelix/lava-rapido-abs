<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\Subscriptions\MercadoPagoDiagnosticsService;
use App\Services\Subscriptions\SubscriptionExpirationService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('subscriptions:expire', function (SubscriptionExpirationService $expirationService) {
    $summary = $expirationService->expireOverdue();

    $this->info(sprintf(
        'Assinaturas expiradas: %d unidade(s), %d assinatura(s). Trials expirados: %d. Assinaturas vencidas: %d.',
        $summary['total_locations'],
        $summary['subscriptions'],
        $summary['trial_locations'],
        $summary['subscription_locations'],
    ));
})->purpose('Expira trials e assinaturas vencidas');

Artisan::command('mercado-pago:diagnose {--api : Testa autenticação chamando a API do Mercado Pago}', function (MercadoPagoDiagnosticsService $diagnostics) {
    $checks = $diagnostics->report((bool) $this->option('api'));
    $hasFailures = false;

    foreach ($checks as $check) {
        if ($check['ok']) {
            $this->info('[OK] '.$check['message']);
            continue;
        }

        $hasFailures = true;
        $this->warn('[ATENÇÃO] '.$check['message']);
    }

    if ($hasFailures) {
        $this->newLine();
        $this->warn('Ajuste os itens acima antes do teste sandbox real.');

        return self::FAILURE;
    }

    $this->newLine();
    $this->info('Mercado Pago pronto para teste sandbox.');

    return self::SUCCESS;
})->purpose('Diagnostica configuracao do Mercado Pago');
