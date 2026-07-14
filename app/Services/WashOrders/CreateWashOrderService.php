<?php

namespace App\Services\WashOrders;

use App\Models\AuditLog;
use App\Models\Service;
use App\Models\WashOrder;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Carbon;
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
            $enteredAt = isset($data['entered_at'])
                ? Carbon::parse($data['entered_at'])
                : now();

            $washOrder = WashOrder::create([
                ...$data,
                'wash_location_id' => $data['wash_location_id'] ?? TenantContext::currentLocationId(),
                'entered_at' => $enteredAt,
                'assigned_user_id' => $teamMemberIds[0] ?? null,
                'status' => WashOrder::STATUS_AWAITING,
                'total_amount' => $calculation['total'],
                'estimated_completion_at' => $enteredAt->copy()->addMinutes($calculation['estimated_minutes']),
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

            $washOrder = $washOrder->load(['customer', 'vehicle', 'services', 'statusHistories', 'teamMembers']);

            AuditLogger::record(
                AuditLog::ACTION_WASH_ORDER_CREATED,
                auth()->user()?->name.' abriu a lavagem '.$washOrder->code.' para '.$washOrder->customer->name.'.',
                $washOrder,
                [
                    'customer_id' => $washOrder->customer_id,
                    'vehicle_id' => $washOrder->vehicle_id,
                    'team_member_ids' => $teamMemberIds,
                    'service_ids' => $serviceIds,
                    'scheduled_for' => $washOrder->entered_at?->toDateTimeString(),
                ],
            );

            return $washOrder;
        });
    }
}
