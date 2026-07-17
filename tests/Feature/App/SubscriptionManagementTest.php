<?php

namespace Tests\Feature\App;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WashLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_visualiza_plano_atual(): void
    {
        $location = WashLocation::factory()->create([
            'account_status' => WashLocation::ACCOUNT_STATUS_ACTIVE,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_ACTIVE,
            'subscription_ends_at' => now()->addMonth(),
        ]);
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $location->id,
        ]);
        $plan = Plan::factory()->create(['name' => 'Professional', 'price' => 89.90]);

        Subscription::factory()->create([
            'wash_location_id' => $location->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'started_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->actingAs($owner)
            ->get(route('subscriptions.show'))
            ->assertOk()
            ->assertSee('Assinatura')
            ->assertSee('Professional')
            ->assertSee('R$ 89,90')
            ->assertSee('Próxima cobrança')
            ->assertSee('Plano atual')
            ->assertSee('Assinatura ativa')
            ->assertSee('Este é o plano ativo da unidade.')
            ->assertSee('data-onboarding-tour', false)
            ->assertSee('subscriptions.show.v1')
            ->assertSee('data-tour="subscription-summary"', false)
            ->assertSee('data-tour="subscription-choice"', false)
            ->assertSee('data-tour="subscription-plans"', false)
            ->assertSee('data-tour="subscription-plan-card"', false)
            ->assertSee('data-tour="subscription-plan-action"', false)
            ->assertSee('data-tour="subscription-history"', false);
    }

    public function test_owner_ve_plano_atual_no_card_do_plano_contratado(): void
    {
        $location = WashLocation::factory()->create([
            'account_status' => WashLocation::ACCOUNT_STATUS_ACTIVE,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_ACTIVE,
            'subscription_ends_at' => now()->addMonth(),
        ]);
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $location->id,
        ]);
        $starter = Plan::factory()->create(['name' => 'Starter', 'price' => 49.90]);
        $professional = Plan::factory()->create(['name' => 'Professional', 'price' => 89.90]);

        Subscription::factory()->create([
            'wash_location_id' => $location->id,
            'plan_id' => $professional->id,
            'status' => Subscription::STATUS_ACTIVE,
            'started_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        $response = $this->actingAs($owner)
            ->get(route('subscriptions.show'))
            ->assertOk()
            ->assertSee('Assinatura ativa')
            ->assertSee('Este é o plano ativo da unidade.');

        $content = $response->getContent();

        $this->assertMatchesRegularExpression('/<h2[^>]*>Starter<\/h2>.*Disponível/s', $content);
        $this->assertMatchesRegularExpression('/<h2[^>]*>Professional<\/h2>.*Plano atual/s', $content);
    }

    public function test_owner_visualiza_historico_de_assinaturas(): void
    {
        $location = WashLocation::factory()->create([
            'account_status' => WashLocation::ACCOUNT_STATUS_ACTIVE,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_ACTIVE,
            'subscription_ends_at' => now()->addMonth(),
        ]);
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $location->id,
        ]);
        $plan = Plan::factory()->create(['name' => 'Starter']);

        Subscription::factory()->create([
            'wash_location_id' => $location->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'started_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'payment_provider' => 'mercado_pago',
            'provider_preference_id' => 'pref_123',
            'provider_payment_id' => 'pay_456',
            'paid_at' => now(),
        ]);

        $this->actingAs($owner)
            ->get(route('subscriptions.show'))
            ->assertOk()
            ->assertSee('Histórico de assinatura')
            ->assertSee('Últimas escolhas de plano, pagamentos e renovações da unidade.')
            ->assertSee('Starter')
            ->assertSee('Mercado Pago')
            ->assertSee('pay_456')
            ->assertSee('pref_123');
    }

    public function test_owner_nao_acessa_plano_inativo(): void
    {
        $location = WashLocation::factory()->create([
            'account_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'trial_ends_at' => now()->addDays(3),
        ]);
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $location->id,
        ]);
        $plan = Plan::factory()->create(['is_active' => false]);

        $this->actingAs($owner)
            ->from(route('subscriptions.show'))
            ->post(route('subscriptions.choose'), ['plan_id' => $plan->id])
            ->assertRedirect(route('subscriptions.show'))
            ->assertSessionHasErrors('plan_id');

        $this->assertDatabaseMissing('subscriptions', [
            'wash_location_id' => $location->id,
            'plan_id' => $plan->id,
        ]);
    }

    public function test_owner_acessa_assinatura_mesmo_com_trial_expirado(): void
    {
        $location = WashLocation::factory()->create([
            'account_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'trial_ends_at' => now()->subDay(),
            'blocked_at' => now(),
        ]);
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $location->id,
        ]);
        Plan::factory()->create(['name' => 'Starter']);

        $this->actingAs($owner)
            ->get(route('subscriptions.show'))
            ->assertOk()
            ->assertSee('Escolha de plano')
            ->assertSee('Starter')
            ->assertSee('Disponível')
            ->assertDontSee('>Ativo<', false);
    }

    public function test_owner_ve_aviso_de_trial_expirado_na_tela_de_assinatura(): void
    {
        $location = WashLocation::factory()->create([
            'account_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'trial_ends_at' => now()->subDay(),
            'blocked_at' => now(),
        ]);
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $location->id,
        ]);
        Plan::factory()->create(['name' => 'Starter']);

        $this->actingAs($owner)
            ->get(route('subscriptions.show'))
            ->assertOk()
            ->assertSee('Trial expirado')
            ->assertSee('escolha um plano para reativar a unidade')
            ->assertDontSee('Trial em andamento');
    }

    public function test_super_admin_ativa_assinatura(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'wash_location_id' => null,
        ]);
        $location = WashLocation::factory()->create([
            'account_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'trial_ends_at' => now()->subDay(),
            'blocked_at' => now(),
        ]);
        $plan = Plan::factory()->create(['name' => 'Enterprise']);
        $endsAt = now()->addMonth()->format('Y-m-d');

        $this->actingAs($superAdmin)
            ->patch(route('super-admin.locations.activate-subscription', ['washLocation' => $location->id]), [
                'plan_id' => $plan->id,
                'subscription_ends_at' => $endsAt,
            ])
            ->assertRedirect();

        $location->refresh();

        $this->assertSame(WashLocation::ACCOUNT_STATUS_ACTIVE, $location->subscription_status);
        $this->assertNull($location->blocked_at);
        $this->assertDatabaseHas('subscriptions', [
            'wash_location_id' => $location->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
        ]);
    }
}
