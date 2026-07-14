<?php

namespace Database\Factories;

use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CashMovement> */
class CashMovementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'cash_register_id' => CashRegister::factory(),
            'user_id' => User::factory(),
            'type' => CashMovement::TYPE_SUPPLY,
            'amount' => fake()->randomFloat(2, 10, 100),
            'description' => fake()->sentence(),
            'occurred_at' => now(),
        ];
    }
}
