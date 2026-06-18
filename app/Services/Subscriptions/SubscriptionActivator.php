<?php

namespace App\Services\Subscriptions;

use App\Models\Subscription;
use App\Models\WashLocation;
use Carbon\CarbonInterface;

class SubscriptionActivator
{
    public function activate(Subscription $subscription, CarbonInterface|string|null $endsAt = null, array $attributes = []): Subscription
    {
        $subscription->loadMissing('washLocation');

        $location = $subscription->washLocation;
        $subscriptionEndsAt = $endsAt ?: now()->addMonth();

        $location->subscriptions()
            ->whereKeyNot($subscription->id)
            ->whereIn('status', [Subscription::STATUS_PENDING, Subscription::STATUS_ACTIVE])
            ->update(['status' => Subscription::STATUS_CANCELED]);

        $subscription->forceFill(array_merge([
            'status' => Subscription::STATUS_ACTIVE,
            'started_at' => $subscription->started_at ?: now(),
            'ends_at' => $subscriptionEndsAt,
        ], $attributes))->save();

        $location->forceFill([
            'account_status' => WashLocation::ACCOUNT_STATUS_ACTIVE,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_ACTIVE,
            'subscription_ends_at' => $subscriptionEndsAt,
            'blocked_at' => null,
            'public_visible' => true,
        ])->save();

        return $subscription;
    }
}
