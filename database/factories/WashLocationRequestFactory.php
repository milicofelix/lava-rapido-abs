<?php

namespace Database\Factories;

use App\Models\WashLocationRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WashLocationRequest>
 */
class WashLocationRequestFactory extends Factory
{
    protected $model = WashLocationRequest::class;

    public function definition(): array
    {
        return [
            'responsible_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '(11) 98888-'.fake()->numberBetween(1000, 9999),
            'business_name' => 'Lava Rápido '.fake()->company(),
            'zip_code' => fake()->postcode(),
            'address' => fake()->streetAddress(),
            'district' => fake()->citySuffix(),
            'city' => fake()->city(),
            'state' => 'SP',
            'employees_count' => fake()->numberBetween(1, 12),
            'notes' => fake()->sentence(),
            'status' => WashLocationRequest::STATUS_PENDING_REVIEW,
        ];
    }
}
