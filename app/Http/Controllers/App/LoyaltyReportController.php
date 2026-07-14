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
use Symfony\Component\HttpFoundation\StreamedResponse;

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
                'active_coupons' => $this->activeCoupons($filters),
                'used_coupons' => $this->usedCoupons($start, $end, $filters),
                'expired_coupons' => $this->expiredCoupons($start, $end, $filters),
                'discount_granted' => $this->discountGranted($start, $end, $filters),
            ],
            'retentionReport' => $this->retentionReport($start, $end, $filters),
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

    public function export(Request $request): StreamedResponse
    {
        [$filters, $start, $end] = $this->filters($request);

        $coupons = $this->couponsForPeriod($start, $end, $filters)
            ->with(['customer', 'rewardService', 'loyaltyProgram', 'sourceWashOrder.services', 'usedWashOrder'])
            ->oldest('earned_at')
            ->get();

        return response()->streamDownload(function () use ($coupons) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Código',
                'Cliente',
                'Status',
                'Beneficio',
                'Gerado em',
                'Validade',
                'Usado em',
                'Lavagem origem',
                'Lavagem uso',
            ]);

            foreach ($coupons as $coupon) {
                fputcsv($handle, [
                    $coupon->code,
                    $coupon->customer?->name ?? 'Cliente não informado',
                    $coupon->statusLabel(),
                    $coupon->benefitLabel(),
                    $coupon->earned_at?->format('d/m/Y H:i') ?? '',
                    $coupon->expires_at?->format('d/m/Y') ?? '',
                    $coupon->used_at?->format('d/m/Y H:i') ?? '',
                    $coupon->sourceWashOrder?->code ?? '',
                    $coupon->usedWashOrder?->code ?? '',
                ]);
            }

            fclose($handle);
        }, 'fidelidade-'.$filters['start'].'-'.$filters['end'].'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array{0: array{start: string, end: string, customer_id: ?int, status: ?string, search: string}, 1: Carbon, 2: Carbon}
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
            'search' => ['nullable', 'string', 'max:80'],
        ], [], [
            'start' => 'data inicial',
            'end' => 'data final',
            'customer_id' => 'cliente',
            'status' => 'status',
            'search' => 'busca',
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
            'search' => trim((string) ($validated['search'] ?? '')),
        ], $start, $end];
    }

    /**
     * @param  array{customer_id: ?int, status: ?string, search: string}  $filters
     */
    private function couponsForPeriod(Carbon $start, Carbon $end, array $filters): Builder
    {
        return TenantContext::scopeByColumn(LoyaltyCoupon::query())
            ->whereBetween('earned_at', [$start, $end])
            ->when($filters['customer_id'], fn (Builder $query, int $customerId) => $query->where('customer_id', $customerId))
            ->when($filters['status'], fn (Builder $query, string $status) => $this->applyEffectiveStatus($query, $status))
            ->when($filters['search'] !== '', fn (Builder $query) => $this->applySearch($query, $filters['search']));
    }

    /**
     * @param  array{customer_id: ?int, search: string}  $filters
     */
    private function discountGranted(Carbon $start, Carbon $end, array $filters): float
    {
        return (float) TenantContext::scopeWashOrders(WashOrder::query())
            ->whereNotNull('loyalty_coupon_id')
            ->when($filters['customer_id'], fn (Builder $query, int $customerId) => $query->where('customer_id', $customerId))
            ->whereHas('loyaltyCoupon', function (Builder $query) use ($start, $end): void {
                $query->where('status', LoyaltyCoupon::STATUS_USED)
                    ->whereBetween('used_at', [$start, $end]);
            })
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $query->where(function (Builder $query) use ($filters): void {
                    $query->whereHas('loyaltyCoupon', fn (Builder $couponQuery) => $this->applySearch($couponQuery, $filters['search']))
                        ->orWhereHas('customer', fn (Builder $customerQuery) => $this->applyCustomerSearch($customerQuery, $filters['search']));
                });
            })
            ->sum('loyalty_discount_amount');
    }

    /**
     * @param  array{customer_id: ?int, search: string}  $filters
     */
    private function activeCoupons(array $filters): int
    {
        return TenantContext::scopeByColumn(LoyaltyCoupon::query())
            ->activeAndValid()
            ->when($filters['customer_id'], fn (Builder $query, int $customerId) => $query->where('customer_id', $customerId))
            ->when($filters['search'] !== '', fn (Builder $query) => $this->applySearch($query, $filters['search']))
            ->count();
    }

    /**
     * @param  array{customer_id: ?int, search: string}  $filters
     */
    private function usedCoupons(Carbon $start, Carbon $end, array $filters): int
    {
        return TenantContext::scopeByColumn(LoyaltyCoupon::query())
            ->where('status', LoyaltyCoupon::STATUS_USED)
            ->whereBetween('used_at', [$start, $end])
            ->when($filters['customer_id'], fn (Builder $query, int $customerId) => $query->where('customer_id', $customerId))
            ->when($filters['search'] !== '', fn (Builder $query) => $this->applySearch($query, $filters['search']))
            ->count();
    }

    /**
     * @param  array{customer_id: ?int, search: string}  $filters
     */
    private function expiredCoupons(Carbon $start, Carbon $end, array $filters): int
    {
        return TenantContext::scopeByColumn(LoyaltyCoupon::query())
            ->whereBetween('expires_at', [$start, $end])
            ->where(function (Builder $query): void {
                $query->where('status', LoyaltyCoupon::STATUS_EXPIRED)
                    ->orWhere(function (Builder $query): void {
                        $query->where('status', LoyaltyCoupon::STATUS_ACTIVE)
                            ->where('expires_at', '<', now());
                    });
            })
            ->when($filters['customer_id'], fn (Builder $query, int $customerId) => $query->where('customer_id', $customerId))
            ->when($filters['search'] !== '', fn (Builder $query) => $this->applySearch($query, $filters['search']))
            ->count();
    }

    /**
     * @param  array{customer_id: ?int, search: string}  $filters
     * @return array{
     *     served_customers: int,
     *     returning_customers: int,
     *     retained_from_before: int,
     *     orders_count: int,
     *     recurrence_rate: float,
     *     average_orders_per_customer: float,
     *     frequency_buckets: array<int, array{label: string, count: int, percent: float}>,
     *     monthly_series: array<int, array{label: string, orders: int, customers: int, returning: int, recurrence_rate: float, percent: float}>
     * }
     */
    private function retentionReport(Carbon $start, Carbon $end, array $filters): array
    {
        $customerOrderCounts = (clone $this->retentionOrdersQuery($start, $end, $filters))
            ->select('customer_id')
            ->selectRaw('COUNT(*) as orders_count')
            ->whereNotNull('customer_id')
            ->groupBy('customer_id')
            ->get();

        $ordersCount = (int) $customerOrderCounts->sum('orders_count');
        $servedCustomers = $customerOrderCounts->count();
        $returningCustomers = $customerOrderCounts
            ->filter(fn ($row) => (int) $row->orders_count >= 2)
            ->count();
        $servedCustomerIds = $customerOrderCounts->pluck('customer_id')->filter()->values();
        $retainedFromBefore = $servedCustomerIds->isEmpty()
            ? 0
            : (int) TenantContext::scopeWashOrders(WashOrder::query())
                ->where('status', WashOrder::STATUS_DELIVERED)
                ->whereIn('customer_id', $servedCustomerIds)
                ->where('entered_at', '<', $start)
                ->distinct('customer_id')
                ->count('customer_id');

        $frequencyBucketCounts = [
            1 => $customerOrderCounts->filter(fn ($row) => (int) $row->orders_count === 1)->count(),
            2 => $customerOrderCounts->filter(fn ($row) => (int) $row->orders_count === 2)->count(),
            3 => $customerOrderCounts->filter(fn ($row) => (int) $row->orders_count >= 3)->count(),
        ];

        return [
            'served_customers' => $servedCustomers,
            'returning_customers' => $returningCustomers,
            'retained_from_before' => $retainedFromBefore,
            'orders_count' => $ordersCount,
            'recurrence_rate' => $servedCustomers > 0 ? round(($returningCustomers / $servedCustomers) * 100, 1) : 0.0,
            'average_orders_per_customer' => $servedCustomers > 0 ? round($ordersCount / $servedCustomers, 1) : 0.0,
            'frequency_buckets' => collect([
                ['label' => '1 lavagem', 'count' => $frequencyBucketCounts[1]],
                ['label' => '2 lavagens', 'count' => $frequencyBucketCounts[2]],
                ['label' => '3+ lavagens', 'count' => $frequencyBucketCounts[3]],
            ])->map(fn (array $bucket): array => [
                ...$bucket,
                'percent' => $servedCustomers > 0 ? round(($bucket['count'] / $servedCustomers) * 100, 1) : 0.0,
            ])->all(),
            'monthly_series' => $this->monthlyRetentionSeries($start, $end, $filters),
        ];
    }

    /**
     * @param  array{customer_id: ?int, search: string}  $filters
     */
    private function retentionOrdersQuery(Carbon $start, Carbon $end, array $filters): Builder
    {
        return TenantContext::scopeWashOrders(WashOrder::query())
            ->where('status', WashOrder::STATUS_DELIVERED)
            ->whereBetween('entered_at', [$start, $end])
            ->when($filters['customer_id'], fn (Builder $query, int $customerId) => $query->where('customer_id', $customerId))
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $query->whereHas('customer', fn (Builder $customerQuery) => $this->applyCustomerSearch($customerQuery, $filters['search']));
            });
    }

    /**
     * @param  array{customer_id: ?int, search: string}  $filters
     * @return array<int, array{label: string, orders: int, customers: int, returning: int, recurrence_rate: float, percent: float}>
     */
    private function monthlyRetentionSeries(Carbon $start, Carbon $end, array $filters): array
    {
        $series = [];
        $maxOrders = 0;

        for ($cursor = $start->copy()->startOfMonth(); $cursor->lte($end); $cursor->addMonth()) {
            $monthStart = $cursor->copy()->startOfMonth()->max($start->copy());
            $monthEnd = $cursor->copy()->endOfMonth()->min($end->copy());
            $customerOrderCounts = (clone $this->retentionOrdersQuery($monthStart, $monthEnd, $filters))
                ->select('customer_id')
                ->selectRaw('COUNT(*) as orders_count')
                ->whereNotNull('customer_id')
                ->groupBy('customer_id')
                ->get();

            $orders = (int) $customerOrderCounts->sum('orders_count');
            $customers = $customerOrderCounts->count();
            $returning = $customerOrderCounts
                ->filter(fn ($row) => (int) $row->orders_count >= 2)
                ->count();
            $maxOrders = max($maxOrders, $orders);

            $series[] = [
                'label' => ucfirst($cursor->isoFormat('MMM/YY')),
                'orders' => $orders,
                'customers' => $customers,
                'returning' => $returning,
                'recurrence_rate' => $customers > 0 ? round(($returning / $customers) * 100, 1) : 0.0,
                'percent' => 0.0,
            ];
        }

        return collect($series)
            ->map(fn (array $month): array => [
                ...$month,
                'percent' => $maxOrders > 0 ? round(($month['orders'] / $maxOrders) * 100, 1) : 0.0,
            ])
            ->all();
    }

    private function applyEffectiveStatus(Builder $query, string $status): void
    {
        if ($status === LoyaltyCoupon::STATUS_ACTIVE) {
            $query->activeAndValid();

            return;
        }

        if ($status === LoyaltyCoupon::STATUS_EXPIRED) {
            $query->where(function (Builder $query): void {
                $query->where('status', LoyaltyCoupon::STATUS_EXPIRED)
                    ->orWhere(function (Builder $query): void {
                        $query->where('status', LoyaltyCoupon::STATUS_ACTIVE)
                            ->where('expires_at', '<', now());
                    });
            });

            return;
        }

        $query->where('status', $status);
    }

    private function applySearch(Builder $query, string $search): void
    {
        $query->where(function (Builder $query) use ($search): void {
            $query->where('code', 'like', '%'.$search.'%')
                ->orWhereHas('customer', fn (Builder $customerQuery) => $this->applyCustomerSearch($customerQuery, $search));
        });
    }

    private function applyCustomerSearch(Builder $query, string $search): void
    {
        $query->where('name', 'like', '%'.$search.'%')
            ->orWhere('phone', 'like', '%'.$search.'%')
            ->orWhere('cpf', 'like', '%'.$search.'%');
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
