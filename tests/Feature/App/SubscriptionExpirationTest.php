<?php

namespace Tests\Feature\App;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\WashLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionExpirationTest extends TestCase
{
    use RefreshDatabase;

    public function test_comando_expira_trial_vencido_e_bloqueia_unidade(): void
    {
        $location = WashLocation::factory()->create([
            'account_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'trial_ends_at' => now()->subDay(),
            'subscription_ends_at' => null,
            'blocked_at' => null,
            'public_visible' => true,
        ]);

        $this->artisan('subscriptions:expire')
            ->expectsOutputToContain('Trials expirados: 1')
            ->assertExitCode(0);

        $location->refresh();

        $this->assertSame(WashLocation::ACCOUNT_STATUS_EXPIRED, $location->account_status);
        $this->assertSame(WashLocation::ACCOUNT_STATUS_EXPIRED, $location->subscription_status);
        $this->assertNotNull($location->blocked_at);
        $this->assertFalse($location->public_visible);
    }

    public function test_comando_expira_assinatura_vencida_e_bloqueia_unidade(): void
    {
        $location = WashLocation::factory()->create([
            'account_status' => WashLocation::ACCOUNT_STATUS_ACTIVE,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_ACTIVE,
            'trial_ends_at' => now()->subMonth(),
            'subscription_ends_at' => now()->subDay(),
            'blocked_at' => null,
            'public_visible' => true,
        ]);
        $plan = Plan::factory()->create();
        $subscription = Subscription::factory()->create([
            'wash_location_id' => $location->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'started_at' => now()->subMonth(),
            'ends_at' => now()->subDay(),
        ]);

        $this->artisan('subscriptions:expire')
            ->expectsOutputToContain('Assinaturas vencidas: 1')
            ->assertExitCode(0);

        $location->refresh();
        $subscription->refresh();

        $this->assertSame(WashLocation::ACCOUNT_STATUS_EXPIRED, $location->account_status);
        $this->assertSame(WashLocation::ACCOUNT_STATUS_EXPIRED, $location->subscription_status);
        $this->assertSame(Subscription::STATUS_EXPIRED, $subscription->status);
        $this->assertNotNull($location->blocked_at);
        $this->assertFalse($location->public_visible);
    }

    public function test_comando_nao_expira_trial_ou_assinatura_validos(): void
    {
        $trialLocation = WashLocation::factory()->create([
            'account_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'trial_ends_at' => now()->addDay(),
            'blocked_at' => null,
            'public_visible' => true,
        ]);
        $activeLocation = WashLocation::factory()->create([
            'account_status' => WashLocation::ACCOUNT_STATUS_ACTIVE,
            'subscription_status' => WashLocation::ACCOUNT_STATUS_ACTIVE,
            'subscription_ends_at' => now()->addDay(),
            'blocked_at' => null,
            'public_visible' => true,
        ]);
        $subscription = Subscription::factory()->create([
            'wash_location_id' => $activeLocation->id,
            'status' => Subscription::STATUS_ACTIVE,
            'ends_at' => now()->addDay(),
        ]);

        $this->artisan('subscriptions:expire')
            ->expectsOutputToContain('Assinaturas expiradas: 0 unidade(s), 0 assinatura(s)')
            ->assertExitCode(0);

        $this->assertSame(WashLocation::ACCOUNT_STATUS_TRIAL, $trialLocation->refresh()->subscription_status);
        $this->assertSame(WashLocation::ACCOUNT_STATUS_ACTIVE, $activeLocation->refresh()->subscription_status);
        $this->assertSame(Subscription::STATUS_ACTIVE, $subscription->refresh()->status);
        $this->assertNull($trialLocation->blocked_at);
        $this->assertNull($activeLocation->blocked_at);
    }
}
