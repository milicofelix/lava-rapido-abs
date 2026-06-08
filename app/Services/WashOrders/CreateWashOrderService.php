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
     */
    public function handle(array $data, array $serviceIds): WashOrder
    {
        return DB::transaction(function () use ($data, $serviceIds) {
            $calculation = $this->calculator->handle($serviceIds);

            $washOrder = WashOrder::create([
                ...$data,
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

            $washOrder->statusHistories()->create([
                'user_id' => auth()->id(),
                'from_status' => null,
                'to_status' => WashOrder::STATUS_AWAITING,
                'notes' => 'Ordem de lavagem criada.',
            ]);

            return $washOrder->load(['customer', 'vehicle', 'services', 'statusHistories']);
        });
    }
}
