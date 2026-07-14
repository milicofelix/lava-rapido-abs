<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Support\TenantContext;
use Illuminate\Contracts\View\View;

class SubscriptionBlockedController extends Controller
{
    public function __invoke(): View
    {
        return view('app.subscription-blocked', [
            'currentLocation' => TenantContext::currentLocation(),
        ]);
    }
}
