<?php

namespace App\Services\Loyalty;

use App\Models\AuditLog;
use App\Models\LoyaltyCoupon;
use App\Models\LoyaltyProgram;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Models\WashOrder;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ApplyLoyaltyCouponService
{
    public function handle(WashOrder $washOrder, LoyaltyCoupon $coupon, ?User $user = null): WashOrder
    {
        return DB::transaction(function () use ($washOrder, $coupon, $user) {
            $washOrder = $washOrder->fresh(['services', 'customer', 'loyaltyCoupon']);
            $coupon = $coupon->fresh(['loyaltyProgram', 'rewardService', 'sourceWashOrder.services']);

            if (! $washOrder || ! $coupon) {
                throw new InvalidArgumentException('Cupom ou lavagem nao encontrados.');
            }

            $this->validateCoupon($washOrder, $coupon);

            $discountAmount = $this->discountAmountFor($washOrder, $coupon);

            if ($discountAmount <= 0) {
                throw new InvalidArgumentException('Este cupom nao gera abatimento para os servicos desta lavagem.');
            }

            $washOrder->forceFill([
                'loyalty_coupon_id' => $coupon->id,
                'loyalty_discount_amount' => min($discountAmount, (float) $washOrder->total_amount),
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

    private function validateCoupon(WashOrder $washOrder, LoyaltyCoupon $coupon): void
    {
        if ($washOrder->loyalty_coupon_id !== null) {
            throw new InvalidArgumentException('Esta lavagem ja possui um cupom de fidelidade aplicado.');
        }

        if ($washOrder->hasIdentifiedPayment()) {
            throw new InvalidArgumentException('Nao e possivel aplicar cupom em uma lavagem que ja possui pagamento registrado.');
        }

        if ((int) $coupon->wash_location_id !== (int) $washOrder->wash_location_id) {
            throw new InvalidArgumentException('Este cupom pertence a outra unidade.');
        }

        if ((int) $coupon->customer_id !== (int) $washOrder->customer_id) {
            throw new InvalidArgumentException('Este cupom pertence a outro cliente.');
        }

        if ($coupon->status !== LoyaltyCoupon::STATUS_ACTIVE) {
            throw new InvalidArgumentException('Este cupom nao esta ativo.');
        }

        if ($coupon->isExpired()) {
            throw new InvalidArgumentException('Este cupom esta vencido.');
        }

        if (! $coupon->loyaltyProgram) {
            throw new InvalidArgumentException('Este cupom nao possui regra de fidelidade vinculada.');
        }
    }

    private function discountAmountFor(WashOrder $washOrder, LoyaltyCoupon $coupon): float
    {
        $program = $coupon->loyaltyProgram;

        if ($program->reward_type === LoyaltyProgram::REWARD_DISCOUNT_AMOUNT) {
            return min((float) $program->discount_value, (float) $washOrder->total_amount);
        }

        if ($program->reward_type === LoyaltyProgram::REWARD_DISCOUNT_PERCENT) {
            return round((float) $washOrder->total_amount * ((float) $program->discount_value / 100), 2);
        }

        $service = $this->rewardServiceFor($coupon);

        if (! $service) {
            throw new InvalidArgumentException('Nao foi possivel identificar o servico de premio deste cupom.');
        }

        $washService = $washOrder->services->firstWhere('id', $service->id);

        if (! $washService) {
            throw new InvalidArgumentException('Este cupom e valido para '.$service->name.', mas esta lavagem nao possui esse servico.');
        }

        return (float) $washService->pivot->price;
    }

    private function rewardServiceFor(LoyaltyCoupon $coupon): ?Service
    {
        if ($coupon->rewardService) {
            return $coupon->rewardService;
        }

        if (in_array($coupon->loyaltyProgram?->reward_type, [
            LoyaltyProgram::REWARD_SAME_SERVICE,
            LoyaltyProgram::REWARD_FIXED_SERVICE,
        ], true)) {
            return $coupon->sourceWashOrder?->services?->first();
        }

        return null;
    }
}
