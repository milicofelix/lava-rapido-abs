<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\LoyaltyCoupon;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CancelLoyaltyCouponController extends Controller
{
    public function __invoke(Request $request, LoyaltyCoupon $loyaltyCoupon): RedirectResponse
    {
        TenantContext::abortUnlessModelBelongsToTenant($loyaltyCoupon);

        if ($loyaltyCoupon->status !== LoyaltyCoupon::STATUS_ACTIVE) {
            throw ValidationException::withMessages([
                'coupon' => 'Somente cupons ativos podem ser cancelados.',
            ]);
        }

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ], [], [
            'reason' => 'motivo',
        ]);

        $loyaltyCoupon->forceFill([
            'status' => LoyaltyCoupon::STATUS_CANCELED,
            'metadata' => array_merge($loyaltyCoupon->metadata ?? [], [
                'canceled_reason' => $data['reason'] ?? null,
                'canceled_by_user_id' => $request->user()?->id,
                'canceled_at' => now()->toDateTimeString(),
            ]),
        ])->save();

        AuditLogger::record(
            AuditLog::ACTION_LOYALTY_COUPON_CANCELED,
            ($request->user()?->name ?? 'Sistema').' cancelou o cupom '.$loyaltyCoupon->code.'.',
            $loyaltyCoupon,
            [
                'coupon_code' => $loyaltyCoupon->code,
                'reason' => $data['reason'] ?? null,
            ],
            $request->user(),
        );

        return redirect()
            ->route('loyalty-coupons.show', $loyaltyCoupon)
            ->with('status', 'Cupom de fidelidade cancelado com sucesso.');
    }
}
