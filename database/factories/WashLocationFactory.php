<?php

namespace Database\Factories;

use App\Models\WashLocation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<WashLocation> */
class WashLocationFactory extends Factory
{
    protected $model = WashLocation::class;

    public function definition(): array
    {
        $name = fake()->company().' Lava Rápido';

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'address' => fake()->streetName(),
            'address_number' => (string) fake()->buildingNumber(),
            'district' => fake()->citySuffix(),
            'city' => fake()->city(),
            'status' => WashLocation::STATUS_OPEN,
            'account_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'public_visible' => true,
            'trial_started_at' => now(),
            'trial_ends_at' => now()->addDays(15),
            'subscription_ends_at' => null,
            'blocked_at' => null,
            'map_x' => fake()->numberBetween(15, 85),
            'map_y' => fake()->numberBetween(15, 85),
            'latitude' => fake()->latitude(-23.70, -23.40),
            'longitude' => fake()->longitude(-46.80, -46.40),
            'active_orders_count' => 0,
            'phone' => fake()->phoneNumber(),
        ];
    }
}
