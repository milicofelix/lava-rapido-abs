<?php

namespace Tests\Feature\App;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WashLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MercadoPagoSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_escolhe_plano_e_e_redirecionado_para_checkout_do_mercado_pago(): void
    {
        config([
            'services.mercado_pago.access_token' => 'test-token',
            'services.mercado_pago.notification_url' => null,
            'services.mercado_pago.success_url' => null,
            'services.mercado_pago.failure_url' => null,
            'services.mercado_pago.pending_url' => null,
        ]);

        Http::fake([
            'https://api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => 'pref_123',
                'init_point' => 'https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=pref_123',
            ], 201),
        ]);

        $location = WashLocation::factory()->create([
            'account_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'trial_ends_at' => now()->addDays(3),
        ]);
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $location->id,
        ]);
        $plan = Plan::factory()->create(['name' => 'Professional', 'price' => 89.90]);

        $this->actingAs($owner)
            ->post(route('subscriptions.choose'), ['plan_id' => $plan->id])
            ->assertRedirect('https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=pref_123');

        $subscription = Subscription::query()->firstOrFail();

        $this->assertSame(Subscription::STATUS_PENDING, $subscription->status);
        $this->assertSame('mercado_pago', $subscription->payment_provider);
        $this->assertSame('pref_123', $subscription->provider_preference_id);
        $this->assertNotNull($subscription->external_reference);
        $this->assertSame('https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=pref_123', $subscription->checkout_url);

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer test-token')
            && $request['external_reference'] === $subscription->external_reference
            && $request['items'][0]['unit_price'] === 89.9
            && $request['notification_url'] === route('webhooks.mercado-pago')
            && ! array_key_exists('auto_return', $request->data()));
    }

    public function test_checkout_usa_urls_publicas_configuradas_para_sandbox_real(): void
    {
        config([
            'services.mercado_pago.access_token' => 'TEST-token',
            'services.mercado_pago.notification_url' => 'https://sandbox.test/webhooks/mercado-pago',
            'services.mercado_pago.success_url' => 'https://sandbox.test/configuracoes/assinatura?status=approved',
            'services.mercado_pago.failure_url' => 'https://sandbox.test/configuracoes/assinatura?status=failure',
            'services.mercado_pago.pending_url' => 'https://sandbox.test/configuracoes/assinatura?status=pending',
        ]);

        Http::fake([
            'https://api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => 'pref_sandbox',
                'init_point' => 'https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=pref_sandbox',
            ], 201),
        ]);

        $location = WashLocation::factory()->create([
            'account_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'trial_ends_at' => now()->addDays(3),
        ]);
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $location->id,
        ]);
        $plan = Plan::factory()->create(['price' => 49.90]);

        $this->actingAs($owner)
            ->post(route('subscriptions.choose'), ['plan_id' => $plan->id])
            ->assertRedirect('https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=pref_sandbox');

        Http::assertSent(fn ($request) => $request['notification_url'] === 'https://sandbox.test/webhooks/mercado-pago'
            && $request['back_urls']['success'] === 'https://sandbox.test/configuracoes/assinatura?status=approved'
            && $request['back_urls']['failure'] === 'https://sandbox.test/configuracoes/assinatura?status=failure'
            && $request['back_urls']['pending'] === 'https://sandbox.test/configuracoes/assinatura?status=pending'
            && $request['auto_return'] === 'approved');
    }

    public function test_checkout_sandbox_envia_email_do_comprador_de_teste_quando_configurado(): void
    {
        config([
            'services.mercado_pago.access_token' => 'APP_USR-test-credential',
            'services.mercado_pago.environment' => 'sandbox',
            'services.mercado_pago.sandbox_payer_email' => 'comprador-teste@example.com',
        ]);

        Http::fake([
            'https://api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => 'pref_buyer',
                'init_point' => 'https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=pref_buyer',
            ], 201),
        ]);

        $location = WashLocation::factory()->create([
            'account_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'trial_ends_at' => now()->addDays(3),
        ]);
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $location->id,
        ]);
        $plan = Plan::factory()->create(['price' => 49.90]);

        $this->actingAs($owner)
            ->post(route('subscriptions.choose'), ['plan_id' => $plan->id])
            ->assertRedirect('https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=pref_buyer');

        Http::assertSent(fn ($request) => $request['payer']['email'] === 'comprador-teste@example.com');
    }

    public function test_falha_do_checkout_volta_para_assinatura_sem_erro_500(): void
    {
        config(['services.mercado_pago.access_token' => 'test-token']);

        Http::fake([
            'https://api.mercadopago.com/checkout/preferences' => Http::response([
                'message' => 'auto_return invalid. back_url.success must be defined',
                'error' => 'invalid_auto_return',
            ], 400),
        ]);

        $location = WashLocation::factory()->create([
            'account_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'trial_ends_at' => now()->addDays(3),
        ]);
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $location->id,
        ]);
        $plan = Plan::factory()->create();

        $this->actingAs($owner)
            ->from(route('subscriptions.show'))
            ->post(route('subscriptions.choose'), ['plan_id' => $plan->id])
            ->assertRedirect(route('subscriptions.show'))
            ->assertSessionHas('status', 'Não foi possível iniciar o pagamento agora. Tente novamente em instantes.');

        $this->assertDatabaseHas('subscriptions', [
            'wash_location_id' => $location->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_CANCELED,
        ]);
    }

    public function test_checkout_real_fica_bloqueado_sem_flag_explicita(): void
    {
        config([
            'services.mercado_pago.access_token' => 'APP_USR-production-token',
            'services.mercado_pago.environment' => 'production',
            'services.mercado_pago.live_enabled' => false,
        ]);

        Http::fake();

        $location = WashLocation::factory()->create([
            'account_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'trial_ends_at' => now()->addDays(3),
        ]);
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $location->id,
        ]);
        $plan = Plan::factory()->create(['name' => 'Enterprise']);

        $this->actingAs($owner)
            ->from(route('subscriptions.show'))
            ->post(route('subscriptions.choose'), ['plan_id' => $plan->id])
            ->assertRedirect(route('subscriptions.show'))
            ->assertSessionHas('status', 'Não foi possível iniciar o pagamento agora. Tente novamente em instantes.');

        Http::assertNothingSent();

        $this->assertDatabaseHas('subscriptions', [
            'wash_location_id' => $location->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_CANCELED,
        ]);
    }

    public function test_tela_informa_quando_token_de_producao_esta_bloqueado(): void
    {
        config([
            'services.mercado_pago.access_token' => 'APP_USR-production-token',
            'services.mercado_pago.environment' => 'production',
            'services.mercado_pago.live_enabled' => false,
        ]);

        $location = WashLocation::factory()->create([
            'account_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'trial_ends_at' => now()->addDays(3),
        ]);
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $location->id,
        ]);
        Plan::factory()->create(['name' => 'Enterprise']);

        $this->actingAs($owner)
            ->get(route('subscriptions.show'))
            ->assertOk()
            ->assertSee('Escolha o plano ideal para sua unidade')
            ->assertSee('Pagar via Pix')
            ->assertDontSee('Token de produção detectado, mas cobrança real bloqueada')
            ->assertDontSee('Checkout bloqueado até habilitar MERCADO_PAGO_LIVE_ENABLED=true.')
            ->assertDontSee('Pagar com Mercado Pago');
    }

    public function test_credencial_app_usr_em_ambiente_sandbox_pode_abrir_checkout(): void
    {
        config([
            'services.mercado_pago.access_token' => 'APP_USR-test-credential-from-mercado-pago-panel',
            'services.mercado_pago.environment' => 'sandbox',
            'services.mercado_pago.live_enabled' => false,
        ]);

        Http::fake([
            'https://api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => 'pref_app_usr_test',
                'init_point' => 'https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=pref_app_usr_test',
            ], 201),
        ]);

        $location = WashLocation::factory()->create([
            'account_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'trial_ends_at' => now()->addDays(3),
        ]);
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $location->id,
        ]);
        $plan = Plan::factory()->create(['name' => 'Starter']);

        $this->actingAs($owner)
            ->post(route('subscriptions.choose'), ['plan_id' => $plan->id])
            ->assertRedirect('https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=pref_app_usr_test');

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer APP_USR-test-credential-from-mercado-pago-panel'));
    }

    public function test_owner_visualiza_retorno_aprovado_do_mercado_pago(): void
    {
        config(['services.mercado_pago.access_token' => 'test-token']);

        $location = WashLocation::factory()->create([
            'account_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'trial_ends_at' => now()->addDays(3),
        ]);
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $location->id,
        ]);
        Plan::factory()->create(['name' => 'Professional']);

        $this->actingAs($owner)
            ->get(route('subscriptions.show', ['collection_status' => 'approved']))
            ->assertOk()
            ->assertSee('Pagamento aprovado')
            ->assertSee('Sua assinatura será atualizada após a confirmação.');
    }

    public function test_owner_cancela_assinatura_pendente(): void
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
        $plan = Plan::factory()->create(['name' => 'Starter']);
        $subscription = Subscription::factory()->create([
            'wash_location_id' => $location->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_PENDING,
            'checkout_url' => 'https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=pref_123',
        ]);

        $this->actingAs($owner)
            ->from(route('subscriptions.show'))
            ->patch(route('subscriptions.cancel-pending'))
            ->assertRedirect(route('subscriptions.show'))
            ->assertSessionHas('status', 'Escolha de plano cancelada. Você pode selecionar outro plano quando quiser.');

        $this->assertSame(Subscription::STATUS_CANCELED, $subscription->refresh()->status);
    }

    public function test_webhook_aprovado_ativa_assinatura_e_libera_unidade(): void
    {
        config(['services.mercado_pago.access_token' => 'test-token']);

        $location = WashLocation::factory()->create([
            'account_status' => WashLocation::ACCOUNT_STATUS_EXPIRED,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_EXPIRED,
            'subscription_ends_at' => null,
            'blocked_at' => now(),
            'public_visible' => false,
        ]);
        $plan = Plan::factory()->create(['name' => 'Starter']);
        $subscription = Subscription::factory()->create([
            'wash_location_id' => $location->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_PENDING,
            'payment_provider' => 'mercado_pago',
            'external_reference' => 'subscription-test-ref',
        ]);

        Http::fake([
            'https://api.mercadopago.com/v1/payments/987654' => Http::response([
                'id' => 987654,
                'status' => 'approved',
                'external_reference' => 'subscription-test-ref',
                'date_approved' => now()->toIso8601String(),
            ]),
        ]);

        $this->postJson(route('webhooks.mercado-pago'), [
            'type' => 'payment',
            'data' => ['id' => '987654'],
        ])->assertOk()
            ->assertJson(['status' => 'activated']);

        $subscription->refresh();
        $location->refresh();

        $this->assertSame(Subscription::STATUS_ACTIVE, $subscription->status);
        $this->assertSame('987654', $subscription->provider_payment_id);
        $this->assertNotNull($subscription->paid_at);
        $this->assertSame(WashLocation::ACCOUNT_STATUS_ACTIVE, $location->account_status);
        $this->assertSame(WashLocation::ACCOUNT_STATUS_ACTIVE, $location->subscription_status);
        $this->assertNull($location->blocked_at);
        $this->assertTrue($location->public_visible);
        $this->assertNotNull($location->subscription_ends_at);
    }

    public function test_webhook_aprovado_renova_a_partir_do_vencimento_atual_quando_assinatura_ainda_esta_ativa(): void
    {
        config(['services.mercado_pago.access_token' => 'test-token']);

        $currentEndsAt = now()->addDays(10)->startOfDay();
        $location = WashLocation::factory()->create([
            'account_status' => WashLocation::ACCOUNT_STATUS_ACTIVE,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_ACTIVE,
            'subscription_ends_at' => $currentEndsAt,
            'blocked_at' => null,
            'public_visible' => true,
        ]);
        $plan = Plan::factory()->create(['name' => 'Professional']);
        Subscription::factory()->create([
            'wash_location_id' => $location->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'started_at' => now()->subMonth(),
            'ends_at' => $currentEndsAt,
        ]);
        $renewal = Subscription::factory()->create([
            'wash_location_id' => $location->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_PENDING,
            'payment_provider' => 'mercado_pago',
            'external_reference' => 'subscription-renewal-ref',
        ]);

        Http::fake([
            'https://api.mercadopago.com/v1/payments/555' => Http::response([
                'id' => 555,
                'status' => 'approved',
                'external_reference' => 'subscription-renewal-ref',
                'date_approved' => now()->toIso8601String(),
            ]),
        ]);

        $this->postJson(route('webhooks.mercado-pago'), [
            'type' => 'payment',
            'data' => ['id' => '555'],
        ])->assertOk()
            ->assertJson(['status' => 'activated']);

        $renewal->refresh();
        $location->refresh();

        $this->assertSame(Subscription::STATUS_ACTIVE, $renewal->status);
        $this->assertSame($currentEndsAt->copy()->addMonth()->toDateString(), $renewal->ends_at->toDateString());
        $this->assertSame($currentEndsAt->copy()->addMonth()->toDateString(), $location->subscription_ends_at->toDateString());
    }

    public function test_webhook_aprovado_duplicado_nao_renova_duas_vezes(): void
    {
        config(['services.mercado_pago.access_token' => 'test-token']);

        $currentEndsAt = now()->addDays(10)->startOfDay();
        $location = WashLocation::factory()->create([
            'account_status' => WashLocation::ACCOUNT_STATUS_ACTIVE,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_ACTIVE,
            'subscription_ends_at' => $currentEndsAt,
            'blocked_at' => null,
            'public_visible' => true,
        ]);
        $plan = Plan::factory()->create(['name' => 'Professional']);
        $renewal = Subscription::factory()->create([
            'wash_location_id' => $location->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_PENDING,
            'payment_provider' => 'mercado_pago',
            'external_reference' => 'subscription-duplicate-ref',
        ]);

        Http::fake([
            'https://api.mercadopago.com/v1/payments/777' => Http::response([
                'id' => 777,
                'status' => 'approved',
                'external_reference' => 'subscription-duplicate-ref',
                'date_approved' => now()->toIso8601String(),
            ]),
        ]);

        $payload = [
            'type' => 'payment',
            'data' => ['id' => '777'],
        ];

        $this->postJson(route('webhooks.mercado-pago'), $payload)
            ->assertOk()
            ->assertJson(['status' => 'activated']);

        $expectedEndDate = $currentEndsAt->copy()->addMonth()->toDateString();

        $this->postJson(route('webhooks.mercado-pago'), $payload)
            ->assertOk()
            ->assertJson(['status' => 'already_processed']);

        $renewal->refresh();
        $location->refresh();

        $this->assertSame(Subscription::STATUS_ACTIVE, $renewal->status);
        $this->assertSame('777', $renewal->provider_payment_id);
        $this->assertSame($expectedEndDate, $renewal->ends_at->toDateString());
        $this->assertSame($expectedEndDate, $location->subscription_ends_at->toDateString());
    }

    public function test_webhook_de_pagamento_rejeitado_cancela_assinatura_pendente(): void
    {
        config(['services.mercado_pago.access_token' => 'test-token']);

        $subscription = Subscription::factory()->create([
            'status' => Subscription::STATUS_PENDING,
            'payment_provider' => 'mercado_pago',
            'external_reference' => 'subscription-rejected-ref',
        ]);

        Http::fake([
            'https://api.mercadopago.com/v1/payments/123' => Http::response([
                'id' => 123,
                'status' => 'rejected',
                'external_reference' => 'subscription-rejected-ref',
            ]),
        ]);

        $this->postJson(route('webhooks.mercado-pago'), [
            'type' => 'payment',
            'data' => ['id' => '123'],
        ])->assertOk()
            ->assertJson(['status' => 'received']);

        $this->assertSame(Subscription::STATUS_CANCELED, $subscription->refresh()->status);
    }
}
