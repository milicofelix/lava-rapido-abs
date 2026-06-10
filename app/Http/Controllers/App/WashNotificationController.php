<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\CustomerNotification;
use App\Models\WashOrder;
use App\Services\Notifications\CreateManualWhatsappNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WashNotificationController extends Controller
{
    public function store(Request $request, WashOrder $washOrder, CreateManualWhatsappNotificationService $creator): RedirectResponse
    {
        $data = $request->validate([
            'template_key' => ['required', 'string', Rule::in(array_keys(CustomerNotification::templates()))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $notification = $creator->handle(
            $washOrder,
            $data['template_key'],
            $request->user(),
            $data['notes'] ?? null,
        );

        $status = $notification->action_url
            ? 'Mensagem de WhatsApp preparada. Confira, copie ou abra o WhatsApp para enviar manualmente.'
            : 'Mensagem preparada, mas o cliente nao possui telefone valido para abrir o WhatsApp.';

        return redirect()
            ->route('wash-orders.show', $washOrder)
            ->with('status', $status);
    }

    public function markAsSent(Request $request, WashOrder $washOrder, CustomerNotification $notification): RedirectResponse
    {
        abort_unless((int) $notification->wash_order_id === (int) $washOrder->id, 404);

        $notification->forceFill([
            'status' => CustomerNotification::STATUS_SENT_MANUALLY,
            'manually_sent_at' => now(),
        ])->save();

        return redirect()
            ->route('wash-orders.show', $washOrder)
            ->with('status', 'Notificacao marcada como enviada manualmente.');
    }
}
