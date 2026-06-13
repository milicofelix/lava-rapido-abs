<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Service;
use App\Models\StatusHistory;
use App\Models\Vehicle;
use App\Models\WashOrder;
use App\Support\TenantContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): RedirectResponse|View
    {
        if (auth()->user()?->isSuperAdmin()) {
            return redirect()->route('super-admin.location-requests.index');
        }

        $today = today();
        $weekStart = $today->copy()->subDays(6)->startOfDay();
        $weekEnd = $today->copy()->endOfDay();

        $currentLocation = TenantContext::currentLocation();
        $todayPayments = TenantContext::scopePayments(
            Payment::query()->whereBetween('paid_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
        );
        $todayRevenue = (clone $todayPayments)->sum('amount');
        $todayPaymentCount = (clone $todayPayments)->count();

        return view('app.dashboard', [
            'currentLocation' => $currentLocation,
            'customerCount' => Customer::count(),
            'vehicleCount' => Vehicle::count(),
            'serviceCount' => Service::count(),
            'activeServiceCount' => Service::where('active', true)->count(),
            'washOrdersToday' => TenantContext::scopeWashOrders(WashOrder::query())->whereDate('entered_at', $today)->count(),
            'activeWashOrders' => TenantContext::scopeWashOrders(WashOrder::query())->whereIn('status', WashOrder::activeStatuses())->count(),
            'readyWashOrders' => TenantContext::scopeWashOrders(WashOrder::query())->where('status', WashOrder::STATUS_READY)->count(),
            'todayRevenue' => $todayRevenue,
            'ticketAverage' => $todayPaymentCount > 0 ? $todayRevenue / $todayPaymentCount : 0,
            'topService' => $this->topService($today),
            'averageWashMinutes' => $this->averageWashMinutes($today),
            'washOrdersByDay' => $this->washOrdersByDay($weekStart, $weekEnd),
            'revenueByDay' => $this->revenueByDay($weekStart, $weekEnd),
            'topServices' => $this->topServices($weekStart, $weekEnd),
            'kanbanColumns' => $this->kanbanColumns(),
            'financeByMethod' => $this->financeByMethod($today),
            'recentActivities' => $this->recentActivities(),
            'recentCustomers' => Customer::latest()->withCount('vehicles')->limit(5)->get(),
            'recentWashOrders' => TenantContext::scopeWashOrders(WashOrder::query())->with(['customer', 'vehicle'])->latest('entered_at')->limit(5)->get(),
        ]);
    }

    /**
     * @return array{name: string, count: int}|null
     */
    private function topService(Carbon $day): ?array
    {
        $service = DB::table('service_wash_order')
            ->join('wash_orders', 'wash_orders.id', '=', 'service_wash_order.wash_order_id')
            ->when(TenantContext::currentLocationId(), fn ($query, $locationId) => $query->where('wash_orders.wash_location_id', $locationId))
            ->whereDate('wash_orders.entered_at', $day)
            ->select('service_wash_order.service_name', DB::raw('COUNT(*) as total'))
            ->groupBy('service_wash_order.service_name')
            ->orderByDesc('total')
            ->first();

        if (! $service) {
            return null;
        }

        return [
            'name' => $service->service_name,
            'count' => (int) $service->total,
        ];
    }

    private function averageWashMinutes(Carbon $day): int
    {
        $durations = TenantContext::scopeWashOrders(WashOrder::query())
            ->whereDate('entered_at', $day)
            ->whereNotNull('completed_at')
            ->get(['entered_at', 'completed_at'])
            ->map(fn (WashOrder $washOrder) => $washOrder->entered_at->diffInMinutes($washOrder->completed_at));

        if ($durations->isEmpty()) {
            return 0;
        }

        return (int) round($durations->average());
    }

    /**
     * @return array<int, array{date: string, label: string, count: int, percent: float}>
     */
    private function washOrdersByDay(Carbon $start, Carbon $end): array
    {
        $rows = TenantContext::scopeWashOrders(WashOrder::query())
            ->whereBetween('entered_at', [$start, $end])
            ->selectRaw('DATE(entered_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $max = max(1, (int) $rows->max());

        return $this->periodDays($start, $end)
            ->map(function (Carbon $day) use ($rows, $max) {
                $count = (int) ($rows[$day->toDateString()] ?? 0);

                return [
                    'date' => $day->toDateString(),
                    'label' => $day->format('d/m'),
                    'count' => $count,
                    'percent' => ($count / $max) * 100,
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array{date: string, label: string, total: float, percent: float}>
     */
    private function revenueByDay(Carbon $start, Carbon $end): array
    {
        $rows = TenantContext::scopePayments(Payment::query())
            ->whereBetween('paid_at', [$start, $end])
            ->selectRaw('DATE(paid_at) as day, SUM(amount) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $max = max(1, (float) $rows->max());

        return $this->periodDays($start, $end)
            ->map(function (Carbon $day) use ($rows, $max) {
                $total = (float) ($rows[$day->toDateString()] ?? 0);

                return [
                    'date' => $day->toDateString(),
                    'label' => $day->format('d/m'),
                    'total' => $total,
                    'percent' => ($total / $max) * 100,
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array{name: string, count: int, percent: float}>
     */
    private function topServices(Carbon $start, Carbon $end): array
    {
        $rows = DB::table('service_wash_order')
            ->join('wash_orders', 'wash_orders.id', '=', 'service_wash_order.wash_order_id')
            ->when(TenantContext::currentLocationId(), fn ($query, $locationId) => $query->where('wash_orders.wash_location_id', $locationId))
            ->whereBetween('wash_orders.entered_at', [$start, $end])
            ->select('service_wash_order.service_name', DB::raw('COUNT(*) as total'))
            ->groupBy('service_wash_order.service_name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $max = max(1, (int) $rows->max('total'));

        return $rows->map(fn ($row) => [
            'name' => $row->service_name,
            'count' => (int) $row->total,
            'percent' => (((int) $row->total) / $max) * 100,
        ])->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function kanbanColumns(): array
    {
        $washOrders = TenantContext::scopeWashOrders(WashOrder::query())
            ->with(['customer', 'vehicle', 'services'])
            ->whereIn('status', collect(WashKanbanController::columns())->pluck('statuses')->flatten()->all())
            ->oldest('entered_at')
            ->limit(40)
            ->get();

        return collect(WashKanbanController::columns())
            ->take(4)
            ->map(function (array $column) use ($washOrders) {
                $orders = $washOrders->whereIn('status', $column['statuses'])->values();

                return [
                    'title' => $column['title'],
                    'key' => $column['key'],
                    'count' => $orders->count(),
                    'orders' => $orders->take(3)->map(fn (WashOrder $washOrder) => [
                        'id' => $washOrder->id,
                        'code' => $washOrder->code,
                        'plate' => $washOrder->vehicle->plate,
                        'brand' => $washOrder->vehicle->brand,
                        'customer' => $washOrder->customer->name,
                        'service' => $washOrder->services->first()?->pivot->service_name ?? 'Servico',
                        'time' => $washOrder->entered_at->format('H:i'),
                    ])->all(),
                    'remaining' => max(0, $orders->count() - 3),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{label: string, total: float, percent: float, color: string}>
     */
    private function financeByMethod(Carbon $day): array
    {
        $rows = TenantContext::scopePayments(Payment::query())
            ->whereBetween('paid_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
            ->selectRaw('method, SUM(amount) as total')
            ->groupBy('method')
            ->orderByDesc('total')
            ->get();

        $total = max(1, (float) $rows->sum('total'));
        $colors = ['bg-blue-600', 'bg-emerald-500', 'bg-amber-500', 'bg-violet-600', 'bg-slate-400'];

        return $rows->values()->map(fn ($row, int $index) => [
            'label' => Payment::methods()[$row->method] ?? $row->method,
            'total' => (float) $row->total,
            'percent' => ((float) $row->total / $total) * 100,
            'color' => $colors[$index] ?? 'bg-slate-400',
        ])->all();
    }

    /**
     * @return array<int, array{title: string, subtitle: string, time: string, color: string}>
     */
    private function recentActivities(): array
    {
        return TenantContext::scopeStatusHistories(StatusHistory::query())
            ->with(['washOrder.customer', 'washOrder.vehicle'])
            ->latest()
            ->limit(4)
            ->get()
            ->map(fn (StatusHistory $history) => [
                'title' => 'Lavagem '.$history->washOrder->code.' '.$history->washOrder->statusLabel(),
                'subtitle' => $history->washOrder->vehicle->plate.' - '.$history->washOrder->customer->name,
                'time' => $history->created_at->diffForHumans(null, true),
                'color' => match ($history->to_status) {
                    WashOrder::STATUS_READY, WashOrder::STATUS_DELIVERED => 'bg-emerald-500',
                    WashOrder::STATUS_WASHING, WashOrder::STATUS_PREPARING => 'bg-blue-500',
                    WashOrder::STATUS_CANCELED => 'bg-red-500',
                    default => 'bg-amber-500',
                },
            ])->all();
    }

    private function periodDays(Carbon $start, Carbon $end)
    {
        return collect(range(0, (int) $start->diffInDays($end)))
            ->map(fn (int $offset) => $start->copy()->addDays($offset));
    }
}
