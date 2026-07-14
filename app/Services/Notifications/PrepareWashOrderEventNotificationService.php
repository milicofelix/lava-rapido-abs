<?php

namespace App\Services\Notifications;

use App\Models\CustomerNotification;
use App\Models\User;
use App\Models\WashOrder;

class PrepareWashOrderEventNotificationService
{
    public function __construct(
        private readonly CreateManualWhatsappNotificationService $creator,
    ) {}

    public function handle(WashOrder $washOrder, ?User $user = null): ?CustomerNotification
    {
        $templateKey = $this->templateForStatus($washOrder->status);

        if (! $templateKey) {
            return null;
        }

        $exists = CustomerNotification::query()
            ->where('wash_order_id', $washOrder->id)
            ->where('channel', CustomerNotification::CHANNEL_WHATSAPP_MANUAL)
            ->where('template_key', $templateKey)
            ->exists();

        if ($exists) {
            return null;
        }

        return $this->creator->handle(
            $washOrder,
            $templateKey,
            $user,
            'Mensagem preparada automaticamente pela mudança de status.',
        );
    }

    private function templateForStatus(string $status): ?string
    {
        return match ($status) {
            WashOrder::STATUS_WASHING => CustomerNotification::TEMPLATE_WASH_STARTED,
            WashOrder::STATUS_READY => CustomerNotification::TEMPLATE_READY_FOR_PICKUP,
            WashOrder::STATUS_DELIVERED => CustomerNotification::TEMPLATE_WASH_COMPLETED,
            default => null,
        };
    }
}
