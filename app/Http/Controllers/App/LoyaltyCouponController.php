<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyCoupon;
use App\Support\TenantContext;
use Illuminate\View\View;

class LoyaltyCouponController extends Controller
{
    public function __invoke(LoyaltyCoupon $loyaltyCoupon): View
    {
        TenantContext::abortUnlessModelBelongsToTenant($loyaltyCoupon);

        $loyaltyCoupon->loadMissing([
            'customer',
            'loyaltyProgram',
            'rewardService',
            'sourceWashOrder.services',
            'washLocation',
        ]);

        return view('app.loyalty-coupons.show', [
            'coupon' => $loyaltyCoupon,
            'whatsappUrl' => $loyaltyCoupon->whatsappShareUrl(),
        ]);
    }
}
