<?php

namespace Tests\Feature\App;

use App\Models\User;
use App\Models\WashLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WashLocationMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitor_can_view_public_lava_rapido_map(): void
    {
        WashLocation::query()->create([
            'name' => 'Lava Rapido Central',
            'address' => 'Av. das Nacoes, 1580',
            'district' => 'Centro',
            'status' => WashLocation::STATUS_OPEN,
            'map_x' => 62,
            'map_y' => 34,
            'latitude' => -23.54891,
            'longitude' => -46.63412,
            'active_orders_count' => 18,
            'phone' => '(11) 98888-1101',
        ]);

        $this->get(route('public.locations.index'))
            ->assertOk()
            ->assertSee('Lava-rápidos próximos')
            ->assertSee('Encontre um lava-rápido próximo')
            ->assertSee('Lava Rapido Central')
            ->assertSee('Av. das Nacoes, 1580')
            ->assertSee('-23.54891')
            ->assertSee('-46.63412')
            ->assertSee('Minha localização')
            ->assertSee('Centralizar')
            ->assertSee('Use sua localização para ordenar por proximidade')
            ->assertSee('Distância após localização')
            ->assertSee('Mais próximo')
            ->assertSee('calculateDistanceInKm', false)
            ->assertSee('data-locations-list', false)
            ->assertSee('data-distance-label', false)
            ->assertSee('data-closest-badge', false)
            ->assertSee('Não foi possível acessar sua localização')
            ->assertSee('data-map-geolocation', false)
            ->assertSee('data-map-reset', false)
            ->assertSee('https://wa.me/5511988881101', false);
    }

    public function test_internal_dashboard_does_not_show_old_map_link(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Unidades no mapa')
            ->assertDontSee(route('public.locations.index'));
    }

    public function test_old_internal_map_route_was_removed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/mapa')
            ->assertNotFound();
    }
}
