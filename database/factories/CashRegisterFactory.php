<?php

namespace Database\Factories;

use App\Models\CashRegister;
use App\Models\WashLocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CashRegister> */
class CashRegisterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'wash_location_id' => WashLocation::factory(),
            'opened_by_user_id' => User::factory(),
            'status' => CashRegister::STATUS_OPEN,
            'opening_balance' => 100,
            'opened_at' => now(),
            'opening_notes' => fake()->optional()->sentence(),
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'closed_by_user_id' => User::factory(),
            'status' => CashRegister::STATUS_CLOSED,
            'counted_cash' => 100,
            'expected_cash' => 100,
            'cash_difference' => 0,
            'closed_at' => now(),
        ]);
    }
}
