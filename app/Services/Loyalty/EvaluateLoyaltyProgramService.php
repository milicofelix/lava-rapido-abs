<?php

namespace App\Services\Loyalty;

use App\Models\Customer;
use App\Models\LoyaltyCoupon;
use App\Models\LoyaltyProgram;
use App\Models\Service;
use App\Models\WashOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class EvaluateLoyaltyProgramService
{
    public function handle(WashOrder $washOrder): ?LoyaltyCoupon
    {
        $washOrder = $washOrder->loadMissing(['services', 'customer', 'washLocation']);

        if ($washOrder->status !== WashOrder::STATUS_DELIVERED || ! $washOrder->hasIdentifiedPayment()) {
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

        return $this->handleCustomer($washOrder->customer, $program);
    }

    public function handleCustomer(Customer $customer, LoyaltyProgram $program): ?LoyaltyCoupon
    {
        if (! $program->is_active || (int) $customer->wash_location_id !== (int) $program->wash_location_id) {
            return null;
        }

        /** @var Collection<int, WashOrder> $eligibleOrders */
        $eligibleOrders = $this->eligibleWashOrdersQuery($customer, $program)
            ->oldest('entered_at')
            ->oldest('id')
            ->get();

        $eligibleCount = $eligibleOrders->count();

        if ($eligibleCount < $program->threshold) {
            return null;
        }

        $sourceOrder = $eligibleOrders->get($program->threshold - 1);

        if (! $sourceOrder || LoyaltyCoupon::query()->where('source_wash_order_id', $sourceOrder->id)->exists()) {
            return null;
        }

        return $this->createCoupon($customer, $program, $sourceOrder->loadMissing('services'), $eligibleCount);
    }

    public function handleEligibleCustomers(LoyaltyProgram $program): int
    {
        if (! $program->is_active) {
            return 0;
        }

        $created = 0;

        Customer::query()
            ->where('wash_location_id', $program->wash_location_id)
            ->chunkById(100, function ($customers) use ($program, &$created) {
                foreach ($customers as $customer) {
                    while ($this->handleCustomer($customer, $program)) {
                        $created++;
                    }
                }
            });

        return $created;
    }

    private function createCoupon(
        Customer $customer,
        LoyaltyProgram $program,
        WashOrder $sourceOrder,
        int $eligibleCount,
    ): LoyaltyCoupon {
        $rewardService = $this->rewardServiceFor($sourceOrder, $program);

        return LoyaltyCoupon::query()->create([
            'wash_location_id' => $customer->wash_location_id,
            'loyalty_program_id' => $program->id,
            'customer_id' => $customer->id,
            'source_wash_order_id' => $sourceOrder->id,
            'reward_service_id' => $rewardService?->id,
            'code' => $this->couponCode($sourceOrder),
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

    private function eligibleWashOrdersQuery(Customer $customer, LoyaltyProgram $program): Builder
    {
        $lastCouponSource = LoyaltyCoupon::query()
            ->with('sourceWashOrder:id,entered_at')
            ->where('loyalty_program_id', $program->id)
            ->where('customer_id', $customer->id)
            ->latest('earned_at')
            ->first()
            ?->sourceWashOrder;

        return WashOrder::query()
            ->where('wash_location_id', $customer->wash_location_id)
            ->where('customer_id', $customer->id)
            ->where('status', WashOrder::STATUS_DELIVERED)
            ->whereIn('payment_status', [
                WashOrder::PAYMENT_PAID,
                WashOrder::PAYMENT_COURTESY,
                WashOrder::PAYMENT_CREDIT_PENDING,
            ])
            ->when($lastCouponSource, function (Builder $query) use ($lastCouponSource) {
                $query->where(function (Builder $query) use ($lastCouponSource) {
                    $query->where('entered_at', '>', $lastCouponSource->entered_at)
                        ->orWhere(function (Builder $query) use ($lastCouponSource) {
                            $query->where('entered_at', $lastCouponSource->entered_at)
                                ->where('id', '>', $lastCouponSource->id);
                        });
                });
            })
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
