<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\WashOrder;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class WashKanbanController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Kanban', $this->payload($request));
    }

    public function feed(Request $request): JsonResponse
    {
        return response()->json($this->payload($request));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Request $request): array
    {
        $filters = $this->filtersFromRequest($request);
        $washOrders = TenantContext::scopeWashOrders(WashOrder::query())
            ->with(['customer', 'vehicle', 'assignedUser', 'teamMembers', 'services'])
            ->whereIn('status', collect(self::columns())->pluck('statuses')->flatten()->all())
            ->when($filters['start_at'], fn ($query, Carbon $startAt) => $query->where('entered_at', '>=', $startAt))
            ->when($filters['end_at'], fn ($query, Carbon $endAt) => $query->where('entered_at', '<=', $endAt))
            ->oldest('entered_at')
            ->get();

        $currentLocation = TenantContext::currentLocation();
        $queryFilters = array_filter([
            'period' => $filters['period'] === 'today' ? null : $filters['period'],
            'date' => $filters['period'] === 'date' ? $filters['date'] : null,
        ]);

        return [
            'columns' => collect(self::columns())->map(function (array $column) use ($washOrders) {
                return [
                    ...$column,
                    'orders' => $washOrders->whereIn('status', $column['statuses'])
                        ->values()
                        ->map(fn (WashOrder $washOrder) => $this->serializeOrder($washOrder))
                        ->all(),
                ];
            })->all(),
            'statuses' => WashOrder::statuses(),
            'filters' => [
                'period' => $filters['period'],
                'date' => $filters['date'],
                'label' => $filters['label'],
                'show_outside_day_badge' => $filters['show_outside_day_badge'],
            ],
            'periodOptions' => self::periodOptions(),
            'feedUrl' => route('kanban.feed', $queryFilters),
            'filterUrl' => route('kanban'),
            'createUrl' => route('wash-orders.create'),
            'dashboardUrl' => route('dashboard'),
            'logoUrl' => $currentLocation?->logoUrl() ?? asset('images/autoflow-logo.png'),
            'currentLocation' => $currentLocation ? [
                'id' => $currentLocation->id,
                'name' => $currentLocation->name,
                'account_status' => $currentLocation->accountStatusLabel(),
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeOrder(WashOrder $washOrder): array
    {
        return [
            'id' => $washOrder->id,
            'code' => $washOrder->code,
            'status' => $washOrder->status,
            'status_label' => $washOrder->statusLabel(),
            'entered_at_for_humans' => $washOrder->entered_at->diffForHumans(null, true),
            'entered_at_date_label' => $washOrder->entered_at->isToday()
                ? 'Hoje'
                : $washOrder->entered_at->format('d/m/Y'),
            'is_outside_today' => ! $washOrder->entered_at->isToday(),
            'total_amount' => number_format((float) $washOrder->total_amount, 2, ',', '.'),
            'show_url' => route('wash-orders.show', $washOrder),
            'update_url' => route('wash-orders.update-status', $washOrder),
            'customer' => [
                'name' => $washOrder->customer->name,
            ],
            'vehicle' => [
                'plate' => $washOrder->vehicle->plate,
                'brand' => $washOrder->vehicle->brand,
                'model' => $washOrder->vehicle->model,
            ],
            'assigned_user' => $washOrder->assignedUser ? [
                'name' => $washOrder->assignedUser->name,
            ] : null,
            'team_members' => $washOrder->teamMembers->map(fn ($user) => [
                'name' => $user->name,
            ])->all(),
            'services' => $washOrder->services->map(fn ($service) => [
                'name' => $service->pivot->service_name,
            ])->all(),
        ];
    }

    public static function columns(): array
    {
        return [
            [
                'key' => 'awaiting',
                'title' => 'Aguardando',
                'target_status' => WashOrder::STATUS_AWAITING,
                'statuses' => [WashOrder::STATUS_AWAITING],
            ],
            [
                'key' => 'washing',
                'title' => 'Em lavagem',
                'target_status' => WashOrder::STATUS_WASHING,
                'statuses' => [
                    WashOrder::STATUS_PREPARING,
                    WashOrder::STATUS_WASHING,
                    WashOrder::STATUS_VACUUMING,
                    WashOrder::STATUS_WAXING,
                ],
            ],
            [
                'key' => 'finishing',
                'title' => 'Finalizando',
                'target_status' => WashOrder::STATUS_FINISHING,
                'statuses' => [WashOrder::STATUS_FINISHING],
            ],
            [
                'key' => 'ready',
                'title' => 'Pronto',
                'target_status' => WashOrder::STATUS_READY,
                'statuses' => [WashOrder::STATUS_READY],
            ],
            [
                'key' => 'delivered',
                'title' => 'Entregue',
                'target_status' => WashOrder::STATUS_DELIVERED,
                'statuses' => [WashOrder::STATUS_DELIVERED],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function filtersFromRequest(Request $request): array
    {
        $period = (string) $request->query('period', 'today');
        $period = array_key_exists($period, self::periodOptions()) ? $period : 'today';
        $today = now()->startOfDay();
        $date = (string) $request->query('date', now()->toDateString());

        return match ($period) {
            'yesterday' => [
                'period' => $period,
                'date' => now()->subDay()->toDateString(),
                'label' => 'Ontem',
                'start_at' => now()->subDay()->startOfDay(),
                'end_at' => now()->subDay()->endOfDay(),
                'show_outside_day_badge' => false,
            ],
            '7_days' => [
                'period' => $period,
                'date' => $date,
                'label' => 'Ultimos 7 dias',
                'start_at' => $today->copy()->subDays(6),
                'end_at' => now()->endOfDay(),
                'show_outside_day_badge' => true,
            ],
            '30_days' => [
                'period' => $period,
                'date' => $date,
                'label' => 'Ultimos 30 dias',
                'start_at' => $today->copy()->subDays(29),
                'end_at' => now()->endOfDay(),
                'show_outside_day_badge' => true,
            ],
            'all' => [
                'period' => $period,
                'date' => $date,
                'label' => 'Todos',
                'start_at' => null,
                'end_at' => null,
                'show_outside_day_badge' => true,
            ],
            'date' => $this->dateFilter($date),
            default => [
                'period' => 'today',
                'date' => now()->toDateString(),
                'label' => 'Hoje',
                'start_at' => now()->startOfDay(),
                'end_at' => now()->endOfDay(),
                'show_outside_day_badge' => false,
            ],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function dateFilter(string $date): array
    {
        try {
            $selectedDate = Carbon::parse($date)->startOfDay();
        } catch (\Throwable) {
            $selectedDate = now()->startOfDay();
        }

        return [
            'period' => 'date',
            'date' => $selectedDate->toDateString(),
            'label' => $selectedDate->isToday() ? 'Hoje' : $selectedDate->format('d/m/Y'),
            'start_at' => $selectedDate->copy()->startOfDay(),
            'end_at' => $selectedDate->copy()->endOfDay(),
            'show_outside_day_badge' => false,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function periodOptions(): array
    {
        return [
            'today' => 'Hoje',
            'yesterday' => 'Ontem',
            'date' => 'Data especifica',
            '7_days' => 'Ultimos 7 dias',
            '30_days' => 'Ultimos 30 dias',
            'all' => 'Todos',
        ];
    }
}
