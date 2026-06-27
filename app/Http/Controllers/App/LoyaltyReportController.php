<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\LoyaltyCoupon;
use App\Models\LoyaltyProgram;
use App\Models\WashOrder;
use App\Support\Loyalty\LoyaltyProgress;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoyaltyReportController extends Controller
{
    public function index(Request $request): View
    {
        [$filters, $start, $end] = $this->filters($request);

        $couponsQuery = $this->couponsForPeriod($start, $end, $filters);

        $coupons = (clone $couponsQuery)
            ->with(['customer', 'rewardService', 'loyaltyProgram', 'sourceWashOrder.services', 'usedWashOrder'])
            ->latest('earned_at')
            ->paginate(12)
            ->withQueryString();

        $loyaltyProgram = LoyaltyProgram::query()
            ->where('wash_location_id', TenantContext::currentLocationId())
            ->where('is_active', true)
            ->first();

        $customerProgress = $this->customerProgress($loyaltyProgram, $filters['customer_id']);

        return view('app.loyalty-reports.index', [
            'filters' => $filters,
            'statuses' => LoyaltyCoupon::statuses(),
            'customersForFilter' => TenantContext::scopeCustomers(Customer::query())
                ->orderBy('name')
                ->get(['id', 'name']),
            'loyaltyProgram' => $loyaltyProgram,
            'metrics' => [
                'active_coupons' => $this->activeCoupons($filters['customer_id']),
                'used_coupons' => $this->usedCoupons($start, $end, $filters['customer_id']),
                'expired_coupons' => $this->expiredCoupons($start, $end, $filters['customer_id']),
                'discount_granted' => $this->discountGranted($start, $end, $filters['customer_id']),
            ],
            'nearRewardCustomers' => $customerProgress
                ->filter(fn (Customer $customer) => $customer->loyalty_progress['enabled']
                    && $customer->loyalty_progress['remaining'] > 0
                    && $customer->loyalty_progress['remaining'] <= 2)
                ->sortBy(fn (Customer $customer) => $customer->loyalty_progress['remaining'])
                ->take(8),
            'customerProgress' => $customerProgress->take(20),
            'coupons' => $coupons,
        ]);
    }

    /**
     * @return array{0: array{start: string, end: string, customer_id: ?int, status: ?string}, 1: Carbon, 2: Carbon}
     */
    private function filters(Request $request): array
    {
        $today = today()->toDateString();

        $validated = $request->validate([
            'start' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:'.$today],
            'end' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:'.$today, 'after_or_equal:start'],
            'customer_id' => [
                'nullable',
                'integer',
                Rule::exists('customers', 'id')->where('wash_location_id', TenantContext::currentLocationId()),
            ],
            'status' => ['nullable', Rule::in(array_keys(LoyaltyCoupon::statuses()))],
        ], [], [
            'start' => 'data inicial',
            'end' => 'data final',
            'customer_id' => 'cliente',
            'status' => 'status',
        ]);

        $start = Carbon::parse($validated['start'] ?? today()->startOfMonth()->toDateString())->startOfDay();
        $end = Carbon::parse($validated['end'] ?? $today)->endOfDay();

        if ($start->isAfter($end)) {
            throw ValidationException::withMessages([
                'end' => 'A data final deve ser igual ou posterior a data inicial.',
            ]);
        }

        return [[
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'customer_id' => isset($validated['customer_id']) ? (int) $validated['customer_id'] : null,
            'status' => $validated['status'] ?? null,
        ], $start, $end];
    }

    /**
     * @param  array{customer_id: ?int, status: ?string}  $filters
     */
    private function couponsForPeriod(Carbon $start, Carbon $end, array $filters): Builder
    {
        return TenantContext::scopeByColumn(LoyaltyCoupon::query())
            ->whereBetween('earned_at', [$start, $end])
            ->when($filters['customer_id'], fn (Builder $query, int $customerId) => $query->where('customer_id', $customerId))
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status));
    }

    private function discountGranted(Carbon $start, Carbon $end, ?int $customerId): float
    {
        return (float) TenantContext::scopeWashOrders(WashOrder::query())
            ->whereNotNull('loyalty_coupon_id')
            ->when($customerId, fn (Builder $query, int $customerId) => $query->where('customer_id', $customerId))
            ->whereHas('loyaltyCoupon', function (Builder $query) use ($start, $end): void {
                $query->where('status', LoyaltyCoupon::STATUS_USED)
                    ->whereBetween('used_at', [$start, $end]);
            })
            ->sum('loyalty_discount_amount');
    }

    private function activeCoupons(?int $customerId): int
    {
        return TenantContext::scopeByColumn(LoyaltyCoupon::query())
            ->where('status', LoyaltyCoupon::STATUS_ACTIVE)
            ->when($customerId, fn (Builder $query, int $customerId) => $query->where('customer_id', $customerId))
            ->count();
    }

    private function usedCoupons(Carbon $start, Carbon $end, ?int $customerId): int
    {
        return TenantContext::scopeByColumn(LoyaltyCoupon::query())
            ->where('status', LoyaltyCoupon::STATUS_USED)
            ->whereBetween('used_at', [$start, $end])
            ->when($customerId, fn (Builder $query, int $customerId) => $query->where('customer_id', $customerId))
            ->count();
    }

    private function expiredCoupons(Carbon $start, Carbon $end, ?int $customerId): int
    {
        return TenantContext::scopeByColumn(LoyaltyCoupon::query())
            ->where('status', LoyaltyCoupon::STATUS_EXPIRED)
            ->whereBetween('expires_at', [$start, $end])
            ->when($customerId, fn (Builder $query, int $customerId) => $query->where('customer_id', $customerId))
            ->count();
    }

    private function customerProgress(?LoyaltyProgram $loyaltyProgram, ?int $customerId)
    {
        $customers = TenantContext::scopeCustomers(Customer::query())
            ->withCount(['washOrders', 'loyaltyCoupons'])
            ->when($customerId, fn (Builder $query, int $customerId) => $query->whereKey($customerId))
            ->orderBy('name')
            ->limit($customerId ? 1 : 80)
            ->get();

        return $customers->map(function (Customer $customer) use ($loyaltyProgram): Customer {
            $customer->setAttribute('loyalty_progress', LoyaltyProgress::forCustomer($customer, $loyaltyProgram));

            return $customer;
        })->sortByDesc(fn (Customer $customer) => $customer->loyalty_progress['percent'])->values();
    }
}
