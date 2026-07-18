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
            ['name' => 'Ducha simples', 'category' => 'Lavagem', 'price' => 40.00, 'minutes' => 25, 'description' => 'Ducha externa rapida para manutencao.'],
            ['name' => 'Ducha + aspiração', 'category' => 'Lavagem', 'price' => 60.00, 'minutes' => 45, 'description' => 'Ducha externa com aspiracao interna.'],
            ['name' => 'Lavagem completa', 'category' => 'Lavagem', 'price' => 90.00, 'minutes' => 70, 'description' => 'Lavagem externa e interna completa.'],
            ['name' => 'Lavagem completa SUV/caminhonete', 'category' => 'Lavagem', 'price' => 120.00, 'minutes' => 85, 'description' => 'Lavagem externa e interna completa para veiculos maiores.'],
            ['name' => 'Cera', 'category' => 'Estetica', 'price' => 50.00, 'minutes' => 35, 'description' => 'Aplicacao de cera automotiva.'],
            ['name' => 'Higienização interna', 'category' => 'Estetica', 'price' => 220.00, 'minutes' => 180, 'description' => 'Limpeza detalhada da area interna.'],
            ['name' => 'Lavagem de motor', 'category' => 'Estetica', 'price' => 130.00, 'minutes' => 90, 'description' => 'Limpeza cuidadosa do cofre do motor.'],
            ['name' => 'Polimento', 'category' => 'Estetica', 'price' => 350.00, 'minutes' => 210, 'description' => 'Polimento tecnico da pintura.'],
            ['name' => 'Cristalização', 'category' => 'Estetica', 'price' => 450.00, 'minutes' => 240, 'description' => 'Protecao e acabamento de pintura.'],
            ['name' => 'Vitrificação de pintura', 'category' => 'Estetica', 'price' => 900.00, 'minutes' => 480, 'description' => 'Protecao tecnica de longa duracao para pintura.'],
            ['name' => 'Ducha de moto', 'category' => 'Moto', 'price' => 25.00, 'minutes' => 20, 'description' => 'Ducha rapida dedicada para motocicletas.'],
            ['name' => 'Lavagem de moto', 'category' => 'Moto', 'price' => 40.00, 'minutes' => 30, 'description' => 'Lavagem dedicada para motocicletas.'],
            ['name' => 'Lavagem detalhada de moto', 'category' => 'Moto', 'price' => 80.00, 'minutes' => 60, 'description' => 'Lavagem completa e detalhada para motocicletas.'],
        ];
    }

    public static function seedForLocation(WashLocation $location, bool $overwriteExisting = false): void
    {
        foreach (self::catalog() as $service) {
            $attributes = [
                'category' => $service['category'],
                'base_price' => $service['price'],
                'estimated_minutes' => $service['minutes'],
                'active' => true,
                'description' => $service['description'],
            ];

            $lookup = [
                'wash_location_id' => $location->id,
                'name' => $service['name'],
            ];

            if ($overwriteExisting) {
                Service::query()->updateOrCreate($lookup, $attributes);

                continue;
            }

            Service::query()->firstOrCreate(
                $lookup,
                [
                    'wash_location_id' => $location->id,
                    'name' => $service['name'],
                    ...$attributes,
                ],
            );
        }
    }
}
