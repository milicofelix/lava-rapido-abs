<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WashLocation;
use App\Models\WashOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WashOrder> */
class WashOrderFactory extends Factory
{
    public function definition(): array
    {
        $customer = Customer::factory()->create();
        $vehicle = Vehicle::factory()->for($customer)->create();

        return [
            'wash_location_id' => WashLocation::query()->value('id') ?? WashLocation::create([
                'name' => 'AutoFlow Demo',
                'address' => 'Rua Demo, 100',
                'district' => 'Centro',
                'city' => 'Sao Paulo',
                'status' => WashLocation::STATUS_OPEN,
                'map_x' => 50,
                'map_y' => 50,
                'latitude' => -23.55052,
                'longitude' => -46.63331,
                'active_orders_count' => 0,
                'phone' => '(11) 99999-0000',
            ])->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'assigned_user_id' => User::factory(),
            'total_amount' => fake()->randomFloat(2, 30, 250),
            'status' => WashOrder::STATUS_AWAITING,
            'entered_at' => now(),
            'estimated_completion_at' => now()->addMinutes(60),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
