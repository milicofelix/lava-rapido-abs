<?php

namespace App\Http\Controllers;

use App\Models\LoyaltyCoupon;
use App\Models\LoyaltyProgram;
use App\Models\WashOrder;
use App\Support\Loyalty\LoyaltyProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function review(Request $request, string $code): RedirectResponse
    {
        $washOrder = WashOrder::query()
            ->where('code', $code)
            ->firstOrFail();

        if ($washOrder->status !== WashOrder::STATUS_DELIVERED) {
            return back()->withErrors([
                'review' => 'A avaliação fica disponível somente após a entrega do veículo.',
            ]);
        }

        if ($washOrder->customer_reviewed_at !== null) {
            return back()->withErrors([
                'review' => 'Esta lavagem já recebeu uma avaliação.',
            ]);
        }

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'min:10', 'max:500'],
            'publish_consent' => ['accepted'],
        ], [
            'rating.required' => 'Escolha uma nota de 1 a 5.',
            'rating.between' => 'Escolha uma nota de 1 a 5.',
            'comment.required' => 'Escreva um breve depoimento sobre o atendimento.',
            'comment.min' => 'O depoimento precisa ter pelo menos 10 caracteres.',
            'comment.max' => 'O depoimento pode ter no máximo 500 caracteres.',
            'publish_consent.accepted' => 'Autorize a publicação para enviar o depoimento.',
        ]);

        $washOrder->forceFill([
            'customer_review_rating' => (int) $data['rating'],
            'customer_review_comment' => trim($data['comment']),
            'customer_review_public' => true,
            'customer_reviewed_at' => now(),
        ])->save();

        return back()->with('status', 'Obrigado! Seu depoimento foi registrado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $code): array
    {
        $washOrder = WashOrder::query()
            ->where('code', $code)
            ->when(ctype_digit($code), fn ($query) => $query->orWhere('id', (int) $code))
            ->with(['customer', 'vehicle', 'services', 'statusHistories', 'washLocation'])
            ->firstOrFail();
        $loyaltyProgram = LoyaltyProgram::query()
            ->where('wash_location_id', $washOrder->wash_location_id)
            ->where('is_active', true)
            ->first();
        $loyaltyProgress = LoyaltyProgress::forCustomer($washOrder->customer, $loyaltyProgram);
        $activeCoupons = LoyaltyCoupon::query()
            ->with(['loyaltyProgram', 'rewardService', 'sourceWashOrder.services'])
            ->where('wash_location_id', $washOrder->wash_location_id)
            ->where('customer_id', $washOrder->customer_id)
            ->activeAndValid()
            ->latest('earned_at')
            ->limit(3)
            ->get();

        return [
            'logoUrl' => $washOrder->washLocation?->logoUrl() ?? asset('images/autoflow-logo.png'),
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
                'review' => [
                    'can_review' => $washOrder->status === WashOrder::STATUS_DELIVERED
                        && $washOrder->customer_reviewed_at === null,
                    'submitted' => $washOrder->customer_reviewed_at !== null,
                    'rating' => $washOrder->customer_review_rating,
                    'comment' => $washOrder->customer_review_comment,
                ],
            ],
            'loyalty' => [
                'enabled' => $loyaltyProgress['enabled'],
                'current' => $loyaltyProgress['current'],
                'threshold' => $loyaltyProgress['threshold'],
                'remaining' => $loyaltyProgress['remaining'],
                'percent' => $loyaltyProgress['percent'],
                'active_coupons' => $loyaltyProgress['active_coupons'],
                'has_active_coupon' => $loyaltyProgress['has_active_coupon'],
                'label' => $loyaltyProgress['label'],
                'coupons' => $activeCoupons->map(fn (LoyaltyCoupon $coupon) => [
                    'id' => $coupon->id,
                    'code' => $coupon->code,
                    'benefit' => $coupon->benefitLabel(),
                    'expires_at' => $coupon->expires_at?->format('d/m/Y'),
                ])->all(),
            ],
            'statuses' => WashOrder::statuses(),
            'progressStatuses' => WashOrder::publicProgressStatuses(),
            'feedUrl' => route('tracking.feed', $code),
            'reviewUrl' => route('tracking.review', $code),
        ];
    }
}
