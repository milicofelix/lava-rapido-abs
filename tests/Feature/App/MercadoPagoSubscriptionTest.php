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
        config(['services.mercado_pago.access_token' => 'test-token']);

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
            ->assertSessionHas('status', 'Nao foi possivel abrir o checkout do Mercado Pago agora. Verifique as credenciais e tente novamente.');

        $this->assertDatabaseHas('subscriptions', [
            'wash_location_id' => $location->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_CANCELED,
        ]);
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
            ->assertSee('webhook confirmar o pagamento');
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
            ->assertSessionHas('status', 'Escolha de plano cancelada. Voce pode selecionar outro plano quando quiser.');

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
