<?php

namespace Tests\Feature\App;

use App\Models\User;
use App\Models\WashLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantHeaderBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_sees_own_wash_location_name_in_header(): void
    {
        $location = WashLocation::factory()->create([
            'name' => 'Lava Rápido Header Owner',
            'logo_path' => 'wash-location-logos/header-owner.png',
            'account_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'trial_ends_at' => now()->addDays(12),
        ]);

        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $location->id,
        ]);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Unidade atual')
            ->assertSee('Lava Rápido Header Owner')
            ->assertSee('storage/wash-location-logos/header-owner.png', false)
            ->assertSee('Trial:')
            ->assertDontSee('Lava Rapido Central');
    }

    public function test_owner_sees_customer_and_vehicle_menu_links(): void
    {
        $location = WashLocation::factory()->create([
            'account_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'trial_ends_at' => now()->addDays(12),
        ]);

        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $location->id,
        ]);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('href="'.route('customers.index').'"', false)
            ->assertSee('href="'.route('vehicles.index').'"', false);
    }

    public function test_operator_does_not_see_customer_and_vehicle_menu_links(): void
    {
        $location = WashLocation::factory()->create([
            'account_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'trial_ends_at' => now()->addDays(12),
        ]);

        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'wash_location_id' => $location->id,
        ]);

        $this->actingAs($operator)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('href="'.route('customers.index').'"', false)
            ->assertDontSee('href="'.route('vehicles.index').'"', false);
    }

    public function test_super_admin_sees_product_admin_branding_in_header(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'wash_location_id' => null,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('dashboard'))
            ->assertRedirect(route('super-admin.location-requests.index'));

        $this->actingAs($superAdmin)
            ->get(route('super-admin.location-requests.index'))
            ->assertOk()
            ->assertSee('Administração do produto')
            ->assertSee('AutoFlow Admin');
    }
}
