<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Vehicle;
use App\Models\WashOrder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = today();
        $weekStart = $today->copy()->subDays(6)->startOfDay();
        $weekEnd = $today->copy()->endOfDay();

        $todayPayments = Payment::query()->whereBetween('paid_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()]);
        $todayRevenue = (clone $todayPayments)->sum('amount');
        $todayPaymentCount = (clone $todayPayments)->count();

        return view('app.dashboard', [
            'customerCount' => Customer::count(),
            'vehicleCount' => Vehicle::count(),
            'serviceCount' => Service::count(),
            'activeServiceCount' => Service::where('active', true)->count(),
            'washOrdersToday' => WashOrder::whereDate('entered_at', $today)->count(),
            'activeWashOrders' => WashOrder::whereIn('status', WashOrder::activeStatuses())->count(),
            'readyWashOrders' => WashOrder::where('status', WashOrder::STATUS_READY)->count(),
            'todayRevenue' => $todayRevenue,
            'ticketAverage' => $todayPaymentCount > 0 ? $todayRevenue / $todayPaymentCount : 0,
            'topService' => $this->topService($today),
            'averageWashMinutes' => $this->averageWashMinutes($today),
            'washOrdersByDay' => $this->washOrdersByDay($weekStart, $weekEnd),
            'revenueByDay' => $this->revenueByDay($weekStart, $weekEnd),
            'topServices' => $this->topServices($weekStart, $weekEnd),
            'recentCustomers' => Customer::latest()->withCount('vehicles')->limit(5)->get(),
            'recentWashOrders' => WashOrder::with(['customer', 'vehicle'])->latest('entered_at')->limit(5)->get(),
        ]);
    }

    /**
     * @return array{name: string, count: int}|null
     */
    private function topService(Carbon $day): ?array
    {
        $service = DB::table('service_wash_order')
            ->join('wash_orders', 'wash_orders.id', '=', 'service_wash_order.wash_order_id')
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
        $durations = WashOrder::query()
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
        $rows = WashOrder::query()
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
        $rows = Payment::query()
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

    private function periodDays(Carbon $start, Carbon $end)
    {
        return collect(range(0, (int) $start->diffInDays($end)))
            ->map(fn (int $offset) => $start->copy()->addDays($offset));
    }
}
