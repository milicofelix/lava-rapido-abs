<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['Starter', 49.90, 15],
            ['Professional', 89.90, 15],
            ['Enterprise', 149.90, 15],
        ])->each(fn (array $plan) => Plan::query()->updateOrCreate(
            ['name' => $plan[0]],
            [
                'price' => $plan[1],
                'trial_days' => $plan[2],
                'is_active' => true,
            ],
        ));
    }
}
