<?php

namespace Database\Seeders;

use App\Models\Customer;
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

        $this->call(PlanSeeder::class);

        $this->call(WashLocationSeeder::class);
        $this->call(DefaultServicesSeeder::class);
        $this->call(DemoWashOrdersSeeder::class);
    }
}
