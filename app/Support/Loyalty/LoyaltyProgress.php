<?php

namespace App\Support\Loyalty;

use App\Models\Customer;
use App\Models\LoyaltyCoupon;
use App\Models\LoyaltyProgram;
use App\Models\WashOrder;
use Illuminate\Database\Eloquent\Builder;

class LoyaltyProgress
{
    /**
     * @return array{enabled: bool, current: int, threshold: int, remaining: int, percent: float, active_coupons: int, label: string}
     */
    public static function forCustomer(Customer $customer, ?LoyaltyProgram $program = null): array
    {
        $program ??= LoyaltyProgram::query()
            ->where('wash_location_id', $customer->wash_location_id)
            ->where('is_active', true)
            ->first();

        $activeCoupons = LoyaltyCoupon::query()
            ->where('wash_location_id', $customer->wash_location_id)
            ->where('customer_id', $customer->id)
            ->where('status', LoyaltyCoupon::STATUS_ACTIVE)
            ->count();

        if (! $program) {
            return [
                'enabled' => false,
                'current' => 0,
                'threshold' => 0,
                'remaining' => 0,
                'percent' => 0,
                'active_coupons' => $activeCoupons,
                'label' => 'Fidelidade desabilitada',
            ];
        }

        $lastCouponEarnedAt = LoyaltyCoupon::query()
            ->where('loyalty_program_id', $program->id)
            ->where('customer_id', $customer->id)
            ->latest('earned_at')
            ->value('earned_at');

        $current = WashOrder::query()
            ->where('wash_location_id', $customer->wash_location_id)
            ->where('customer_id', $customer->id)
            ->where('status', WashOrder::STATUS_DELIVERED)
            ->whereIn('payment_status', [
                WashOrder::PAYMENT_PAID,
                WashOrder::PAYMENT_COURTESY,
                WashOrder::PAYMENT_CREDIT_PENDING,
            ])
            ->when($lastCouponEarnedAt, fn (Builder $query) => $query->where('entered_at', '>', $lastCouponEarnedAt))
            ->whereHas('services', fn (Builder $query) => self::applyServiceScope($query, $program))
            ->count();

        $threshold = max(1, (int) $program->threshold);
        $remaining = max(0, $threshold - $current);

        return [
            'enabled' => true,
            'current' => $current,
            'threshold' => $threshold,
            'remaining' => $remaining,
            'percent' => min(100, ($current / $threshold) * 100),
            'active_coupons' => $activeCoupons,
            'label' => self::scopeLabel($program),
        ];
    }

    private static function applyServiceScope(Builder $query, LoyaltyProgram $program): void
    {
        if ($program->count_scope === LoyaltyProgram::COUNT_SERVICE && $program->qualifying_service_id) {
            $query->where('services.id', $program->qualifying_service_id);

            return;
        }

        if ($program->count_scope === LoyaltyProgram::COUNT_CATEGORY && $program->qualifying_category) {
            $query->where('services.category', $program->qualifying_category);
        }
    }

    private static function scopeLabel(LoyaltyProgram $program): string
    {
        return match ($program->count_scope) {
            LoyaltyProgram::COUNT_SERVICE => 'Serviço específico',
            LoyaltyProgram::COUNT_CATEGORY => 'Categoria '.$program->qualifying_category,
            default => 'Qualquer lavagem',
        };
    }
}
