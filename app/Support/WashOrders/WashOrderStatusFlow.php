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

    public static function isKnownStatus(string $status): bool
    {
        return array_key_exists($status, self::labels());
    }

    public static function isCompletionStatus(string $status): bool
    {
        return in_array($status, self::completionStatuses(), true);
    }

    public static function labelFor(string $status): string
    {
        return self::labels()[$status] ?? $status;
    }
}
