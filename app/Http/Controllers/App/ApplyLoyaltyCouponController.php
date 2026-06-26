<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyCoupon;
use App\Models\WashOrder;
use App\Services\Loyalty\ApplyLoyaltyCouponService;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class ApplyLoyaltyCouponController extends Controller
{
    public function __invoke(Request $request, WashOrder $washOrder, ApplyLoyaltyCouponService $service): RedirectResponse
    {
        TenantContext::abortUnlessModelBelongsToTenant($washOrder);

        $data = $request->validate([
            'loyalty_coupon_id' => [
                'required',
                'integer',
                Rule::exists('loyalty_coupons', 'id')
                    ->where('wash_location_id', $washOrder->wash_location_id)
                    ->where('customer_id', $washOrder->customer_id),
            ],
        ], [], [
            'loyalty_coupon_id' => 'cupom de fidelidade',
        ]);

        try {
            $service->handle($washOrder, LoyaltyCoupon::query()->findOrFail($data['loyalty_coupon_id']), $request->user());
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withErrors(['loyalty_coupon_id' => $exception->getMessage()])
                ->withInput();
        }

        return back()->with('status', 'Cupom de fidelidade aplicado com sucesso.');
    }
}
