<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
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
