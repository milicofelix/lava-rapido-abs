<?php

namespace Tests\Feature\App;

use App\Models\User;
use App\Models\WashLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WashLocationMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_lava_rapido_locations_on_map(): void
    {
        $user = User::factory()->create();

        WashLocation::query()->create([
            'name' => 'Lava Rapido Central',
            'address' => 'Av. das Nacoes, 1580',
            'district' => 'Centro',
            'status' => WashLocation::STATUS_OPEN,
            'map_x' => 62,
            'map_y' => 34,
            'active_orders_count' => 18,
        ]);

        $this->actingAs($user)->get(route('locations.map'))
            ->assertOk()
            ->assertSee('Mapa de Unidades')
            ->assertSee('Lava-rapidos no mapa')
            ->assertSee('Lava Rapido Central')
            ->assertSee('Av. das Nacoes, 1580')
            ->assertSee('18');
    }
}
