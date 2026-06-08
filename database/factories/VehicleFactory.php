<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Vehicle> */
class VehicleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'plate' => strtoupper(fake()->bothify('???#?##')),
            'model' => fake()->randomElement(['Corolla', 'Civic', 'Onix', 'HB20', 'Toro', 'Biz']),
            'brand' => fake()->randomElement(['Toyota', 'Honda', 'Chevrolet', 'Hyundai', 'Fiat']),
            'color' => fake()->safeColorName(),
            'type' => fake()->randomElement(['carro', 'moto', 'suv', 'caminhonete']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
