<?php

namespace Tests\Feature\App;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WashLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SuperAdminSubscriptionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_lista_unidades(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'wash_location_id' => null,
            'is_active' => true,
        ]);

        $location = WashLocation::factory()->create([
            'name' => 'Lava Rapido Central',
            'city' => 'Sao Paulo/SP',
            'subscription_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'account_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
        ]);

        User::factory()->create([
            'name' => 'Joao Owner',
            'email' => 'joao.owner@autoflow.test',
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $location->id,
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.locations.index'))
            ->assertOk()
            ->assertSee('Unidades cadastradas')
            ->assertSee('Lava Rapido Central')
            ->assertSee('Joao Owner')
            ->assertSee('Trial')
            ->assertSee('data-onboarding-tour', false)
            ->assertSee('super-admin.locations.index.v1')
            ->assertSee('data-tour="super-locations-intro"', false)
            ->assertSee('data-tour="super-locations-summary"', false)
            ->assertSee('data-tour="super-locations-filters"', false)
            ->assertSee('data-tour="super-locations-list"', false)
            ->assertSee('data-tour="super-locations-row"', false)
            ->assertSee('data-tour="super-locations-actions"', false);
    }

    public function test_super_admin_lista_unidade_sem_slug_legado(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'wash_location_id' => null,
            'is_active' => true,
        ]);

        $location = WashLocation::factory()->create([
            'name' => 'Unidade Legada Sem Slug',
            'subscription_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'account_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
        ]);

        DB::table('wash_locations')
            ->where('id', $location->id)
            ->update(['slug' => null]);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.locations.index'))
            ->assertOk()
            ->assertSee('Unidade Legada Sem Slug')
            ->assertSee('admin-produto/unidades/'.$location->id.'/prorrogar-trial', false)
            ->assertSee('admin-produto/unidades/'.$location->id.'/ativar-assinatura', false);
    }

    public function test_super_admin_prorroga_trial(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'wash_location_id' => null,
            'is_active' => true,
        ]);

        $location = WashLocation::factory()->create([
            'subscription_status' => WashLocation::ACCOUNT_STATUS_EXPIRED,
            'account_status' => WashLocation::ACCOUNT_STATUS_EXPIRED,
            'trial_ends_at' => now()->subDays(2),
            'blocked_at' => now(),
            'public_visible' => false,
        ]);

        $this->actingAs($superAdmin)
            ->patch(route('super-admin.locations.extend-trial', ['washLocation' => $location->id]), ['days' => 15])
            ->assertRedirect();

        $location->refresh();

        $this->assertSame(WashLocation::ACCOUNT_STATUS_TRIAL, $location->subscription_status);
        $this->assertSame(WashLocation::ACCOUNT_STATUS_TRIAL, $location->account_status);
        $this->assertNull($location->blocked_at);
        $this->assertTrue($location->public_visible);
        $this->assertTrue($location->trial_ends_at->isFuture());
    }

    public function test_super_admin_ativa_assinatura(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'wash_location_id' => null,
            'is_active' => true,
        ]);

        $location = WashLocation::factory()->create([
            'subscription_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'account_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'subscription_ends_at' => null,
            'blocked_at' => null,
        ]);
        $plan = Plan::factory()->create(['name' => 'Professional']);

        $endsAt = now()->addDays(30)->format('Y-m-d');

        $this->actingAs($superAdmin)
            ->patch(route('super-admin.locations.activate-subscription', ['washLocation' => $location->id]), [
                'plan_id' => $plan->id,
                'subscription_ends_at' => $endsAt,
            ])
            ->assertRedirect();

        $location->refresh();

        $this->assertSame(WashLocation::ACCOUNT_STATUS_ACTIVE, $location->subscription_status);
        $this->assertSame(WashLocation::ACCOUNT_STATUS_ACTIVE, $location->account_status);
        $this->assertNull($location->blocked_at);
        $this->assertTrue($location->public_visible);
        $this->assertSame($endsAt, $location->subscription_ends_at->format('Y-m-d'));
        $this->assertDatabaseHas('subscriptions', [
            'wash_location_id' => $location->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
        ]);
    }

    public function test_super_admin_suspende_unidade(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'wash_location_id' => null,
            'is_active' => true,
        ]);

        $location = WashLocation::factory()->create([
            'subscription_status' => WashLocation::ACCOUNT_STATUS_ACTIVE,
            'account_status' => WashLocation::ACCOUNT_STATUS_ACTIVE,
            'subscription_ends_at' => now()->addMonth(),
            'blocked_at' => null,
            'public_visible' => true,
        ]);

        $this->actingAs($superAdmin)
            ->patch(route('super-admin.locations.suspend', ['washLocation' => $location->id]))
            ->assertRedirect();

        $location->refresh();

        $this->assertSame(WashLocation::ACCOUNT_STATUS_SUSPENDED, $location->subscription_status);
        $this->assertSame(WashLocation::ACCOUNT_STATUS_SUSPENDED, $location->account_status);
        $this->assertNotNull($location->blocked_at);
        $this->assertFalse($location->public_visible);
    }

    public function test_super_admin_reativa_unidade(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'wash_location_id' => null,
            'is_active' => true,
        ]);

        $location = WashLocation::factory()->create([
            'subscription_status' => WashLocation::ACCOUNT_STATUS_SUSPENDED,
            'account_status' => WashLocation::ACCOUNT_STATUS_SUSPENDED,
            'subscription_ends_at' => now()->subDay(),
            'blocked_at' => now(),
            'public_visible' => false,
        ]);

        $this->actingAs($superAdmin)
            ->patch(route('super-admin.locations.reactivate', ['washLocation' => $location->id]))
            ->assertRedirect();

        $location->refresh();

        $this->assertSame(WashLocation::ACCOUNT_STATUS_ACTIVE, $location->subscription_status);
        $this->assertSame(WashLocation::ACCOUNT_STATUS_ACTIVE, $location->account_status);
        $this->assertNull($location->blocked_at);
        $this->assertTrue($location->public_visible);
        $this->assertTrue($location->subscription_ends_at->isFuture());
    }

    public function test_admin_comum_nao_acessa_painel_comercial(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('super-admin.locations.index'))
            ->assertForbidden();
    }
}
