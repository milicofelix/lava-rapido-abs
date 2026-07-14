<?php

namespace App\Support;

use App\Models\Service;
use App\Models\WashLocation;

class DefaultServices
{
    /**
     * @return array<int, array{name: string, category: string, price: float, minutes: int, description: string}>
     */
    public static function catalog(): array
    {
        return [
            ['name' => 'Lavagem completa', 'category' => 'Lavagem', 'price' => 80.00, 'minutes' => 70, 'description' => 'Lavagem externa e interna completa.'],
            ['name' => 'Ducha simples', 'category' => 'Lavagem', 'price' => 35.00, 'minutes' => 25, 'description' => 'Ducha externa rapida para manutencao.'],
            ['name' => 'Ducha + aspiração', 'category' => 'Lavagem', 'price' => 55.00, 'minutes' => 45, 'description' => 'Ducha externa com aspiracao interna.'],
            ['name' => 'Cera', 'category' => 'Estetica', 'price' => 45.00, 'minutes' => 35, 'description' => 'Aplicacao de cera automotiva.'],
            ['name' => 'Higienização interna', 'category' => 'Estetica', 'price' => 180.00, 'minutes' => 180, 'description' => 'Limpeza detalhada da area interna.'],
            ['name' => 'Lavagem de motor', 'category' => 'Estetica', 'price' => 120.00, 'minutes' => 90, 'description' => 'Limpeza cuidadosa do cofre do motor.'],
            ['name' => 'Polimento', 'category' => 'Estetica', 'price' => 260.00, 'minutes' => 180, 'description' => 'Polimento tecnico da pintura.'],
            ['name' => 'Cristalização', 'category' => 'Estetica', 'price' => 320.00, 'minutes' => 210, 'description' => 'Protecao e acabamento de pintura.'],
            ['name' => 'Lavagem de moto', 'category' => 'Moto', 'price' => 30.00, 'minutes' => 25, 'description' => 'Lavagem dedicada para motocicletas.'],
        ];
    }

    public static function seedForLocation(WashLocation $location): void
    {
        foreach (self::catalog() as $service) {
            Service::query()->updateOrCreate(
                [
                    'wash_location_id' => $location->id,
                    'name' => $service['name'],
                ],
                [
                    'category' => $service['category'],
                    'base_price' => $service['price'],
                    'estimated_minutes' => $service['minutes'],
                    'active' => true,
                    'description' => $service['description'],
                ],
            );
        }
    }
}
