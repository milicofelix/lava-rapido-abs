<?php

namespace App\Services\Subscriptions;

use App\Models\Subscription;
use App\Models\WashLocation;

class SubscriptionExpirationService
{
    public function expireOverdue(): array
    {
        $trialLocationIds = WashLocation::query()
            ->where('subscription_status', WashLocation::ACCOUNT_STATUS_TRIAL)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', now()->startOfDay())
            ->pluck('id');

        $subscriptionLocationIds = WashLocation::query()
            ->where('subscription_status', WashLocation::ACCOUNT_STATUS_ACTIVE)
            ->whereNotNull('subscription_ends_at')
            ->where('subscription_ends_at', '<', now()->startOfDay())
            ->pluck('id');

        $expiredTrialLocations = $this->expireLocations($trialLocationIds->all());
        $expiredSubscriptionLocations = $this->expireLocations($subscriptionLocationIds->all());

        $expiredSubscriptions = Subscription::query()
            ->where('status', Subscription::STATUS_ACTIVE)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now()->startOfDay())
            ->update(['status' => Subscription::STATUS_EXPIRED]);

        return [
            'trial_locations' => $expiredTrialLocations,
            'subscription_locations' => $expiredSubscriptionLocations,
            'subscriptions' => $expiredSubscriptions,
            'total_locations' => $expiredTrialLocations + $expiredSubscriptionLocations,
        ];
    }

    private function expireLocations(array $locationIds): int
    {
        if ($locationIds === []) {
            return 0;
        }

        return WashLocation::query()
            ->whereKey($locationIds)
            ->update([
                'account_status' => WashLocation::ACCOUNT_STATUS_EXPIRED,
                'subscription_status' => WashLocation::ACCOUNT_STATUS_EXPIRED,
                'blocked_at' => now(),
                'public_visible' => false,
            ]);
    }
}
