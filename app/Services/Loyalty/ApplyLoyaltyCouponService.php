<?php

namespace App\Services\Loyalty;

use App\Models\AuditLog;
use App\Models\LoyaltyCoupon;
use App\Models\Payment;
use App\Models\User;
use App\Models\WashOrder;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ApplyLoyaltyCouponService
{
    public function __construct(
        private readonly LoyaltyCouponApplicabilityService $applicability,
    ) {}

    public function handle(WashOrder $washOrder, LoyaltyCoupon $coupon, ?User $user = null): WashOrder
    {
        return DB::transaction(function () use ($washOrder, $coupon, $user) {
            $washOrder = $washOrder->fresh(['services', 'customer', 'loyaltyCoupon']);
            $coupon = $coupon->fresh(['loyaltyProgram', 'rewardService', 'sourceWashOrder.services']);

            if (! $washOrder || ! $coupon) {
                throw new InvalidArgumentException('Cupom ou lavagem nao encontrados.');
            }

            $evaluation = $this->applicability->evaluate($washOrder, $coupon);

            if (! $evaluation['applicable']) {
                $reason = $evaluation['badge'] === 'Sem abatimento'
                    ? $this->applicability->reasonForIncompatibleService($coupon)
                    : $evaluation['reason'];

                throw new InvalidArgumentException($reason);
            }

            $washOrder->forceFill([
                'loyalty_coupon_id' => $coupon->id,
                'loyalty_discount_amount' => $evaluation['discount_amount'],
            ])->save();

            if ($washOrder->payableAmount() <= 0) {
                $washOrder->payments()->create([
                    'user_id' => $user?->id,
                    'method' => Payment::METHOD_COURTESY,
                    'amount' => 0,
                    'paid_at' => now(),
                    'notes' => 'Lavagem quitada com cupom de fidelidade '.$coupon->code.'.',
                ]);

                $washOrder->forceFill(['payment_status' => WashOrder::PAYMENT_COURTESY])->save();
            }

            $coupon->forceFill([
                'status' => LoyaltyCoupon::STATUS_USED,
                'used_wash_order_id' => $washOrder->id,
                'used_by_user_id' => $user?->id,
                'used_at' => now(),
            ])->save();

            AuditLogger::record(
                AuditLog::ACTION_LOYALTY_COUPON_APPLIED,
                ($user?->name ?? 'Sistema').' aplicou o cupom '.$coupon->code.' na lavagem '.$washOrder->code.'.',
                $washOrder,
                [
                    'loyalty_coupon_id' => $coupon->id,
                    'coupon_code' => $coupon->code,
                    'discount_amount' => (float) $washOrder->loyalty_discount_amount,
                ],
                $user,
            );

            return $washOrder->refresh();
        });
    }
}
