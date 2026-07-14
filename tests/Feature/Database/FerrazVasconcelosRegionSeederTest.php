<?php

namespace Tests\Feature\Database;

use App\Models\Service;
use App\Models\WashLocation;
use App\Models\WashOrder;
use Database\Seeders\Regional\FerrazVasconcelosRegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FerrazVasconcelosRegionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_region_seeder_creates_ferraz_locations_with_orders(): void
    {
        $this->seed(FerrazVasconcelosRegionSeeder::class);

        $locations = WashLocation::query()
            ->where('city', 'Ferraz de Vasconcelos')
            ->where('name', 'like', '%Demo')
            ->get();

        $this->assertCount(8, $locations);
        $this->assertTrue($locations->every(fn (WashLocation $location) => $location->latitude !== null
            && $location->longitude !== null
            && $location->public_visible
            && $location->isSubscriptionActive()));

        $this->assertGreaterThanOrEqual(8 * 9, Service::query()
            ->whereIn('wash_location_id', $locations->pluck('id'))
            ->count());
        $this->assertGreaterThan(0, WashOrder::query()
            ->whereIn('wash_location_id', $locations->pluck('id'))
            ->where('code', 'like', 'REG-FERRAZ-%')
            ->count());
    }

    public function test_region_seeder_is_idempotent_for_orders(): void
    {
        $this->seed(FerrazVasconcelosRegionSeeder::class);

        $orderCount = WashOrder::query()
            ->where('code', 'like', 'REG-FERRAZ-%')
            ->count();

        $this->seed(FerrazVasconcelosRegionSeeder::class);

        $this->assertSame($orderCount, WashOrder::query()
            ->where('code', 'like', 'REG-FERRAZ-%')
            ->count());
    }
}
