<?php

namespace App\Services\Loyalty;

use App\Models\LoyaltyCoupon;
use App\Models\LoyaltyProgram;
use App\Models\Service;
use App\Models\WashOrder;

class LoyaltyCouponApplicabilityService
{
    /**
     * @return array{applicable: bool, reason: string, discount_amount: float, badge: string}
     */
    public function evaluate(WashOrder $washOrder, LoyaltyCoupon $coupon): array
    {
        $washOrder->loadMissing(['services', 'loyaltyCoupon']);
        $coupon->loadMissing(['loyaltyProgram', 'rewardService', 'sourceWashOrder.services']);

        if ($washOrder->loyalty_coupon_id !== null) {
            return $this->blocked('Cupom já aplicado nesta lavagem.', 'Já aplicado');
        }

        if ($washOrder->hasIdentifiedPayment()) {
            return $this->blocked('Lavagem ja possui pagamento identificado.', 'Já pago');
        }

        if ((int) $coupon->wash_location_id !== (int) $washOrder->wash_location_id) {
            return $this->blocked('Cupom pertence a outra unidade.', 'Outra unidade');
        }

        if ((int) $coupon->customer_id !== (int) $washOrder->customer_id) {
            return $this->blocked('Cupom pertence a outro cliente.', 'Outro cliente');
        }

        if ($coupon->status !== LoyaltyCoupon::STATUS_ACTIVE) {
            return $this->blocked('Cupom não está ativo.', 'Indisponível');
        }

        if ($coupon->isExpired()) {
            return $this->blocked('Cupom vencido.', 'Vencido');
        }

        if (! $coupon->loyaltyProgram) {
            return $this->blocked('Cupom sem regra de fidelidade vinculada.', 'Sem regra');
        }

        $discount = $this->discountAmountFor($washOrder, $coupon);

        if ($discount <= 0) {
            return $this->blocked('Cupom não gera abatimento para esta lavagem.', 'Sem abatimento');
        }

        return [
            'applicable' => true,
            'reason' => 'Aplicável nesta lavagem.',
            'discount_amount' => min($discount, (float) $washOrder->total_amount),
            'badge' => 'Aplicável',
        ];
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
            return 0;
        }

        $washService = $washOrder->services->firstWhere('id', $service->id);

        if (! $washService) {
            return 0;
        }

        return (float) $washService->pivot->price;
    }

    public function reasonForIncompatibleService(LoyaltyCoupon $coupon): string
    {
        $service = $this->rewardServiceFor($coupon);

        return $service
            ? 'Este cupom vale para '.$service->name.', mas esta lavagem não possui esse serviço.'
            : 'Não foi possível identificar o serviço de prêmio deste cupom.';
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

    /**
     * @return array{applicable: false, reason: string, discount_amount: 0.0, badge: string}
     */
    private function blocked(string $reason, string $badge): array
    {
        return [
            'applicable' => false,
            'reason' => $reason,
            'discount_amount' => 0.0,
            'badge' => $badge,
        ];
    }
}
