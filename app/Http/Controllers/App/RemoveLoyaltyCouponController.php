<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\WashOrder;
use App\Services\Loyalty\RemoveLoyaltyCouponService;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class RemoveLoyaltyCouponController extends Controller
{
    public function __invoke(Request $request, WashOrder $washOrder, RemoveLoyaltyCouponService $service): RedirectResponse
    {
        TenantContext::abortUnlessModelBelongsToTenant($washOrder);

        try {
            $service->handle($washOrder, $request->user());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['loyalty_coupon_id' => $exception->getMessage()]);
        }

        return back()->with('status', 'Cupom de fidelidade removido com sucesso.');
    }
}
