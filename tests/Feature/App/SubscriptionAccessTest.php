<?php

namespace Tests\Feature\App;

use App\Models\User;
use App\Models\WashLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_unidade_em_trial_acessa_o_sistema(): void
    {
        $location = WashLocation::factory()->create([
            'account_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'trial_ends_at' => now()->addDays(10),
            'subscription_ends_at' => null,
            'blocked_at' => null,
        ]);

        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $location->id,
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_unidade_com_trial_expirado_e_bloqueada(): void
    {
        $location = WashLocation::factory()->create([
            'account_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'trial_ends_at' => now()->subDay(),
            'subscription_ends_at' => null,
            'blocked_at' => now(),
        ]);

        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $location->id,
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertRedirect(route('subscription.blocked'));
    }

    public function test_assinatura_ativa_acessa_o_sistema(): void
    {
        $location = WashLocation::factory()->create([
            'account_status' => WashLocation::ACCOUNT_STATUS_ACTIVE,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_ACTIVE,
            'trial_ends_at' => now()->subDays(5),
            'subscription_ends_at' => now()->addMonth(),
            'blocked_at' => null,
        ]);

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'wash_location_id' => $location->id,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('kanban'))
            ->assertOk();
    }

    public function test_assinatura_vencida_e_bloqueada_mesmo_sem_rotina_diaria(): void
    {
        $location = WashLocation::factory()->create([
            'account_status' => WashLocation::ACCOUNT_STATUS_ACTIVE,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_ACTIVE,
            'trial_ends_at' => now()->subMonth(),
            'subscription_ends_at' => now()->subDay(),
            'blocked_at' => null,
        ]);

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'wash_location_id' => $location->id,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('kanban'))
            ->assertRedirect(route('subscription.blocked'));

        $this->assertDatabaseHas('wash_locations', [
            'id' => $location->id,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_EXPIRED,
            'account_status' => WashLocation::ACCOUNT_STATUS_EXPIRED,
        ]);
    }

    public function test_super_admin_acessa_admin_produto_sem_unidade(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'wash_location_id' => null,
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.location-requests.index'))
            ->assertOk();
    }

    public function test_owner_ve_aviso_de_trial_quando_esta_perto_de_expirar(): void
    {
        $location = WashLocation::factory()->create([
            'account_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'trial_ends_at' => now()->addDays(3),
            'subscription_ends_at' => null,
            'blocked_at' => null,
        ]);

        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $location->id,
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Período gratuito em andamento');
    }
}
