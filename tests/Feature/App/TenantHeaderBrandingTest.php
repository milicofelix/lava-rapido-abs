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
            ->assertSee('Trial:')
            ->assertDontSee('Lava Rapido Central');
    }

    public function test_super_admin_sees_product_admin_branding_in_header(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'wash_location_id' => null,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Administração do produto')
            ->assertSee('AutoFlow Admin');
    }
}
