<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\WashOrder;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function __invoke(Request $request): View
    {
        $selectedDate = $this->selectedDate((string) $request->query('date', now()->toDateString()));
        $startOfDay = $selectedDate->copy()->startOfDay();
        $endOfDay = $selectedDate->copy()->endOfDay();

        $washOrders = TenantContext::scopeWashOrders(WashOrder::query())
            ->with(['customer', 'vehicle', 'teamMembers', 'services'])
            ->whereBetween('entered_at', [$startOfDay, $endOfDay])
            ->orderByRaw('COALESCE(estimated_completion_at, entered_at) asc')
            ->get();

        return view('app.schedule.index', [
            'selectedDate' => $selectedDate,
            'previousDate' => $selectedDate->copy()->subDay()->toDateString(),
            'nextDate' => $selectedDate->copy()->addDay()->toDateString(),
            'washOrders' => $washOrders,
            'summary' => [
                'total' => $washOrders->count(),
                'open' => $washOrders->whereNotIn('status', [WashOrder::STATUS_DELIVERED, WashOrder::STATUS_CANCELED])->count(),
                'delivered' => $washOrders->where('status', WashOrder::STATUS_DELIVERED)->count(),
            ],
        ]);
    }

    private function selectedDate(string $date): Carbon
    {
        try {
            return Carbon::parse($date)->startOfDay();
        } catch (\Throwable) {
            return now()->startOfDay();
        }
    }
}
