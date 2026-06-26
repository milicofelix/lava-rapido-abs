<?php

namespace App\Services\Loyalty;

use App\Models\LoyaltyCoupon;
use App\Models\LoyaltyProgram;
use App\Models\Service;
use App\Models\WashOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class EvaluateLoyaltyProgramService
{
    public function handle(WashOrder $washOrder): ?LoyaltyCoupon
    {
        $washOrder = $washOrder->loadMissing(['services', 'customer', 'washLocation']);

        if ($washOrder->status !== WashOrder::STATUS_DELIVERED || ! $washOrder->hasIdentifiedPayment()) {
            return null;
        }

        if (LoyaltyCoupon::query()->where('source_wash_order_id', $washOrder->id)->exists()) {
            return null;
        }

        $program = LoyaltyProgram::query()
            ->with(['qualifyingService', 'rewardService'])
            ->where('wash_location_id', $washOrder->wash_location_id)
            ->where('is_active', true)
            ->first();

        if (! $program || ! $this->washOrderMatchesProgram($washOrder, $program)) {
            return null;
        }

        $lastCouponEarnedAt = LoyaltyCoupon::query()
            ->where('loyalty_program_id', $program->id)
            ->where('customer_id', $washOrder->customer_id)
            ->latest('earned_at')
            ->value('earned_at');

        $eligibleCount = $this->eligibleWashOrdersQuery($washOrder, $program)
            ->when($lastCouponEarnedAt, fn (Builder $query) => $query->where('entered_at', '>', $lastCouponEarnedAt))
            ->count();

        if ($eligibleCount < $program->threshold) {
            return null;
        }

        $rewardService = $this->rewardServiceFor($washOrder, $program);

        return LoyaltyCoupon::query()->create([
            'wash_location_id' => $washOrder->wash_location_id,
            'loyalty_program_id' => $program->id,
            'customer_id' => $washOrder->customer_id,
            'source_wash_order_id' => $washOrder->id,
            'reward_service_id' => $rewardService?->id,
            'code' => $this->couponCode($washOrder),
            'status' => LoyaltyCoupon::STATUS_ACTIVE,
            'earned_at' => now(),
            'expires_at' => now()->addDays($program->coupon_valid_days),
            'metadata' => [
                'eligible_count' => $eligibleCount,
                'threshold' => $program->threshold,
                'count_scope' => $program->count_scope,
                'reward_type' => $program->reward_type,
                'discount_value' => $program->discount_value,
            ],
        ]);
    }

    private function eligibleWashOrdersQuery(WashOrder $washOrder, LoyaltyProgram $program): Builder
    {
        return WashOrder::query()
            ->where('wash_location_id', $washOrder->wash_location_id)
            ->where('customer_id', $washOrder->customer_id)
            ->where('status', WashOrder::STATUS_DELIVERED)
            ->whereIn('payment_status', [
                WashOrder::PAYMENT_PAID,
                WashOrder::PAYMENT_COURTESY,
                WashOrder::PAYMENT_CREDIT_PENDING,
            ])
            ->whereHas('services', fn (Builder $query) => $this->applyProgramServiceScope($query, $program));
    }

    private function washOrderMatchesProgram(WashOrder $washOrder, LoyaltyProgram $program): bool
    {
        return $washOrder->services->contains(fn (Service $service) => $this->serviceMatchesProgram($service, $program));
    }

    private function applyProgramServiceScope(Builder $query, LoyaltyProgram $program): void
    {
        if ($program->count_scope === LoyaltyProgram::COUNT_SERVICE && $program->qualifying_service_id) {
            $query->where('services.id', $program->qualifying_service_id);

            return;
        }

        if ($program->count_scope === LoyaltyProgram::COUNT_CATEGORY && $program->qualifying_category) {
            $query->where('services.category', $program->qualifying_category);
        }
    }

    private function serviceMatchesProgram(Service $service, LoyaltyProgram $program): bool
    {
        return match ($program->count_scope) {
            LoyaltyProgram::COUNT_SERVICE => (int) $service->id === (int) $program->qualifying_service_id,
            LoyaltyProgram::COUNT_CATEGORY => $service->category === $program->qualifying_category,
            default => true,
        };
    }

    private function rewardServiceFor(WashOrder $washOrder, LoyaltyProgram $program): ?Service
    {
        if ($program->reward_type === LoyaltyProgram::REWARD_FIXED_SERVICE) {
            return $program->rewardService;
        }

        if ($program->reward_type === LoyaltyProgram::REWARD_SAME_SERVICE) {
            return $washOrder->services->first(fn (Service $service) => $this->serviceMatchesProgram($service, $program));
        }

        return null;
    }

    private function couponCode(WashOrder $washOrder): string
    {
        do {
            $code = 'FID-'.$washOrder->wash_location_id.'-'.Str::upper(Str::random(6));
        } while (LoyaltyCoupon::query()->where('code', $code)->exists());

        return $code;
    }
}
