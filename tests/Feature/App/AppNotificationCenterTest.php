<?php

namespace Tests\Feature\App;

use App\Models\AppSetting;
use App\Models\CashRegister;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WashOrder;
use App\Models\WashLocation;
use App\Models\WashLocationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AppNotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_sees_pending_location_request_notification(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'wash_location_id' => null,
        ]);
        WashLocationRequest::factory()->count(2)->create([
            'status' => WashLocationRequest::STATUS_PENDING_REVIEW,
        ]);
        WashLocationRequest::factory()->create([
            'status' => WashLocationRequest::STATUS_APPROVED,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.location-requests.index'))
            ->assertOk()
            ->assertSee('Notificacoes')
            ->assertSee('2 solicitações de lava-rápido')
            ->assertSee('Analisar solicitações')
            ->assertSee('status=pending_review', false);
    }

    public function test_owner_sees_trial_expiration_notification(): void
    {
        $location = WashLocation::factory()->create([
            'account_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'trial_ends_at' => now()->addDays(3),
            'subscription_ends_at' => null,
        ]);
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $location->id,
        ]);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Notificacoes')
            ->assertSee('Trial expira em 3 dias')
            ->assertSee('Escolher plano');
    }

    public function test_admin_sees_open_cash_register_notification_when_module_is_enabled(): void
    {
        $location = WashLocation::factory()->create([
            'account_status' => WashLocation::ACCOUNT_STATUS_ACTIVE,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_ACTIVE,
            'subscription_ends_at' => now()->addMonth(),
        ]);
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'wash_location_id' => $location->id,
        ]);
        AppSetting::setValue('module_cash_register', true);
        CashRegister::factory()->create([
            'wash_location_id' => $location->id,
            'opened_by_user_id' => $admin->id,
            'opened_at' => now()->setTime(8, 30),
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Caixa aberto')
            ->assertSee('Ver caixa');
    }

    public function test_owner_sees_in_progress_wash_notification_for_today(): void
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
        WashOrder::factory()->create([
            'wash_location_id' => $location->id,
            'entered_at' => now(),
            'status' => WashOrder::STATUS_WASHING,
        ]);
        WashOrder::factory()->create([
            'wash_location_id' => $location->id,
            'entered_at' => now()->subDay(),
            'status' => WashOrder::STATUS_WASHING,
        ]);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('1 lavagem em andamento')
            ->assertSee('Abrir Kanban');
    }

    public function test_owner_sees_delayed_wash_notification_for_today(): void
    {
        Carbon::setTestNow('2026-06-25 14:00:00');

        try {
            $location = WashLocation::factory()->create([
                'account_status' => WashLocation::ACCOUNT_STATUS_ACTIVE,
                'subscription_status' => WashLocation::ACCOUNT_STATUS_ACTIVE,
                'subscription_ends_at' => now()->addMonth(),
            ]);
            $owner = User::factory()->create([
                'role' => User::ROLE_OWNER,
                'wash_location_id' => $location->id,
            ]);
            WashOrder::factory()->create([
                'wash_location_id' => $location->id,
                'entered_at' => now()->setTime(10, 0),
                'estimated_completion_at' => now()->setTime(11, 0),
                'status' => WashOrder::STATUS_WASHING,
            ]);
            WashOrder::factory()->create([
                'wash_location_id' => $location->id,
                'entered_at' => now()->subDay()->setTime(10, 0),
                'estimated_completion_at' => now()->subDay()->setTime(11, 0),
                'status' => WashOrder::STATUS_WASHING,
            ]);

            $this->actingAs($owner)
                ->get(route('dashboard'))
                ->assertOk()
                ->assertSee('1 lavagem atrasada')
                ->assertSee('Abrir Agenda')
                ->assertSee('agenda', false);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_attendant_does_not_see_subscription_notification(): void
    {
        $location = WashLocation::factory()->create([
            'account_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'trial_ends_at' => now()->addDays(2),
            'subscription_ends_at' => null,
        ]);
        $attendant = User::factory()->create([
            'role' => User::ROLE_ATTENDANT,
            'wash_location_id' => $location->id,
        ]);

        $this->actingAs($attendant)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Trial expira em 2 dias')
            ->assertDontSee('Trial em andamento');
    }

    public function test_owner_sees_expired_subscription_notification_on_subscription_page(): void
    {
        $location = WashLocation::factory()->create([
            'account_status' => WashLocation::ACCOUNT_STATUS_EXPIRED,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_EXPIRED,
            'trial_ends_at' => now()->subDay(),
            'subscription_ends_at' => null,
        ]);
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $location->id,
        ]);

        $this->actingAs($owner)
            ->get(route('subscriptions.show'))
            ->assertOk()
            ->assertSee('Assinatura expirada')
            ->assertSee('Ver assinatura');
    }

    public function test_owner_sees_subscription_expiring_notification(): void
    {
        $location = WashLocation::factory()->create([
            'account_status' => WashLocation::ACCOUNT_STATUS_ACTIVE,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_ACTIVE,
            'subscription_ends_at' => now()->addDays(4),
        ]);
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $location->id,
        ]);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Assinatura vence em 4 dias')
            ->assertSee('Renovar');
    }

    public function test_owner_sees_pending_payment_notification(): void
    {
        $location = WashLocation::factory()->create([
            'account_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'trial_ends_at' => now()->addDays(10),
        ]);
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $location->id,
        ]);
        $plan = Plan::factory()->create(['name' => 'Starter']);
        Subscription::factory()->create([
            'wash_location_id' => $location->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_PENDING,
            'checkout_url' => 'https://www.mercadopago.com.br/checkout/test',
        ]);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Pagamento pendente')
            ->assertSee('Finalize o pagamento do plano Starter')
            ->assertSee('Continuar pagamento');
    }

    public function test_owner_sees_recent_approved_payment_notification(): void
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
        $plan = Plan::factory()->create(['name' => 'Professional']);
        Subscription::factory()->create([
            'wash_location_id' => $location->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'started_at' => now(),
            'ends_at' => now()->addMonth(),
            'paid_at' => now(),
        ]);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Pagamento aprovado')
            ->assertSee('Assinatura Professional ativa até')
            ->assertSee('Ver assinatura');
    }

    public function test_owner_sees_recent_rejected_payment_notification(): void
    {
        $location = WashLocation::factory()->create([
            'account_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'trial_ends_at' => now()->addDays(10),
        ]);
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $location->id,
        ]);
        $plan = Plan::factory()->create(['name' => 'Enterprise']);
        Subscription::factory()->create([
            'wash_location_id' => $location->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_CANCELED,
            'provider_payment_id' => '123456',
            'updated_at' => now(),
        ]);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Pagamento não aprovado')
            ->assertSee('Tentar novamente');
    }
}
