<?php

namespace App\Support;

use App\Models\AppSetting;
use App\Models\CashRegister;
use App\Models\User;
use App\Models\WashOrder;
use App\Models\WashLocation;
use App\Support\Access\AccessControl;

class AppNotificationCenter
{
    /**
     * @return array<int, array{title: string, body: string, tone: string, url: string|null, action: string|null}>
     */
    public static function for(?User $user): array
    {
        if (! $user || $user->isSuperAdmin()) {
            return [];
        }

        $notifications = [];
        $location = TenantContext::currentLocation();

        if ($location && AccessControl::allows($user, AccessControl::MANAGE_SUBSCRIPTION)) {
            array_push($notifications, ...self::subscriptionNotifications($location));
        }

        if ($location && AccessControl::allows($user, AccessControl::VIEW_DASHBOARD)) {
            array_push($notifications, ...self::operationalNotifications());
        }

        if (
            $location
            && AppSetting::isModuleEnabled('module_cash_register')
            && AccessControl::allows($user, AccessControl::MANAGE_CASH_REGISTER)
        ) {
            $openRegister = CashRegister::openRegister(TenantContext::currentLocationId());

            if ($openRegister) {
                $notifications[] = [
                    'title' => 'Caixa aberto',
                    'body' => 'Aberto desde '.$openRegister->opened_at->format('d/m H:i').'. Feche o caixa ao encerrar o expediente.',
                    'tone' => 'info',
                    'url' => route('finance.cash-registers.index'),
                    'action' => 'Ver caixa',
                ];
            }
        }

        return $notifications;
    }

    /**
     * @return array<int, array{title: string, body: string, tone: string, url: string|null, action: string|null}>
     */
    private static function operationalNotifications(): array
    {
        $inProgressStatuses = [
            WashOrder::STATUS_PREPARING,
            WashOrder::STATUS_WASHING,
            WashOrder::STATUS_VACUUMING,
            WashOrder::STATUS_WAXING,
            WashOrder::STATUS_FINISHING,
        ];

        $inProgressCount = TenantContext::scopeWashOrders(WashOrder::query())
            ->whereDate('entered_at', today())
            ->whereIn('status', $inProgressStatuses)
            ->count();

        if ($inProgressCount === 0) {
            return [];
        }

        return [[
            'title' => $inProgressCount.' lavagem'.($inProgressCount === 1 ? '' : 's').' em andamento',
            'body' => 'Acompanhe o fluxo operacional de hoje pelo Kanban.',
            'tone' => 'info',
            'url' => route('kanban'),
            'action' => 'Abrir Kanban',
        ]];
    }

    /**
     * @return array<int, array{title: string, body: string, tone: string, url: string|null, action: string|null}>
     */
    private static function subscriptionNotifications(WashLocation $location): array
    {
        if ($location->isSubscriptionExpired()) {
            return [[
                'title' => 'Assinatura expirada',
                'body' => 'Escolha um plano e solicite a ativacao para liberar a operacao.',
                'tone' => 'danger',
                'url' => route('subscriptions.show'),
                'action' => 'Ver assinatura',
            ]];
        }

        $trialDaysRemaining = $location->trialDaysRemaining();

        if ($location->subscriptionStatus() === WashLocation::ACCOUNT_STATUS_TRIAL && $trialDaysRemaining !== null && $trialDaysRemaining <= 5) {
            return [[
                'title' => 'Trial expira em '.$trialDaysRemaining.' dia'.($trialDaysRemaining === 1 ? '' : 's'),
                'body' => 'Ative uma assinatura para evitar bloqueio da unidade.',
                'tone' => 'warning',
                'url' => route('subscriptions.show'),
                'action' => 'Escolher plano',
            ]];
        }

        return [];
    }
}
