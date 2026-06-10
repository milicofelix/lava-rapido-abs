<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@lavaabs.test'],
            [
                'name' => 'Admin ABS',
                'role' => 'admin',
                'password' => Hash::make('password'),
            ],
        );

        $customers = Customer::count() === 0
            ? Customer::factory()->count(6)->hasVehicles(1)->create()
            : Customer::query()->limit(6)->get();

        $customers->first()?->vehicles()->firstOrCreate(
            ['plate' => 'ABC1D23'],
            [
                'model' => 'Corolla',
                'brand' => 'Toyota',
                'color' => 'Prata',
                'type' => 'carro',
            ],
        );

        collect([
            ['Lavagem completa', 'Lavagem', 80, 70],
            ['Ducha simples', 'Lavagem', 35, 25],
            ['Ducha + aspiracao', 'Lavagem', 55, 45],
            ['Cera', 'Estetica', 45, 35],
            ['Higienizacao interna', 'Estetica', 180, 180],
            ['Lavagem de motor', 'Estetica', 120, 90],
            ['Polimento', 'Estetica', 260, 180],
            ['Cristalizacao', 'Estetica', 320, 210],
            ['Vitrificacao de pintura', 'Estetica', 650, 360],
            ['Limpeza de bancos', 'Estetica', 160, 120],
            ['Oxi-sanitizacao', 'Estetica', 95, 45],
            ['Lavagem de SUV', 'Lavagem', 100, 85],
            ['Lavagem de caminhonete', 'Lavagem', 130, 100],
            ['Lavagem de moto', 'Moto', 30, 25],
        ])->each(fn ($service) => Service::updateOrCreate(
            ['name' => $service[0]],
            [
                'category' => $service[1],
                'base_price' => $service[2],
                'estimated_minutes' => $service[3],
                'active' => true,
                'description' => 'Servico inicial do catalogo Lava Rapido ABS.',
            ],
        ));

        $this->call(WashLocationSeeder::class);
        $this->call(DemoWashOrdersSeeder::class);
    }
}
