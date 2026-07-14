<?php

namespace Tests\Feature\Database;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WashLocation;
use Database\Seeders\SubscriptionScenariosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionScenariosSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_scenarios_seeder_creates_demo_locations_and_is_idempotent(): void
    {
        $this->seed(SubscriptionScenariosSeeder::class);

        $this->assertSame(3, Plan::query()->count());
        $this->assertSame(8, WashLocation::query()
            ->where('slug', 'like', 'assinatura-demo-%')
            ->count());
        $this->assertSame(8, User::query()
            ->where('email', 'like', 'assinatura.demo.%@autoflow.test')
            ->count());
        $this->assertSame(5, Subscription::query()
            ->where('external_reference', 'like', 'subscription-demo-%')
            ->count());

        $this->assertDatabaseHas('wash_locations', [
            'slug' => 'assinatura-demo-trial-ativo',
            'subscription_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
        ]);
        $this->assertDatabaseHas('wash_locations', [
            'slug' => 'assinatura-demo-trial-expirado',
            'subscription_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
        ]);
        $this->assertDatabaseHas('wash_locations', [
            'slug' => 'assinatura-demo-assinatura-ativa',
            'subscription_status' => WashLocation::ACCOUNT_STATUS_ACTIVE,
        ]);
        $this->assertDatabaseHas('wash_locations', [
            'slug' => 'assinatura-demo-assinatura-expirada',
            'subscription_status' => WashLocation::ACCOUNT_STATUS_EXPIRED,
        ]);
        $this->assertDatabaseHas('wash_locations', [
            'slug' => 'assinatura-demo-unidade-suspensa',
            'subscription_status' => WashLocation::ACCOUNT_STATUS_SUSPENDED,
        ]);
        $this->assertDatabaseHas('subscriptions', [
            'external_reference' => 'subscription-demo-pagamento-pendente',
            'status' => Subscription::STATUS_PENDING,
            'checkout_url' => 'https://sandbox.mercadopago.test/checkout/pagamento-pendente',
        ]);
        $this->assertDatabaseHas('subscriptions', [
            'external_reference' => 'subscription-demo-assinatura-ativa',
            'status' => Subscription::STATUS_ACTIVE,
        ]);
        $this->assertDatabaseHas('subscriptions', [
            'external_reference' => 'subscription-demo-assinatura-expirada',
            'status' => Subscription::STATUS_EXPIRED,
        ]);

        $locationCount = WashLocation::query()->count();
        $subscriptionCount = Subscription::query()->count();
        $userCount = User::query()->count();

        $this->seed(SubscriptionScenariosSeeder::class);

        $this->assertSame($locationCount, WashLocation::query()->count());
        $this->assertSame($subscriptionCount, Subscription::query()->count());
        $this->assertSame($userCount, User::query()->count());
    }
}
