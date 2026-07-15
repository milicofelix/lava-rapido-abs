<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\WashLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Customer> */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'wash_location_id' => WashLocation::query()->value('id') ?? WashLocation::factory(),
            'name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->optional()->safeEmail(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
