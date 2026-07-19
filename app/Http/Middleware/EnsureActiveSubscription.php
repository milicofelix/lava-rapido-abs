<?php

namespace App\Http\Middleware;

use App\Services\Subscriptions\SubscriptionExpirationService;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscription
{
    public function __construct(private readonly SubscriptionExpirationService $expirationService) {}

    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user || $user->isSuperAdmin()) {
            return $next($request);
        }

        $location = TenantContext::currentLocation();

        if ($location) {
            $this->expirationService->expireLocationIfOverdue($location);
        }

        if (! $location || ! $location->canAccessOperationalArea()) {
            if ($request->routeIs('subscription.blocked')) {
                return $next($request);
            }

            return redirect()->route('subscription.blocked');
        }

        return $next($request);
    }
}
