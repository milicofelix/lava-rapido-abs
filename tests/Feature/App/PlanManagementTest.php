<?php

namespace Tests\Feature\App;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_cria_plano(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'wash_location_id' => null,
        ]);

        $this->actingAs($superAdmin)
            ->post(route('super-admin.plans.store'), [
                'name' => 'Starter',
                'price' => '49.90',
                'trial_days' => 15,
                'is_active' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('plans', [
            'name' => 'Starter',
            'price' => 49.90,
            'trial_days' => 15,
            'is_active' => true,
        ]);
    }

    public function test_super_admin_edita_plano(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'wash_location_id' => null,
        ]);
        $plan = Plan::factory()->create(['name' => 'Professional', 'price' => 89.90]);

        $this->actingAs($superAdmin)
            ->put(route('super-admin.plans.update', $plan), [
                'name' => 'Professional Plus',
                'price' => '99.90',
                'trial_days' => 20,
                'is_active' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'name' => 'Professional Plus',
            'price' => 99.90,
            'trial_days' => 20,
            'is_active' => true,
        ]);
    }

    public function test_plano_pode_ser_desativado(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'wash_location_id' => null,
        ]);
        $plan = Plan::factory()->create(['is_active' => true]);

        $this->actingAs($superAdmin)
            ->patch(route('super-admin.plans.deactivate', $plan))
            ->assertRedirect();

        $this->assertFalse($plan->refresh()->is_active);
    }
}
