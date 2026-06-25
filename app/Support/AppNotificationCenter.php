<?php

namespace App\Support;

use App\Models\AppSetting;
use App\Models\CashRegister;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WashOrder;
use App\Models\WashLocation;
use App\Models\WashLocationRequest;
use App\Support\Access\AccessControl;

class AppNotificationCenter
{
    /**
     * @return array<int, array{title: string, body: string, tone: string, url: string|null, action: string|null}>
     */
    public static function for(?User $user): array
    {
        if (! $user) {
            return [];
        }

        if ($user->isSuperAdmin()) {
            return self::productAdminNotifications();
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
    private static function productAdminNotifications(): array
    {
        $pendingRequests = WashLocationRequest::query()
            ->where('status', WashLocationRequest::STATUS_PENDING_REVIEW)
            ->count();

        if ($pendingRequests === 0) {
            return [];
        }

        return [[
            'title' => $pendingRequests.' solicita'.($pendingRequests === 1 ? 'ção' : 'ções').' de lava-rápido',
            'body' => $pendingRequests === 1
                ? 'Existe uma nova unidade aguardando análise para iniciar o trial.'
                : 'Existem novas unidades aguardando análise para iniciar o trial.',
            'tone' => 'warning',
            'url' => route('super-admin.location-requests.index', ['status' => WashLocationRequest::STATUS_PENDING_REVIEW]),
            'action' => 'Analisar solicitações',
        ]];
    }

    /**
     * @return array<int, array{title: string, body: string, tone: string, url: string|null, action: string|null}>
     */
    private static function operationalNotifications(): array
    {
        $notifications = [];
        $inProgressStatuses = [
            WashOrder::STATUS_PREPARING,
            WashOrder::STATUS_WASHING,
            WashOrder::STATUS_VACUUMING,
            WashOrder::STATUS_WAXING,
            WashOrder::STATUS_FINISHING,
        ];

        $delayedCount = TenantContext::scopeWashOrders(WashOrder::query())
            ->whereDate('entered_at', today())
            ->whereIn('status', $inProgressStatuses)
            ->whereNotNull('estimated_completion_at')
            ->where('estimated_completion_at', '<', now())
            ->count();

        if ($delayedCount > 0) {
            $notifications[] = [
                'title' => $delayedCount.' lavagem'.($delayedCount === 1 ? '' : 's').' atrasada'.($delayedCount === 1 ? '' : 's'),
                'body' => 'Existem lavagens abertas com previsao vencida hoje. Priorize a fila da operacao.',
                'tone' => 'danger',
                'url' => AppSetting::isModuleEnabled('module_schedule') ? route('schedule.index') : route('kanban'),
                'action' => AppSetting::isModuleEnabled('module_schedule') ? 'Abrir Agenda' : 'Abrir Kanban',
            ];
        }

        $inProgressCount = TenantContext::scopeWashOrders(WashOrder::query())
            ->whereDate('entered_at', today())
            ->whereIn('status', $inProgressStatuses)
            ->count();

        if ($inProgressCount > 0) {
            $notifications[] = [
                'title' => $inProgressCount.' lavagem'.($inProgressCount === 1 ? '' : 's').' em andamento',
                'body' => 'Acompanhe o fluxo operacional de hoje pelo Kanban.',
                'tone' => 'info',
                'url' => route('kanban'),
                'action' => 'Abrir Kanban',
            ];
        }

        return $notifications;
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

        $notifications = [];
        $currentSubscription = $location->currentSubscription()->with('plan')->first();

        if ($currentSubscription?->status === Subscription::STATUS_PENDING) {
            $notifications[] = [
                'title' => 'Pagamento pendente',
                'body' => 'Finalize o pagamento do plano '.($currentSubscription->plan?->name ?? 'selecionado').' para ativar a assinatura.',
                'tone' => 'warning',
                'url' => $currentSubscription->checkout_url ?: route('subscriptions.show'),
                'action' => $currentSubscription->checkout_url ? 'Continuar pagamento' : 'Ver assinatura',
            ];
        }

        if (
            $currentSubscription?->status === Subscription::STATUS_CANCELED
            && $currentSubscription->provider_payment_id
            && $currentSubscription->updated_at->greaterThanOrEqualTo(now()->subDay())
        ) {
            $notifications[] = [
                'title' => 'Pagamento nao aprovado',
                'body' => 'O Mercado Pago recusou ou cancelou a ultima tentativa. Escolha um plano para tentar novamente.',
                'tone' => 'danger',
                'url' => route('subscriptions.show'),
                'action' => 'Tentar novamente',
            ];
        }

        if (
            $currentSubscription?->status === Subscription::STATUS_ACTIVE
            && $currentSubscription->paid_at
            && $currentSubscription->paid_at->greaterThanOrEqualTo(now()->subDay())
        ) {
            $notifications[] = [
                'title' => 'Pagamento aprovado',
                'body' => 'Assinatura '.($currentSubscription->plan?->name ?? '').' ativa ate '.($currentSubscription->ends_at?->format('d/m/Y') ?? 'a proxima cobranca').'.',
                'tone' => 'success',
                'url' => route('subscriptions.show'),
                'action' => 'Ver assinatura',
            ];
        }

        $trialDaysRemaining = $location->trialDaysRemaining();

        if ($location->subscriptionStatus() === WashLocation::ACCOUNT_STATUS_TRIAL && $trialDaysRemaining !== null && $trialDaysRemaining <= 5) {
            $notifications[] = [
                'title' => 'Trial expira em '.$trialDaysRemaining.' dia'.($trialDaysRemaining === 1 ? '' : 's'),
                'body' => 'Ative uma assinatura para evitar bloqueio da unidade.',
                'tone' => 'warning',
                'url' => route('subscriptions.show'),
                'action' => 'Escolher plano',
            ];
        }

        $subscriptionDaysRemaining = $location->subscription_ends_at
            ? max(0, (int) now()->startOfDay()->diffInDays($location->subscription_ends_at->copy()->startOfDay(), false))
            : null;

        if (
            $location->subscriptionStatus() === WashLocation::ACCOUNT_STATUS_ACTIVE
            && $subscriptionDaysRemaining !== null
            && $subscriptionDaysRemaining <= 5
        ) {
            $notifications[] = [
                'title' => 'Assinatura vence em '.$subscriptionDaysRemaining.' dia'.($subscriptionDaysRemaining === 1 ? '' : 's'),
                'body' => 'Renove a assinatura para manter a operacao liberada.',
                'tone' => 'warning',
                'url' => route('subscriptions.show'),
                'action' => 'Renovar',
            ];
        }

        return $notifications;
    }
}
