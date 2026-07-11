<?php

namespace Tests\Feature\App;

use App\Models\Service;
use App\Models\User;
use App\Models\WashLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WashLocationMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitor_can_view_public_lava_rapido_map(): void
    {
        WashLocation::query()->create([
            'name' => 'Lava Rapido Central',
            'logo_path' => 'wash-location-logos/public-central.png',
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
            ->assertSee('Buscar por nome, bairro ou endereço')
            ->assertSee('Somente abertos')
            ->assertSee('Todos os status')
            ->assertSee('Como chegar')
            ->assertSee('Ver na lista')
            ->assertSee('Lava Rapido Central')
            ->assertSee('Ver detalhes')
            ->assertSee('/lava-rapidos/lava-rapido-central', false)
            ->assertSee('Av. das Nacoes, 1580')
            ->assertSee('-23.54891')
            ->assertSee('-46.63412')
            ->assertSee('Minha localização')
            ->assertSee('Centralizar')
            ->assertSee('Use sua localização para ordenar por proximidade')
            ->assertSee('Distância após localização')
            ->assertSee('Mais próximo')
            ->assertSee('Meus favoritos')
            ->assertSee('Favoritar')
            ->assertSee('Nenhum lava-rápido favorito ainda')
            ->assertSee('autoflow.favoriteLocations', false)
            ->assertSee('data-favorites-filter', false)
            ->assertSee('data-favorite-toggle', false)
            ->assertSee('calculateDistanceInKm', false)
            ->assertSee('data-locations-list', false)
            ->assertSee('data-distance-label', false)
            ->assertSee('data-closest-badge', false)
            ->assertSee('Não foi possível acessar sua localização')
            ->assertSee('data-map-geolocation', false)
            ->assertSee('data-map-reset', false)
            ->assertSee('https://wa.me/5511988881101', false);
    }

    public function test_public_map_ignores_legacy_visible_location_without_slug(): void
    {
        $location = WashLocation::factory()->create([
            'name' => 'Unidade Publica Sem Slug',
            'public_visible' => true,
            'account_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'trial_ends_at' => now()->addDays(10),
        ]);

        DB::table('wash_locations')
            ->where('id', $location->id)
            ->update(['slug' => null]);

        $this->get(route('public.locations.index'))
            ->assertOk()
            ->assertDontSee('Unidade Publica Sem Slug');
    }

    public function test_visitor_can_filter_public_map_by_search_term(): void
    {
        WashLocation::query()->create([
            'name' => 'Lava Rapido Central',
            'address' => 'Av. das Nacoes, 1580',
            'district' => 'Centro',
            'city' => 'Sao Paulo',
            'status' => WashLocation::STATUS_OPEN,
            'latitude' => -23.54891,
            'longitude' => -46.63412,
        ]);

        WashLocation::query()->create([
            'name' => 'Auto Spa Norte',
            'address' => 'Rua das Palmeiras, 200',
            'district' => 'Santana',
            'city' => 'Sao Paulo',
            'status' => WashLocation::STATUS_BUSY,
            'latitude' => -23.50011,
            'longitude' => -46.62221,
        ]);

        $this->get(route('public.locations.index', ['q' => 'Central']))
            ->assertOk()
            ->assertSee('Lava Rapido Central')
            ->assertDontSee('Auto Spa Norte')
            ->assertSee('Resultado filtrado do mapa público');
    }

    public function test_visitor_can_filter_public_map_to_only_open_locations(): void
    {
        Carbon::setTestNow('2026-06-26 10:00:00');

        try {
            WashLocation::query()->create([
                'name' => 'Lava Aberto',
                'address' => 'Rua A, 10',
                'status' => WashLocation::STATUS_OPEN,
                'latitude' => -23.54891,
                'longitude' => -46.63412,
            ]);

            WashLocation::query()->create([
                'name' => 'Lava Fechado',
                'address' => 'Rua B, 20',
                'status' => WashLocation::STATUS_CLOSED,
                'latitude' => -23.50011,
                'longitude' => -46.62221,
            ]);

            $this->get(route('public.locations.index', ['only_open' => 1]))
                ->assertOk()
                ->assertSee('Lava Aberto')
                ->assertDontSee('Lava Fechado')
                ->assertSee('Somente abertos');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_public_map_uses_business_hours_to_show_closed_location(): void
    {
        Carbon::setTestNow('2026-06-26 20:00:00');

        try {
            WashLocation::factory()->create([
                'name' => 'Lava Horario Comercial',
                'status' => WashLocation::STATUS_OPEN,
                'business_hours' => [
                    'monday' => ['is_open' => true, 'opens' => '08:00', 'closes' => '18:00'],
                    'tuesday' => ['is_open' => true, 'opens' => '08:00', 'closes' => '18:00'],
                    'wednesday' => ['is_open' => true, 'opens' => '08:00', 'closes' => '18:00'],
                    'thursday' => ['is_open' => true, 'opens' => '08:00', 'closes' => '18:00'],
                    'friday' => ['is_open' => true, 'opens' => '08:00', 'closes' => '18:00'],
                    'saturday' => ['is_open' => false, 'opens' => '08:00', 'closes' => '18:00'],
                    'sunday' => ['is_open' => false, 'opens' => '08:00', 'closes' => '18:00'],
                ],
            ]);

            $this->get(route('public.locations.index'))
                ->assertOk()
                ->assertSee('Lava Horario Comercial')
                ->assertSee('Fechado')
                ->assertSee('"status":"closed"', false);

            $this->get(route('public.locations.index', ['only_open' => 1]))
                ->assertOk()
                ->assertDontSee('Lava Horario Comercial');
        } finally {
            Carbon::setTestNow();
        }
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

    public function test_visitor_can_view_public_location_detail_page(): void
    {
        $location = WashLocation::query()->create([
            'name' => 'Lava Rapido Central',
            'logo_path' => 'wash-location-logos/public-central.png',
            'address' => 'Av. das Nacoes, 1580',
            'district' => 'Centro',
            'city' => 'Sao Paulo',
            'status' => WashLocation::STATUS_OPEN,
            'latitude' => -23.54891,
            'longitude' => -46.63412,
            'active_orders_count' => 18,
            'phone' => '(11) 98888-1101',
        ]);

        Service::query()->create([
            'name' => 'Lavagem completa',
            'description' => 'Lavagem externa e interna.',
            'base_price' => 60,
            'estimated_minutes' => 50,
            'active' => true,
            'category' => 'Lavagem',
            'wash_location_id' => $location->id,
        ]);

        $otherLocation = WashLocation::factory()->create(['name' => 'Outro Lava Rapido']);
        Service::query()->create([
            'name' => 'Polimento de outra unidade',
            'description' => 'Servico que nao deve aparecer no detalhe publico desta unidade.',
            'base_price' => 220,
            'estimated_minutes' => 120,
            'active' => true,
            'category' => 'Estetica',
            'wash_location_id' => $otherLocation->id,
        ]);

        $this->get(route('public.locations.show', $location))
            ->assertOk()
            ->assertSee('Detalhes da unidade')
            ->assertSee('Lava Rapido Central')
            ->assertSee('storage/wash-location-logos/public-central.png', false)
            ->assertSee('Av. das Nacoes, 1580')
            ->assertSee('Serviços disponíveis')
            ->assertSee('Lavagem completa')
            ->assertDontSee('Polimento de outra unidade')
            ->assertSee('Chamar no WhatsApp')
            ->assertSee('Como chegar')
            ->assertSee('https://wa.me/5511988881101', false);
    }

    public function test_public_location_without_coordinates_uses_address_search_instead_of_fake_marker(): void
    {
        $location = WashLocation::query()->create([
            'name' => 'Lava Sem Coordenadas',
            'address' => 'Rua Sem Fake, 123',
            'district' => 'Centro',
            'city' => 'Campinas',
            'state' => 'SP',
            'status' => WashLocation::STATUS_OPEN,
            'latitude' => null,
            'longitude' => null,
            'phone' => '(19) 98888-1101',
        ]);

        $this->get(route('public.locations.index'))
            ->assertOk()
            ->assertSee('Lava Sem Coordenadas')
            ->assertSee('Mapa pendente')
            ->assertSee('"latitude":null', false)
            ->assertSee('"longitude":null', false)
            ->assertDontSee('destination=-23.55052,-46.63331', false);

        $this->get(route('public.locations.show', $location))
            ->assertOk()
            ->assertSee('https://www.google.com/maps/search/?api=1&amp;query=Rua%20Sem%20Fake%2C%20123%20-%20Centro%20-%20Campinas%2FSP', false)
            ->assertDontSee('destination=-23.55052,-46.63331', false);
    }
}
