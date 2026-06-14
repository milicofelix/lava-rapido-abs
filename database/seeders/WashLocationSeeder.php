<?php

namespace Database\Seeders;

use App\Models\WashLocation;
use App\Support\DefaultServices;
use Illuminate\Database\Seeder;

class WashLocationSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        collect($this->locations())->each(function (array $location) use ($now): void {
            $washLocation = WashLocation::query()->updateOrCreate(
                ['name' => $location['name']],
                [
                    'address' => $location['address'],
                    'address_number' => $location['address_number'],
                    'district' => $location['district'],
                    'city' => 'Sao Paulo',
                    'state' => 'SP',
                    'status' => $location['status'],
                    'account_status' => WashLocation::ACCOUNT_STATUS_ACTIVE,
                    'subscription_status' => WashLocation::ACCOUNT_STATUS_ACTIVE,
                    'public_visible' => true,
                    'trial_started_at' => $now->copy()->subDays(20),
                    'trial_ends_at' => $now->copy()->subDays(5),
                    'subscription_ends_at' => $now->copy()->addMonths(2),
                    'blocked_at' => null,
                    'map_x' => $location['map_x'],
                    'map_y' => $location['map_y'],
                    'latitude' => $location['latitude'],
                    'longitude' => $location['longitude'],
                    'active_orders_count' => $location['active_orders_count'],
                    'phone' => $location['phone'],
                    'opening_hours' => $location['opening_hours'],
                ],
            );

            DefaultServices::seedForLocation($washLocation);
        });
    }

    private function locations(): array
    {
        return [
            // Zona Leste: maior concentracao para validar o mapa com densidade.
            ['name' => 'Auto Spa Tatuape Radial', 'address' => 'Av. Radial Leste', 'address_number' => '3100', 'district' => 'Tatuape', 'status' => WashLocation::STATUS_OPEN, 'map_x' => 74, 'map_y' => 45, 'latitude' => -23.5412100, 'longitude' => -46.5763300, 'active_orders_count' => 16, 'phone' => '(11) 98888-1201', 'opening_hours' => 'Seg a sab: 08:00 as 18:00'],
            ['name' => 'Lava Rapido Analia Franco', 'address' => 'Av. Regente Feijo', 'address_number' => '1739', 'district' => 'Jardim Analia Franco', 'status' => WashLocation::STATUS_OPEN, 'map_x' => 77, 'map_y' => 49, 'latitude' => -23.5598500, 'longitude' => -46.5592100, 'active_orders_count' => 12, 'phone' => '(11) 98888-1202', 'opening_hours' => 'Seg a sex: 08:00 as 19:00; Sab: 08:00 as 17:00'],
            ['name' => 'Ducha Premium Mooca', 'address' => 'Rua da Mooca', 'address_number' => '3020', 'district' => 'Mooca', 'status' => WashLocation::STATUS_BUSY, 'map_x' => 70, 'map_y' => 50, 'latitude' => -23.5595400, 'longitude' => -46.5989500, 'active_orders_count' => 9, 'phone' => '(11) 98888-1203', 'opening_hours' => 'Seg a sab: 07:30 as 18:30'],
            ['name' => 'Estetica Automotiva Belem', 'address' => 'Av. Celso Garcia', 'address_number' => '2190', 'district' => 'Belem', 'status' => WashLocation::STATUS_OPEN, 'map_x' => 69, 'map_y' => 47, 'latitude' => -23.5376900, 'longitude' => -46.5999700, 'active_orders_count' => 8, 'phone' => '(11) 98888-1204', 'opening_hours' => 'Seg a sab: 08:00 as 18:00'],
            ['name' => 'Lava Rapido Penha Center', 'address' => 'Av. Penha de Franca', 'address_number' => '470', 'district' => 'Penha', 'status' => WashLocation::STATUS_OPEN, 'map_x' => 82, 'map_y' => 43, 'latitude' => -23.5238900, 'longitude' => -46.5468400, 'active_orders_count' => 14, 'phone' => '(11) 98888-1205', 'opening_hours' => 'Seg a dom: 08:00 as 17:00'],
            ['name' => 'AutoFlow Vila Matilde', 'address' => 'Av. Waldemar Carlos Pereira', 'address_number' => '1200', 'district' => 'Vila Matilde', 'status' => WashLocation::STATUS_OPEN, 'map_x' => 85, 'map_y' => 45, 'latitude' => -23.5362300, 'longitude' => -46.5237900, 'active_orders_count' => 11, 'phone' => '(11) 98888-1206', 'opening_hours' => 'Seg a sab: 08:00 as 18:00'],
            ['name' => 'Lava Jato Aricanduva', 'address' => 'Av. Aricanduva', 'address_number' => '5555', 'district' => 'Aricanduva', 'status' => WashLocation::STATUS_BUSY, 'map_x' => 89, 'map_y' => 51, 'latitude' => -23.5653900, 'longitude' => -46.5074700, 'active_orders_count' => 18, 'phone' => '(11) 98888-1207', 'opening_hours' => 'Seg a sab: 07:00 as 19:00'],
            ['name' => 'Auto Spa Vila Formosa', 'address' => 'Av. Dr. Eduardo Cotching', 'address_number' => '1320', 'district' => 'Vila Formosa', 'status' => WashLocation::STATUS_OPEN, 'map_x' => 80, 'map_y' => 52, 'latitude' => -23.5662400, 'longitude' => -46.5447000, 'active_orders_count' => 7, 'phone' => '(11) 98888-1208', 'opening_hours' => 'Seg a sab: 08:00 as 18:00'],
            ['name' => 'Ducha Rapida Sao Mateus', 'address' => 'Av. Mateo Bei', 'address_number' => '2400', 'district' => 'Sao Mateus', 'status' => WashLocation::STATUS_OPEN, 'map_x' => 95, 'map_y' => 59, 'latitude' => -23.6047300, 'longitude' => -46.4772100, 'active_orders_count' => 10, 'phone' => '(11) 98888-1209', 'opening_hours' => 'Seg a sab: 08:00 as 18:30'],
            ['name' => 'Lava Rapido Itaquera Metro', 'address' => 'Av. Jose Pinheiro Borges', 'address_number' => '980', 'district' => 'Itaquera', 'status' => WashLocation::STATUS_OPEN, 'map_x' => 97, 'map_y' => 47, 'latitude' => -23.5423400, 'longitude' => -46.4712900, 'active_orders_count' => 15, 'phone' => '(11) 98888-1210', 'opening_hours' => 'Seg a sab: 07:30 as 18:30'],
            ['name' => 'Estetica Automotiva Guaianases', 'address' => 'Estrada Itaquera Guaianases', 'address_number' => '2600', 'district' => 'Guaianases', 'status' => WashLocation::STATUS_OPEN, 'map_x' => 99, 'map_y' => 43, 'latitude' => -23.5428700, 'longitude' => -46.4158200, 'active_orders_count' => 6, 'phone' => '(11) 98888-1211', 'opening_hours' => 'Seg a sab: 08:00 as 18:00'],
            ['name' => 'Lava Car Sao Miguel', 'address' => 'Av. Sao Miguel', 'address_number' => '5800', 'district' => 'Sao Miguel Paulista', 'status' => WashLocation::STATUS_BUSY, 'map_x' => 96, 'map_y' => 32, 'latitude' => -23.4932100, 'longitude' => -46.4389300, 'active_orders_count' => 13, 'phone' => '(11) 98888-1212', 'opening_hours' => 'Seg a sab: 07:30 as 18:00'],
            ['name' => 'Auto Spa Ermelino Matarazzo', 'address' => 'Av. Paranagua', 'address_number' => '1180', 'district' => 'Ermelino Matarazzo', 'status' => WashLocation::STATUS_OPEN, 'map_x' => 91, 'map_y' => 35, 'latitude' => -23.4937600, 'longitude' => -46.4833100, 'active_orders_count' => 8, 'phone' => '(11) 98888-1213', 'opening_hours' => 'Seg a sab: 08:00 as 18:00'],
            ['name' => 'Lava Rapido Cidade Tiradentes', 'address' => 'Av. dos Metalurgicos', 'address_number' => '1900', 'district' => 'Cidade Tiradentes', 'status' => WashLocation::STATUS_OPEN, 'map_x' => 99, 'map_y' => 60, 'latitude' => -23.5939600, 'longitude' => -46.4091200, 'active_orders_count' => 5, 'phone' => '(11) 98888-1214', 'opening_hours' => 'Seg a sab: 08:00 as 17:30'],
            ['name' => 'Ducha Express Sapopemba', 'address' => 'Av. Sapopemba', 'address_number' => '7200', 'district' => 'Sapopemba', 'status' => WashLocation::STATUS_OPEN, 'map_x' => 89, 'map_y' => 58, 'latitude' => -23.6015300, 'longitude' => -46.5113600, 'active_orders_count' => 12, 'phone' => '(11) 98888-1215', 'opening_hours' => 'Seg a sab: 08:00 as 18:30'],
            ['name' => 'AutoFlow Vila Prudente', 'address' => 'Av. Prof. Luiz Ignacio Anhaia Mello', 'address_number' => '1350', 'district' => 'Vila Prudente', 'status' => WashLocation::STATUS_OPEN, 'map_x' => 73, 'map_y' => 55, 'latitude' => -23.5845700, 'longitude' => -46.5815600, 'active_orders_count' => 9, 'phone' => '(11) 98888-1216', 'opening_hours' => 'Seg a sab: 08:00 as 18:00'],

            // Demais regioes de Sao Paulo.
            ['name' => 'Lava Rapido Paulista Jardins', 'address' => 'Alameda Santos', 'address_number' => '1700', 'district' => 'Jardins', 'status' => WashLocation::STATUS_OPEN, 'map_x' => 49, 'map_y' => 52, 'latitude' => -23.5632600, 'longitude' => -46.6587200, 'active_orders_count' => 10, 'phone' => '(11) 98888-1301', 'opening_hours' => 'Seg a sab: 08:00 as 18:00'],
            ['name' => 'Auto Spa Pinheiros Faria Lima', 'address' => 'Av. Brigadeiro Faria Lima', 'address_number' => '1650', 'district' => 'Pinheiros', 'status' => WashLocation::STATUS_BUSY, 'map_x' => 39, 'map_y' => 55, 'latitude' => -23.5708600, 'longitude' => -46.6941600, 'active_orders_count' => 14, 'phone' => '(11) 98888-1302', 'opening_hours' => 'Seg a sex: 08:00 as 19:00; Sab: 08:00 as 16:00'],
            ['name' => 'Lava Car Lapa', 'address' => 'Rua Cerro Cora', 'address_number' => '1220', 'district' => 'Lapa', 'status' => WashLocation::STATUS_OPEN, 'map_x' => 32, 'map_y' => 48, 'latitude' => -23.5357400, 'longitude' => -46.7053300, 'active_orders_count' => 7, 'phone' => '(11) 98888-1303', 'opening_hours' => 'Seg a sab: 08:00 as 18:00'],
            ['name' => 'Ducha Premium Perdizes', 'address' => 'Av. Sumaré', 'address_number' => '1150', 'district' => 'Perdizes', 'status' => WashLocation::STATUS_OPEN, 'map_x' => 41, 'map_y' => 47, 'latitude' => -23.5366200, 'longitude' => -46.6739600, 'active_orders_count' => 6, 'phone' => '(11) 98888-1304', 'opening_hours' => 'Seg a sab: 08:00 as 18:00'],
            ['name' => 'Lava Rapido Santana', 'address' => 'Av. Cruzeiro do Sul', 'address_number' => '2400', 'district' => 'Santana', 'status' => WashLocation::STATUS_OPEN, 'map_x' => 54, 'map_y' => 33, 'latitude' => -23.5027200, 'longitude' => -46.6245300, 'active_orders_count' => 11, 'phone' => '(11) 98888-1305', 'opening_hours' => 'Seg a sab: 07:30 as 18:00'],
            ['name' => 'Auto Spa Tucuruvi', 'address' => 'Av. Tucuruvi', 'address_number' => '808', 'district' => 'Tucuruvi', 'status' => WashLocation::STATUS_OPEN, 'map_x' => 56, 'map_y' => 27, 'latitude' => -23.4786800, 'longitude' => -46.6034200, 'active_orders_count' => 8, 'phone' => '(11) 98888-1306', 'opening_hours' => 'Seg a sab: 08:00 as 18:00'],
            ['name' => 'Lava Rapido Campo Belo', 'address' => 'Av. Vereador Jose Diniz', 'address_number' => '3300', 'district' => 'Campo Belo', 'status' => WashLocation::STATUS_BUSY, 'map_x' => 48, 'map_y' => 70, 'latitude' => -23.6261300, 'longitude' => -46.6683900, 'active_orders_count' => 12, 'phone' => '(11) 98888-1307', 'opening_hours' => 'Seg a sab: 08:00 as 18:30'],
            ['name' => 'Ducha Express Santo Amaro', 'address' => 'Av. Santo Amaro', 'address_number' => '5200', 'district' => 'Santo Amaro', 'status' => WashLocation::STATUS_OPEN, 'map_x' => 44, 'map_y' => 76, 'latitude' => -23.6520100, 'longitude' => -46.7098700, 'active_orders_count' => 9, 'phone' => '(11) 98888-1308', 'opening_hours' => 'Seg a sab: 08:00 as 18:00'],
            ['name' => 'AutoFlow Jabaquara', 'address' => 'Av. Engenheiro Armando de Arruda Pereira', 'address_number' => '900', 'district' => 'Jabaquara', 'status' => WashLocation::STATUS_OPEN, 'map_x' => 57, 'map_y' => 74, 'latitude' => -23.6440200, 'longitude' => -46.6415800, 'active_orders_count' => 6, 'phone' => '(11) 98888-1309', 'opening_hours' => 'Seg a sab: 08:00 as 18:00'],
            ['name' => 'Lava Rapido Ipiranga', 'address' => 'Av. Nazaré', 'address_number' => '1200', 'district' => 'Ipiranga', 'status' => WashLocation::STATUS_OPEN, 'map_x' => 64, 'map_y' => 60, 'latitude' => -23.5894100, 'longitude' => -46.6094700, 'active_orders_count' => 10, 'phone' => '(11) 98888-1310', 'opening_hours' => 'Seg a sab: 08:00 as 18:00'],
            ['name' => 'Estetica Automotiva Morumbi', 'address' => 'Av. Giovanni Gronchi', 'address_number' => '3010', 'district' => 'Morumbi', 'status' => WashLocation::STATUS_CLOSED, 'map_x' => 34, 'map_y' => 72, 'latitude' => -23.6164300, 'longitude' => -46.7308100, 'active_orders_count' => 0, 'phone' => '(11) 98888-1311', 'opening_hours' => 'Seg a sab: 08:00 as 18:00'],
            ['name' => 'Auto Spa Butanta', 'address' => 'Av. Vital Brasil', 'address_number' => '950', 'district' => 'Butanta', 'status' => WashLocation::STATUS_OPEN, 'map_x' => 32, 'map_y' => 60, 'latitude' => -23.5719000, 'longitude' => -46.7212900, 'active_orders_count' => 8, 'phone' => '(11) 98888-1312', 'opening_hours' => 'Seg a sab: 08:00 as 18:00'],
            ['name' => 'Lava Rapido Pirituba', 'address' => 'Av. Raimundo Pereira de Magalhaes', 'address_number' => '3800', 'district' => 'Pirituba', 'status' => WashLocation::STATUS_OPEN, 'map_x' => 31, 'map_y' => 31, 'latitude' => -23.4887200, 'longitude' => -46.7243300, 'active_orders_count' => 7, 'phone' => '(11) 98888-1313', 'opening_hours' => 'Seg a sab: 08:00 as 18:00'],
            ['name' => 'Ducha Rapida Casa Verde', 'address' => 'Av. Braz Leme', 'address_number' => '1800', 'district' => 'Casa Verde', 'status' => WashLocation::STATUS_OPEN, 'map_x' => 48, 'map_y' => 37, 'latitude' => -23.5096300, 'longitude' => -46.6543300, 'active_orders_count' => 5, 'phone' => '(11) 98888-1314', 'opening_hours' => 'Seg a sab: 08:00 as 17:30'],
            ['name' => 'Auto Spa Liberdade', 'address' => 'Rua Vergueiro', 'address_number' => '800', 'district' => 'Liberdade', 'status' => WashLocation::STATUS_OPEN, 'map_x' => 58, 'map_y' => 55, 'latitude' => -23.5680100, 'longitude' => -46.6381800, 'active_orders_count' => 9, 'phone' => '(11) 98888-1315', 'opening_hours' => 'Seg a sab: 08:00 as 18:00'],
            ['name' => 'Lava Rapido Vila Mariana', 'address' => 'Rua Domingos de Morais', 'address_number' => '1800', 'district' => 'Vila Mariana', 'status' => WashLocation::STATUS_OPEN, 'map_x' => 58, 'map_y' => 62, 'latitude' => -23.5904300, 'longitude' => -46.6348800, 'active_orders_count' => 13, 'phone' => '(11) 98888-1316', 'opening_hours' => 'Seg a sab: 07:30 as 18:30'],
        ];
    }
}
