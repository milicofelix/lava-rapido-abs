<?php

namespace Tests\Feature\Database;

use App\Models\Service;
use App\Models\WashLocation;
use App\Support\DefaultServices;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefaultServicesSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_services_are_created_without_overwriting_custom_prices(): void
    {
        $location = WashLocation::factory()->create();

        Service::factory()->create([
            'wash_location_id' => $location->id,
            'name' => 'Ducha simples',
            'category' => 'Lavagem',
            'base_price' => 99.90,
            'estimated_minutes' => 12,
            'active' => false,
        ]);

        DefaultServices::seedForLocation($location);

        $this->assertDatabaseHas('services', [
            'wash_location_id' => $location->id,
            'name' => 'Ducha simples',
            'base_price' => 99.90,
            'estimated_minutes' => 12,
            'active' => false,
        ]);

        $this->assertDatabaseHas('services', [
            'wash_location_id' => $location->id,
            'name' => 'Lavagem completa',
            'base_price' => 90.00,
            'active' => true,
        ]);
    }
}
