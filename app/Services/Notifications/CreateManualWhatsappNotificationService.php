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
        $notesLine = $notes ? "\n\nObservacao: {$notes}" : '';

        return match ($templateKey) {
            CustomerNotification::TEMPLATE_WASH_STARTED => "Ola {$customer->name}, iniciamos a lavagem do veiculo {$vehicle} ({$plate}). Voce pode acompanhar tudo por aqui: {$trackingUrl}{$notesLine}",
            CustomerNotification::TEMPLATE_WASH_COMPLETED => "Ola {$customer->name}, a lavagem do veiculo {$vehicle} ({$plate}) foi concluida. Confira os detalhes pelo link: {$trackingUrl}{$notesLine}",
            CustomerNotification::TEMPLATE_PROMOTION => "Ola {$customer->name}, temos uma condicao especial para o seu veiculo {$vehicle} ({$plate}). Fale com a nossa equipe pelo WhatsApp para aproveitar.{$notesLine}",
            CustomerNotification::TEMPLATE_STATUS_UPDATE => "Ola {$customer->name}, sua lavagem do veiculo {$vehicle} ({$plate}) esta com status: {$status}. Acompanhe em tempo real: {$trackingUrl}{$notesLine}",
            CustomerNotification::TEMPLATE_READY_FOR_PICKUP => "Ola {$customer->name}, seu veiculo {$vehicle} ({$plate}) esta pronto para retirada. Acompanhe os detalhes pelo link: {$trackingUrl}{$notesLine}",
            default => "Ola {$customer->name}, acompanhe o status da lavagem do seu veiculo {$vehicle} ({$plate}) pelo link: {$trackingUrl}".($estimate ? "\nPrevisao: {$estimate}" : '').$notesLine,
        };
    }
}
