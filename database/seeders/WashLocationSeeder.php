<?php

namespace Database\Seeders;

use App\Models\WashLocation;
use Illuminate\Database\Seeder;

class WashLocationSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['Lava Rapido Central', 'Av. das Nacoes, 1580', 'Centro', WashLocation::STATUS_OPEN, 62, 34, -23.5489100, -46.6341200, 18, '(11) 98888-1101'],
            ['Lava Rapido Premium', 'Rua Joao XXIII, 450', 'Bela Vista', WashLocation::STATUS_OPEN, 36, 48, -23.5611200, -46.6549800, 12, '(11) 98888-1102'],
            ['Auto Spa Express', 'Av. Brasil, 2100', 'Jardim America', WashLocation::STATUS_BUSY, 70, 58, -23.5704300, -46.6722100, 7, '(11) 98888-1103'],
            ['Lava Car Sul', 'Rua das Flores, 90', 'Vila Nova', WashLocation::STATUS_OPEN, 52, 72, -23.6103800, -46.6409900, 5, '(11) 98888-1104'],
            ['AutoFlow Norte', 'Av. Cruzeiro do Sul, 840', 'Santana', WashLocation::STATUS_OPEN, 48, 24, -23.5122200, -46.6255100, 9, '(11) 98888-1105'],
            ['Lava Rapido Oeste', 'Rua Cerro Cora, 1220', 'Lapa', WashLocation::STATUS_BUSY, 24, 62, -23.5357400, -46.7053300, 6, '(11) 98888-1106'],
            ['Estetica Automotiva Leste', 'Av. Aricanduva, 3900', 'Tatuape', WashLocation::STATUS_OPEN, 82, 46, -23.5398200, -46.5661600, 11, '(11) 98888-1107'],
            ['Ducha Rapida Morumbi', 'Av. Giovanni Gronchi, 3010', 'Morumbi', WashLocation::STATUS_CLOSED, 32, 76, -23.6164300, -46.7308100, 0, '(11) 98888-1108'],
        ])->each(fn (array $location) => WashLocation::query()->updateOrCreate(
            ['name' => $location[0]],
            [
                'address' => $location[1],
                'district' => $location[2],
                'city' => 'Sao Paulo',
                'status' => $location[3],
                'map_x' => $location[4],
                'map_y' => $location[5],
                'latitude' => $location[6],
                'longitude' => $location[7],
                'active_orders_count' => $location[8],
                'phone' => $location[9],
            ],
        ));
    }
}
