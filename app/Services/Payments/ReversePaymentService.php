<?php

namespace App\Services\Payments;

use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\User;
use App\Models\WashOrder;
use App\Support\AuditLogger;
use DomainException;
use Illuminate\Support\Facades\DB;

class ReversePaymentService
{
    /**
     * @param  array{reversal_reason: string}  $data
     */
    public function handle(WashOrder $washOrder, Payment $payment, array $data, ?User $user = null): Payment
    {
        if ((int) $payment->wash_order_id !== (int) $washOrder->id) {
            throw new DomainException('Pagamento não pertence a esta lavagem.');
        }

        if ($payment->isReversed()) {
            throw new DomainException('Este pagamento já foi estornado.');
        }

        return DB::transaction(function () use ($washOrder, $payment, $data, $user): Payment {
            $payment->forceFill([
                'reversed_at' => now(),
                'reversed_by_user_id' => $user?->id,
                'reversal_reason' => $data['reversal_reason'],
            ])->save();

            $this->refreshWashOrderPaymentStatus($washOrder);

            AuditLogger::record(
                AuditLog::ACTION_PAYMENT_REVERSED,
                ($user?->name ?? 'Sistema').' estornou pagamento em '.$payment->methodLabel().' na lavagem '.$washOrder->code.'.',
                $washOrder,
                [
                    'payment_id' => $payment->id,
                    'method' => $payment->method,
                    'amount' => (float) $payment->amount,
                    'reason' => $data['reversal_reason'],
                ],
                $user,
            );

            return $payment->refresh();
        });
    }

    private function refreshWashOrderPaymentStatus(WashOrder $washOrder): void
    {
        $latestPayment = $washOrder->payments()
            ->effective()
            ->latest('paid_at')
            ->latest('id')
            ->first();

        $washOrder->forceFill([
            'payment_status' => match ($latestPayment?->method) {
                Payment::METHOD_COURTESY => WashOrder::PAYMENT_COURTESY,
                Payment::METHOD_CREDIT_PENDING => WashOrder::PAYMENT_CREDIT_PENDING,
                null => WashOrder::PAYMENT_PENDING,
                default => WashOrder::PAYMENT_PAID,
            },
        ])->save();
    }
}
