<?php

namespace App\Services\WashOrders;

use App\Events\WashOrderStatusChanged;
use App\Models\User;
use App\Models\WashOrder;
use InvalidArgumentException;

class ChangeWashOrderStatusService
{
    public function handle(WashOrder $washOrder, string $status, ?User $user = null, ?string $notes = null): WashOrder
    {
        if (! array_key_exists($status, WashOrder::statuses())) {
            throw new InvalidArgumentException('Status de lavagem invalido.');
        }

        $fromStatus = $washOrder->status;

        if ($fromStatus === $status) {
            return $washOrder;
        }

        $washOrder->forceFill([
            'status' => $status,
            'completed_at' => in_array($status, [WashOrder::STATUS_READY, WashOrder::STATUS_DELIVERED, WashOrder::STATUS_CANCELED], true)
                ? ($washOrder->completed_at ?? now())
                : $washOrder->completed_at,
        ])->save();

        $washOrder->statusHistories()->create([
            'user_id' => $user?->id,
            'from_status' => $fromStatus,
            'to_status' => $status,
            'notes' => $notes,
        ]);

        $washOrder = $washOrder->refresh();

        event(new WashOrderStatusChanged($washOrder, $fromStatus));

        return $washOrder;
    }
}
