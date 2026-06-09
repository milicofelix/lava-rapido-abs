<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\WashOrder;
use Illuminate\View\View;

class WashKanbanController extends Controller
{
    public function __invoke(): View
    {
        $washOrders = WashOrder::query()
            ->with(['customer', 'vehicle', 'assignedUser', 'services'])
            ->whereIn('status', collect(self::columns())->pluck('statuses')->flatten()->all())
            ->oldest('entered_at')
            ->get();

        return view('app.wash-orders.kanban', [
            'columns' => collect(self::columns())->map(function (array $column) use ($washOrders) {
                return [
                    ...$column,
                    'orders' => $washOrders->whereIn('status', $column['statuses'])->values(),
                ];
            })->all(),
        ]);
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
}
