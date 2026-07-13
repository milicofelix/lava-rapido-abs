<?php

namespace App\Services\WashOrders;

use App\Events\WashOrderStatusChanged;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\WashOrder;
use App\Services\Loyalty\EvaluateLoyaltyProgramService;
use App\Support\AuditLogger;
use App\Support\WashOrders\WashOrderStatusFlow;
use InvalidArgumentException;

class ChangeWashOrderStatusService
{
    public function __construct(
        private readonly EvaluateLoyaltyProgramService $loyalty,
    ) {}

    public function handle(WashOrder $washOrder, string $status, ?User $user = null, ?string $notes = null): WashOrder
    {
        if (! WashOrderStatusFlow::isKnownStatus($status)) {
            throw new InvalidArgumentException('Status de lavagem invalido.');
        }

        $fromStatus = $washOrder->status;

        if ($fromStatus === $status) {
            return $washOrder;
        }

        if (! WashOrderStatusFlow::canTransition($fromStatus, $status)) {
            throw new InvalidArgumentException('Transição de status não permitida.');
        }

        $washOrder->load('services', 'washLocation');

        if (! WashOrderStatusFlow::washOrderCanUseStatus($washOrder, $status)) {
            throw new InvalidArgumentException('Este status não se aplica aos serviços selecionados nesta lavagem.');
        }

        if ($status !== WashOrder::STATUS_CANCELED && ! ($washOrder->washLocation?->canOpenWashOrderAt() ?? true)) {
            throw new InvalidArgumentException('A unidade está fechada agora. Avance etapas somente dentro do horário de funcionamento.');
        }

        if ($status === WashOrder::STATUS_DELIVERED && ! $washOrder->hasIdentifiedPayment()) {
            throw new InvalidArgumentException('Registre o pagamento antes de marcar a lavagem como entregue.');
        }

        if ($user?->isOperator() && ! $washOrder->teamMembers()->whereKey($user->id)->exists()) {
            throw new InvalidArgumentException('Operador não faz parte da equipe desta lavagem.');
        }

        $washOrder->forceFill([
            'status' => $status,
            'completed_at' => WashOrderStatusFlow::isCompletionStatus($status)
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

        AuditLogger::record(
            AuditLog::ACTION_WASH_ORDER_STATUS_CHANGED,
            ($user?->name ?? 'Sistema').' alterou a lavagem '.$washOrder->code.' de '.WashOrderStatusFlow::labelFor($fromStatus).' para '.$washOrder->statusLabel().'.',
            $washOrder,
            [
                'from_status' => $fromStatus,
                'to_status' => $status,
                'notes' => $notes,
            ],
            $user,
        );

        event(new WashOrderStatusChanged($washOrder, $fromStatus));
        $this->loyalty->handle($washOrder);

        return $washOrder;
    }
}
