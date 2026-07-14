<?php

namespace Tests\Feature\App;

use App\Models\User;
use App\Models\WashLocation;
use App\Models\WashOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TenantOperationalScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_sees_only_own_location_wash_orders_on_kanban(): void
    {
        $locationA = $this->createLocation('AutoFlow Unidade A');
        $locationA->update(['logo_path' => 'wash-location-logos/kanban-a.png']);
        $locationB = $this->createLocation('AutoFlow Unidade B');
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $locationA->id,
        ]);

        $ownOrder = WashOrder::factory()->create([
            'wash_location_id' => $locationA->id,
            'status' => WashOrder::STATUS_WASHING,
        ]);

        $otherOrder = WashOrder::factory()->create([
            'wash_location_id' => $locationB->id,
            'status' => WashOrder::STATUS_WASHING,
        ]);

        $this->actingAs($owner)->get(route('kanban'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Kanban')
                ->where('currentLocation.name', 'AutoFlow Unidade A')
                ->where('logoUrl', $locationA->logoUrl())
                ->where('columns.1.orders.0.id', $ownOrder->id)
                ->missing('columns.1.orders.1')
            );

        $this->actingAs($owner)->get(route('kanban.feed'))
            ->assertOk()
            ->assertJsonFragment(['id' => $ownOrder->id])
            ->assertJsonMissing(['id' => $otherOrder->id]);
    }

    public function test_owner_dashboard_is_filtered_by_current_location(): void
    {
        $locationA = $this->createLocation('Lava Rápido do Owner');
        $locationB = $this->createLocation('Lava Rápido de Outro Cliente');
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $locationA->id,
        ]);

        WashOrder::factory()->create([
            'wash_location_id' => $locationA->id,
            'status' => WashOrder::STATUS_READY,
            'entered_at' => now(),
        ]);

        WashOrder::factory()->create([
            'wash_location_id' => $locationB->id,
            'status' => WashOrder::STATUS_READY,
            'entered_at' => now(),
        ]);

        $this->actingAs($owner)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Unidade atual')
            ->assertSee('Lava Rápido do Owner')
            ->assertDontSee('Lava Rápido de Outro Cliente')
            ->assertSee('Os indicadores desta tela estão filtrados por este lava-rápido.');
    }

    private function createLocation(string $name): WashLocation
    {
        return WashLocation::create([
            'name' => $name,
            'address' => 'Rua Teste, 100',
            'district' => 'Centro',
            'city' => 'Sao Paulo',
            'status' => WashLocation::STATUS_OPEN,
            'account_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'public_visible' => true,
            'trial_started_at' => now(),
            'trial_ends_at' => now()->addDays(15),
            'map_x' => 50,
            'map_y' => 50,
            'latitude' => -23.55052,
            'longitude' => -46.63331,
            'active_orders_count' => 0,
            'phone' => '(11) 99999-0000',
        ]);
    }
}
