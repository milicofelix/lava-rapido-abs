<?php

namespace App\Http\Controllers;

use App\Models\WashOrder;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class PublicWashTrackingController extends Controller
{
    public function __invoke(string $code): Response
    {
        return Inertia::render('Tracking', $this->payload($code));
    }

    public function feed(string $code): JsonResponse
    {
        return response()->json($this->payload($code));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $code): array
    {
        $washOrder = WashOrder::query()
            ->where('code', $code)
            ->when(ctype_digit($code), fn ($query) => $query->orWhere('id', (int) $code))
            ->with(['vehicle', 'services', 'statusHistories'])
            ->firstOrFail();

        return [
            'washOrder' => [
                'id' => $washOrder->id,
                'code' => $washOrder->code,
                'status' => $washOrder->status,
                'status_label' => $washOrder->statusLabel(),
                'entered_at' => $washOrder->entered_at->format('H:i'),
                'estimated_completion_at' => $washOrder->estimated_completion_at?->format('H:i') ?? '-',
                'vehicle' => [
                    'plate' => $washOrder->vehicle->plate,
                    'model' => $washOrder->vehicle->model,
                    'color' => $washOrder->vehicle->color,
                ],
                'services' => $washOrder->services->map(fn ($service) => [
                    'name' => $service->pivot->service_name,
                    'estimated_minutes' => $service->pivot->estimated_minutes,
                ])->all(),
                'status_histories' => $washOrder->statusHistories
                    ->sortByDesc('created_at')
                    ->values()
                    ->map(fn ($history) => [
                        'id' => $history->id,
                        'to_status' => $history->to_status,
                        'label' => WashOrder::statuses()[$history->to_status] ?? $history->to_status,
                        'created_at' => $history->created_at->format('d/m/Y H:i'),
                    ])->all(),
            ],
            'statuses' => WashOrder::statuses(),
            'progressStatuses' => WashOrder::publicProgressStatuses(),
            'feedUrl' => route('tracking.feed', $code),
        ];
    }
}
