<?php

namespace App\Support\WashOrders;

use App\Models\WashOrder;

class WashOrderStatusFlow
{
    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            WashOrder::STATUS_AWAITING => 'Aguardando',
            WashOrder::STATUS_PREPARING => 'Em preparacao',
            WashOrder::STATUS_WASHING => 'Lavando',
            WashOrder::STATUS_VACUUMING => 'Aspirando',
            WashOrder::STATUS_WAXING => 'Aplicando cera',
            WashOrder::STATUS_FINISHING => 'Finalizando',
            WashOrder::STATUS_READY => 'Pronto para retirada',
            WashOrder::STATUS_DELIVERED => 'Entregue',
            WashOrder::STATUS_CANCELED => 'Cancelado',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function activeStatuses(): array
    {
        return array_diff(array_keys(self::labels()), self::terminalStatuses());
    }

    /**
     * @return array<int, string>
     */
    public static function publicProgressStatuses(): array
    {
        return [
            WashOrder::STATUS_AWAITING,
            WashOrder::STATUS_PREPARING,
            WashOrder::STATUS_WASHING,
            WashOrder::STATUS_VACUUMING,
            WashOrder::STATUS_WAXING,
            WashOrder::STATUS_FINISHING,
            WashOrder::STATUS_READY,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function completionStatuses(): array
    {
        return [
            WashOrder::STATUS_READY,
            WashOrder::STATUS_DELIVERED,
            WashOrder::STATUS_CANCELED,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function terminalStatuses(): array
    {
        return [
            WashOrder::STATUS_DELIVERED,
            WashOrder::STATUS_CANCELED,
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function allowedTransitions(): array
    {
        return [
            WashOrder::STATUS_AWAITING => [
                WashOrder::STATUS_PREPARING,
                WashOrder::STATUS_WASHING,
                WashOrder::STATUS_CANCELED,
            ],
            WashOrder::STATUS_PREPARING => [
                WashOrder::STATUS_WASHING,
                WashOrder::STATUS_VACUUMING,
                WashOrder::STATUS_WAXING,
                WashOrder::STATUS_FINISHING,
            ],
            WashOrder::STATUS_WASHING => [
                WashOrder::STATUS_VACUUMING,
                WashOrder::STATUS_WAXING,
                WashOrder::STATUS_FINISHING,
                WashOrder::STATUS_READY,
            ],
            WashOrder::STATUS_VACUUMING => [
                WashOrder::STATUS_WAXING,
                WashOrder::STATUS_FINISHING,
                WashOrder::STATUS_READY,
            ],
            WashOrder::STATUS_WAXING => [
                WashOrder::STATUS_FINISHING,
                WashOrder::STATUS_READY,
            ],
            WashOrder::STATUS_FINISHING => [
                WashOrder::STATUS_READY,
            ],
            WashOrder::STATUS_READY => [
                WashOrder::STATUS_DELIVERED,
            ],
            WashOrder::STATUS_DELIVERED => [],
            WashOrder::STATUS_CANCELED => [],
        ];
    }

    public static function isKnownStatus(string $status): bool
    {
        return array_key_exists($status, self::labels());
    }

    public static function isCompletionStatus(string $status): bool
    {
        return in_array($status, self::completionStatuses(), true);
    }

    public static function canTransition(string $fromStatus, string $toStatus): bool
    {
        if ($fromStatus === $toStatus) {
            return true;
        }

        return in_array($toStatus, self::allowedTransitions()[$fromStatus] ?? [], true);
    }

    /**
     * @return array<string, string>
     */
    public static function allowedStatusLabelsFrom(string $fromStatus): array
    {
        $labels = self::labels();

        return collect(self::allowedTransitions()[$fromStatus] ?? [])
            ->mapWithKeys(fn (string $status) => [$status => $labels[$status] ?? $status])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function allowedStatusLabelsForWashOrder(WashOrder $washOrder): array
    {
        $allowed = self::allowedTransitions()[$washOrder->status] ?? [];
        $allowed = self::filterStatusesByServices($allowed, $washOrder);

        if (! self::canCancel($washOrder)) {
            $allowed = array_values(array_diff($allowed, [WashOrder::STATUS_CANCELED]));
        }

        $labels = self::labels();

        return collect($allowed)
            ->mapWithKeys(fn (string $status) => [$status => $labels[$status] ?? $status])
            ->all();
    }

    public static function canCancel(WashOrder $washOrder): bool
    {
        return $washOrder->status === WashOrder::STATUS_AWAITING
            && ! $washOrder->hasIdentifiedPayment();
    }

    /**
     * @param  array<int, string>  $statuses
     * @return array<int, string>
     */
    public static function filterStatusesByServices(array $statuses, WashOrder $washOrder): array
    {
        if (in_array(WashOrder::STATUS_WAXING, $statuses, true) && ! self::washOrderHasWaxLikeService($washOrder)) {
            $statuses = array_values(array_diff($statuses, [WashOrder::STATUS_WAXING]));
        }

        if (in_array(WashOrder::STATUS_VACUUMING, $statuses, true) && ! self::washOrderHasVacuumLikeService($washOrder)) {
            $statuses = array_values(array_diff($statuses, [WashOrder::STATUS_VACUUMING]));
        }

        return $statuses;
    }

    public static function washOrderCanUseStatus(WashOrder $washOrder, string $status): bool
    {
        return in_array($status, self::filterStatusesByServices([$status], $washOrder), true);
    }

    public static function labelFor(string $status): string
    {
        return self::labels()[$status] ?? $status;
    }

    private static function washOrderHasWaxLikeService(WashOrder $washOrder): bool
    {
        return $washOrder->services->contains(function ($service) {
            $text = mb_strtolower(($service->pivot->service_name ?? $service->name).' '.$service->category);

            return str_contains($text, 'cera')
                || str_contains($text, 'polimento')
                || str_contains($text, 'cristalizacao')
                || str_contains($text, 'cristalização');
        });
    }

    private static function washOrderHasVacuumLikeService(WashOrder $washOrder): bool
    {
        return $washOrder->services->contains(function ($service) {
            $text = mb_strtolower(($service->pivot->service_name ?? $service->name).' '.$service->category);

            return str_contains($text, 'aspir')
                || str_contains($text, 'interna')
                || str_contains($text, 'higien');
        });
    }
}
