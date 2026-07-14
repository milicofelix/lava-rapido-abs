<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\WashLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Subscription> */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        return [
            'wash_location_id' => WashLocation::factory(),
            'plan_id' => Plan::factory(),
            'status' => Subscription::STATUS_PENDING,
            'started_at' => null,
            'ends_at' => null,
        ];
    }
}
