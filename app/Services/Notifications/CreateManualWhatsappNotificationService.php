<?php

namespace App\Services\Notifications;

use App\Models\CustomerNotification;
use App\Models\User;
use App\Models\WashOrder;
use Illuminate\Support\Str;

class CreateManualWhatsappNotificationService
{
    public function handle(WashOrder $washOrder, string $templateKey, ?User $user = null, ?string $notes = null): CustomerNotification
    {
        $washOrder->loadMissing(['customer', 'vehicle', 'services']);

        $message = $this->messageFor($washOrder, $templateKey, $notes);

        return CustomerNotification::create([
            'wash_order_id' => $washOrder->id,
            'customer_id' => $washOrder->customer_id,
            'user_id' => $user?->id,
            'channel' => CustomerNotification::CHANNEL_WHATSAPP_MANUAL,
            'template_key' => $templateKey,
            'target' => $washOrder->customer->whatsappNumber(),
            'message' => $message,
            'action_url' => $washOrder->customer->whatsappManualUrl($message),
            'status' => CustomerNotification::STATUS_PREPARED,
            'prepared_at' => now(),
            'notes' => $notes,
        ]);
    }

    private function messageFor(WashOrder $washOrder, string $templateKey, ?string $notes): string
    {
        $customer = $washOrder->customer;
        $vehicle = trim("{$washOrder->vehicle->brand} {$washOrder->vehicle->model}");
        $plate = $washOrder->vehicle->plate;
        $status = Str::lower($washOrder->statusLabel());
        $trackingUrl = $washOrder->trackingUrl();
        $estimate = $washOrder->estimated_completion_at?->format('H:i');
        $notesLine = $notes ? "\n\nObservação: {$notes}" : '';

        return match ($templateKey) {
            CustomerNotification::TEMPLATE_WASH_STARTED => "Olá {$customer->name}, iniciamos a lavagem do veículo {$vehicle} ({$plate}). Você pode acompanhar tudo por aqui: {$trackingUrl}{$notesLine}",
            CustomerNotification::TEMPLATE_WASH_COMPLETED => "Olá {$customer->name}, a lavagem do veículo {$vehicle} ({$plate}) foi concluída. Confira os detalhes pelo link: {$trackingUrl}{$notesLine}",
            CustomerNotification::TEMPLATE_PROMOTION => "Olá {$customer->name}, temos uma condição especial para o seu veículo {$vehicle} ({$plate}). Fale com a nossa equipe pelo WhatsApp para aproveitar.{$notesLine}",
            CustomerNotification::TEMPLATE_STATUS_UPDATE => "Olá {$customer->name}, sua lavagem do veículo {$vehicle} ({$plate}) está com status: {$status}. Acompanhe em tempo real: {$trackingUrl}{$notesLine}",
            CustomerNotification::TEMPLATE_READY_FOR_PICKUP => "Olá {$customer->name}, seu veículo {$vehicle} ({$plate}) está pronto para retirada. Acompanhe os detalhes pelo link: {$trackingUrl}{$notesLine}",
            default => "Olá {$customer->name}, acompanhe o status da lavagem do seu veículo {$vehicle} ({$plate}) pelo link: {$trackingUrl}".($estimate ? "\nPrevisão: {$estimate}" : '').$notesLine,
        };
    }
}
