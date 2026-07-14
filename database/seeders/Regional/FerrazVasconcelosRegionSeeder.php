<?php

namespace Database\Seeders\Regional;

use App\Models\WashLocation;

class FerrazVasconcelosRegionSeeder extends RegionalWashLocationSeeder
{
    protected function regionCode(): string
    {
        return 'FERRAZ';
    }

    /**
     * Dados demonstrativos posicionados em Ferraz de Vasconcelos.
     *
     * Os nomes nao declaram empresas reais. As coordenadas foram distribuidas
     * em vias e bairros da regiao para manter o mapa coerente durante testes.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function locations(): array
    {
        return [
            [
                'name' => 'Lava Rápido Ferraz Centro Demo',
                'slug' => 'ferraz-centro-demo',
                'address' => 'Avenida XV de Novembro',
                'address_number' => '221',
                'district' => 'Centro',
                'city' => 'Ferraz de Vasconcelos',
                'phone' => '(11) 94610-1101',
                'latitude' => -23.5411300,
                'longitude' => -46.3689000,
                'map_x' => 94,
                'map_y' => 41,
                'active_orders_count' => 6,
                'opening_hours' => 'Seg a sab: 08:00 as 18:00',
            ],
            [
                'name' => 'Auto Ducha Avenida Brasil Demo',
                'slug' => 'ferraz-avenida-brasil-demo',
                'address' => 'Avenida Brasil',
                'address_number' => '1435',
                'district' => 'Vila Romanópolis',
                'city' => 'Ferraz de Vasconcelos',
                'phone' => '(11) 94610-1102',
                'latitude' => -23.5369200,
                'longitude' => -46.3617800,
                'map_x' => 95,
                'map_y' => 40,
                'active_orders_count' => 8,
                'opening_hours' => 'Seg a sab: 07:30 as 18:30',
            ],
            [
                'name' => 'Estética Automotiva Vila Corrêa Demo',
                'slug' => 'ferraz-vila-correa-demo',
                'address' => 'Rua Godofredo Ozório Novaes',
                'address_number' => '640',
                'district' => 'Vila Corrêa',
                'city' => 'Ferraz de Vasconcelos',
                'phone' => '(11) 94610-1103',
                'latitude' => -23.5499600,
                'longitude' => -46.3791500,
                'map_x' => 93,
                'map_y' => 43,
                'active_orders_count' => 5,
                'opening_hours' => 'Seg a sex: 08:00 as 19:00; Sab: 08:00 as 17:00',
            ],
            [
                'name' => 'Lava Car Parque São Francisco Demo',
                'slug' => 'ferraz-parque-sao-francisco-demo',
                'address' => 'Avenida Governador Jânio Quadros',
                'address_number' => '2590',
                'district' => 'Parque São Francisco',
                'city' => 'Ferraz de Vasconcelos',
                'phone' => '(11) 94610-1104',
                'latitude' => -23.5306200,
                'longitude' => -46.3904200,
                'map_x' => 91,
                'map_y' => 38,
                'active_orders_count' => 9,
                'opening_hours' => 'Seg a sab: 08:00 as 18:30',
            ],
            [
                'name' => 'Ducha Express Santa Margarida Demo',
                'slug' => 'ferraz-santa-margarida-demo',
                'address' => 'Avenida Stella Mazzucca',
                'address_number' => '780',
                'district' => 'Vila Santa Margarida',
                'city' => 'Ferraz de Vasconcelos',
                'phone' => '(11) 94610-1105',
                'latitude' => -23.5282800,
                'longitude' => -46.3485400,
                'map_x' => 97,
                'map_y' => 37,
                'active_orders_count' => 4,
                'opening_hours' => 'Seg a sab: 08:00 as 18:00',
            ],
            [
                'name' => 'Auto Spa Tanquinho Demo',
                'slug' => 'ferraz-tanquinho-demo',
                'address' => 'Rua Bom Jesus',
                'address_number' => '310',
                'district' => 'Tanquinho',
                'city' => 'Ferraz de Vasconcelos',
                'phone' => '(11) 94610-1106',
                'latitude' => -23.5467800,
                'longitude' => -46.3592100,
                'map_x' => 95,
                'map_y' => 42,
                'active_orders_count' => 3,
                'opening_hours' => 'Seg a sab: 08:00 as 17:30',
            ],
            [
                'name' => 'Lava Rápido Cambiri Demo',
                'slug' => 'ferraz-cambiri-demo',
                'address' => 'Avenida Dom Pedro II',
                'address_number' => '1680',
                'district' => 'Cambiri',
                'city' => 'Ferraz de Vasconcelos',
                'phone' => '(11) 94610-1107',
                'latitude' => -23.5587200,
                'longitude' => -46.3461800,
                'map_x' => 98,
                'map_y' => 45,
                'active_orders_count' => 2,
                'opening_hours' => 'Seg a sab: 08:00 as 18:00',
            ],
            [
                'name' => 'Lava Jato Santo Antônio Demo',
                'slug' => 'ferraz-santo-antonio-demo',
                'address' => 'Rua Treze de Maio',
                'address_number' => '910',
                'district' => 'Vila Santo Antônio',
                'city' => 'Ferraz de Vasconcelos',
                'phone' => '(11) 94610-1108',
                'latitude' => -23.5515400,
                'longitude' => -46.3862600,
                'map_x' => 92,
                'map_y' => 44,
                'status' => WashLocation::STATUS_BUSY,
                'active_orders_count' => 7,
                'opening_hours' => 'Seg a dom: 08:00 as 17:00',
            ],
        ];
    }
}
