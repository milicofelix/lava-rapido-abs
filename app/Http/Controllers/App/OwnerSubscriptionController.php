<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OwnerSubscriptionController extends Controller
{
    public function show(): View
    {
        $location = TenantContext::currentLocation()?->load(['currentSubscription.plan', 'activeSubscription.plan']);
        abort_unless($location, 404);

        return view('app.subscriptions.show', [
            'location' => $location,
            'currentSubscription' => $location->currentSubscription,
            'activeSubscription' => $location->activeSubscription,
            'plans' => Plan::query()->active()->orderBy('price')->orderBy('name')->get(),
        ]);
    }

    public function choose(Request $request): RedirectResponse
    {
        $location = TenantContext::currentLocation();
        abort_unless($location, 404);

        $data = $request->validate([
            'plan_id' => ['required', 'integer', Rule::exists('plans', 'id')->where('is_active', true)],
        ]);

        $location->subscriptions()
            ->where('status', Subscription::STATUS_PENDING)
            ->update(['status' => Subscription::STATUS_CANCELED]);

        $location->subscriptions()->create([
            'plan_id' => $data['plan_id'],
            'status' => Subscription::STATUS_PENDING,
            'started_at' => null,
            'ends_at' => null,
        ]);

        return back()->with('status', 'Plano escolhido. A assinatura ficará ativa após confirmação manual do Super Admin.');
    }
}
