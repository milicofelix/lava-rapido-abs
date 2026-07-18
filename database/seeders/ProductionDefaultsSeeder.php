<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductionDefaultsSeeder extends Seeder
{
    /**
     * Seed only safe production defaults.
     *
     * This seeder does not create demo wash locations, customers, vehicles or wash orders.
     */
    public function run(): void
    {
        $this->call([
            SuperAdminSeeder::class,
            PlanSeeder::class,
            DefaultServicesSeeder::class,
        ]);
    }
}
