<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\WashLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Service> */
class ServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'wash_location_id' => WashLocation::query()->value('id') ?? WashLocation::factory(),
            'name' => fake()->randomElement(['Lavagem completa', 'Ducha simples', 'Cera', 'Higienizacao interna']),
            'description' => fake()->sentence(),
            'base_price' => fake()->randomFloat(2, 25, 250),
            'estimated_minutes' => fake()->numberBetween(20, 180),
            'active' => true,
            'category' => fake()->randomElement(['Lavagem', 'Estetica', 'Moto']),
        ];
    }
}
