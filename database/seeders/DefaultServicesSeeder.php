<?php

namespace Database\Seeders;

use App\Models\WashLocation;
use App\Support\DefaultServices;
use Illuminate\Database\Seeder;

class DefaultServicesSeeder extends Seeder
{
    public function run(): void
    {
        WashLocation::query()
            ->orderBy('id')
            ->get()
            ->each(fn (WashLocation $location) => DefaultServices::seedForLocation($location));
    }
}
