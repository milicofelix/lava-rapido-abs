<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\User;
use App\Models\WashOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Payment> */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'wash_order_id' => WashOrder::factory(),
            'user_id' => User::factory(),
            'method' => Payment::METHOD_PIX,
            'amount' => fake()->randomFloat(2, 30, 250),
            'paid_at' => now(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
