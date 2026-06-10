<?php

namespace App\Services\WashOrders;

use App\Models\Service;
use App\Models\WashOrder;
use Illuminate\Support\Facades\DB;

class CreateWashOrderService
{
    public function __construct(
        private readonly CalculateWashTotalService $calculator,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int>  $serviceIds
     * @param  array<int>  $teamMemberIds
     */
    public function handle(array $data, array $serviceIds, array $teamMemberIds = []): WashOrder
    {
        return DB::transaction(function () use ($data, $serviceIds, $teamMemberIds) {
            $calculation = $this->calculator->handle($serviceIds);

            $washOrder = WashOrder::create([
                ...$data,
                'assigned_user_id' => $teamMemberIds[0] ?? null,
                'status' => WashOrder::STATUS_AWAITING,
                'total_amount' => $calculation['total'],
                'estimated_completion_at' => now()->addMinutes($calculation['estimated_minutes']),
            ]);

            $washOrder->services()->attach(
                $calculation['services']->mapWithKeys(fn (Service $service) => [
                    $service->id => [
                        'service_name' => $service->name,
                        'price' => $service->base_price,
                        'estimated_minutes' => $service->estimated_minutes,
                    ],
                ])->all()
            );

            $washOrder->teamMembers()->sync($teamMemberIds);

            $washOrder->statusHistories()->create([
                'user_id' => auth()->id(),
                'from_status' => null,
                'to_status' => WashOrder::STATUS_AWAITING,
                'notes' => 'Ordem de lavagem criada.',
            ]);

            return $washOrder->load(['customer', 'vehicle', 'services', 'statusHistories', 'teamMembers']);
        });
    }
}
