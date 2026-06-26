<?php

namespace App\Services\Loyalty;

use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\User;
use App\Models\WashOrder;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RemoveLoyaltyCouponService
{
    public function handle(WashOrder $washOrder, ?User $user = null): WashOrder
    {
        return DB::transaction(function () use ($washOrder, $user) {
            $washOrder = $washOrder->fresh(['loyaltyCoupon', 'payments']);

            if (! $washOrder) {
                throw new InvalidArgumentException('Lavagem nao encontrada.');
            }

            $state = $this->removableState($washOrder);

            if (! $state['can_remove']) {
                throw new InvalidArgumentException($state['reason']);
            }

            $coupon = $washOrder->loyaltyCoupon;
            $autoPayment = $this->autoCouponPayment($washOrder);

            if ($autoPayment) {
                $autoPayment->delete();
            }

            $washOrder->forceFill([
                'loyalty_coupon_id' => null,
                'loyalty_discount_amount' => 0,
                'payment_status' => WashOrder::PAYMENT_PENDING,
            ])->save();

            $coupon->forceFill([
                'status' => \App\Models\LoyaltyCoupon::STATUS_ACTIVE,
                'used_wash_order_id' => null,
                'used_by_user_id' => null,
                'used_at' => null,
            ])->save();

            AuditLogger::record(
                AuditLog::ACTION_LOYALTY_COUPON_REMOVED,
                ($user?->name ?? 'Sistema').' removeu o cupom '.$coupon->code.' da lavagem '.$washOrder->code.'.',
                $washOrder,
                [
                    'loyalty_coupon_id' => $coupon->id,
                    'coupon_code' => $coupon->code,
                    'removed_auto_payment' => $autoPayment !== null,
                ],
                $user,
            );

            return $washOrder->refresh();
        });
    }

    /**
     * @return array{can_remove: bool, reason: string}
     */
    public function removableState(WashOrder $washOrder): array
    {
        $washOrder->loadMissing(['loyaltyCoupon', 'payments']);

        if (! $washOrder->loyaltyCoupon) {
            return ['can_remove' => false, 'reason' => 'Esta lavagem nao possui cupom aplicado.'];
        }

        if (in_array($washOrder->status, [WashOrder::STATUS_DELIVERED, WashOrder::STATUS_CANCELED], true)) {
            return ['can_remove' => false, 'reason' => 'Nao e possivel remover cupom de uma lavagem finalizada ou cancelada.'];
        }

        $payments = $washOrder->payments;

        if ($payments->isEmpty()) {
            return ['can_remove' => true, 'reason' => 'Cupom pode ser removido.'];
        }

        if ($payments->count() === 1 && $this->autoCouponPayment($washOrder)) {
            return ['can_remove' => true, 'reason' => 'Cupom pode ser removido.'];
        }

        return ['can_remove' => false, 'reason' => 'Esta lavagem ja possui pagamento real registrado. Estorne o pagamento antes de remover o cupom.'];
    }

    private function autoCouponPayment(WashOrder $washOrder): ?Payment
    {
        $couponCode = $washOrder->loyaltyCoupon?->code;

        if (! $couponCode) {
            return null;
        }

        return $washOrder->payments->first(function (Payment $payment) use ($couponCode) {
            return $payment->method === Payment::METHOD_COURTESY
                && (float) $payment->amount === 0.0
                && str_contains((string) $payment->notes, 'cupom de fidelidade '.$couponCode);
        });
    }
}
