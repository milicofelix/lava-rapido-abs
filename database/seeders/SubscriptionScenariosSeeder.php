<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WashLocation;
use App\Support\DefaultServices;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscriptionScenariosSeeder extends Seeder
{
    private const NAME_PREFIX = 'Assinatura Demo';

    /**
     * @var array<int, array{key: string, name: string, account_status: string, trial_days: ?int, subscription_days: ?int, subscription_status: ?string, plan: string}>
     */
    private const SCENARIOS = [
        [
            'key' => 'trial-ativo',
            'name' => 'Trial Ativo',
            'account_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'trial_days' => 12,
            'subscription_days' => null,
            'subscription_status' => null,
            'plan' => 'Starter',
        ],
        [
            'key' => 'trial-vencendo',
            'name' => 'Trial Vencendo',
            'account_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'trial_days' => 2,
            'subscription_days' => null,
            'subscription_status' => null,
            'plan' => 'Starter',
        ],
        [
            'key' => 'trial-expirado',
            'name' => 'Trial Expirado',
            'account_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'trial_days' => -2,
            'subscription_days' => null,
            'subscription_status' => null,
            'plan' => 'Professional',
        ],
        [
            'key' => 'assinatura-ativa',
            'name' => 'Assinatura Ativa',
            'account_status' => WashLocation::ACCOUNT_STATUS_ACTIVE,
            'trial_days' => -20,
            'subscription_days' => 25,
            'subscription_status' => Subscription::STATUS_ACTIVE,
            'plan' => 'Professional',
        ],
        [
            'key' => 'assinatura-vencendo',
            'name' => 'Assinatura Vencendo',
            'account_status' => WashLocation::ACCOUNT_STATUS_ACTIVE,
            'trial_days' => -25,
            'subscription_days' => 3,
            'subscription_status' => Subscription::STATUS_ACTIVE,
            'plan' => 'Enterprise',
        ],
        [
            'key' => 'assinatura-expirada',
            'name' => 'Assinatura Expirada',
            'account_status' => WashLocation::ACCOUNT_STATUS_EXPIRED,
            'trial_days' => -40,
            'subscription_days' => -1,
            'subscription_status' => Subscription::STATUS_EXPIRED,
            'plan' => 'Professional',
        ],
        [
            'key' => 'pagamento-pendente',
            'name' => 'Pagamento Pendente',
            'account_status' => WashLocation::ACCOUNT_STATUS_TRIAL,
            'trial_days' => 1,
            'subscription_days' => null,
            'subscription_status' => Subscription::STATUS_PENDING,
            'plan' => 'Starter',
        ],
        [
            'key' => 'unidade-suspensa',
            'name' => 'Unidade Suspensa',
            'account_status' => WashLocation::ACCOUNT_STATUS_SUSPENDED,
            'trial_days' => -10,
            'subscription_days' => null,
            'subscription_status' => Subscription::STATUS_CANCELED,
            'plan' => 'Enterprise',
        ],
    ];

    public function run(): void
    {
        $this->call(PlanSeeder::class);

        DB::transaction(function (): void {
            foreach (self::SCENARIOS as $index => $scenario) {
                $plan = Plan::query()->where('name', $scenario['plan'])->firstOrFail();
                $location = $this->locationFor($scenario, $index);
                $this->ownerFor($location, $scenario);
                DefaultServices::seedForLocation($location);
                $this->subscriptionFor($location, $plan, $scenario);

                $this->command?->info("Cenario de assinatura criado: {$location->name}.");
            }
        });
    }

    /**
     * @param  array{key: string, name: string, account_status: string, trial_days: ?int, subscription_days: ?int, subscription_status: ?string, plan: string}  $scenario
     */
    private function locationFor(array $scenario, int $index): WashLocation
    {
        $subscriptionStatus = match ($scenario['account_status']) {
            WashLocation::ACCOUNT_STATUS_TRIAL => WashLocation::ACCOUNT_STATUS_TRIAL,
            WashLocation::ACCOUNT_STATUS_ACTIVE => WashLocation::ACCOUNT_STATUS_ACTIVE,
            WashLocation::ACCOUNT_STATUS_EXPIRED => WashLocation::ACCOUNT_STATUS_EXPIRED,
            WashLocation::ACCOUNT_STATUS_SUSPENDED => WashLocation::ACCOUNT_STATUS_SUSPENDED,
            default => $scenario['account_status'],
        };

        return WashLocation::query()->updateOrCreate(
            ['slug' => 'assinatura-demo-'.$scenario['key']],
            [
                'name' => self::NAME_PREFIX.' - '.$scenario['name'],
                'legal_name' => self::NAME_PREFIX.' '.$scenario['name'].' LTDA',
                'document' => '00.000.000/000'.$index.'-00',
                'address' => 'Rua das Assinaturas',
                'address_number' => (string) (100 + $index),
                'district' => 'Centro',
                'city' => 'Sao Paulo',
                'state' => 'SP',
                'status' => WashLocation::STATUS_OPEN,
                'account_status' => $scenario['account_status'],
                'subscription_status' => $subscriptionStatus,
                'public_visible' => true,
                'trial_started_at' => now()->subDays(15),
                'trial_ends_at' => $scenario['trial_days'] === null ? null : now()->addDays($scenario['trial_days']),
                'subscription_ends_at' => $scenario['subscription_days'] === null ? null : now()->addDays($scenario['subscription_days']),
                'blocked_at' => in_array($scenario['account_status'], [
                    WashLocation::ACCOUNT_STATUS_EXPIRED,
                    WashLocation::ACCOUNT_STATUS_SUSPENDED,
                ], true) ? now()->subDay() : null,
                'map_x' => 40 + $index,
                'map_y' => 45 + $index,
                'latitude' => -23.5505200 + ($index / 1000),
                'longitude' => -46.6333100 - ($index / 1000),
                'active_orders_count' => 0,
                'phone' => '(11) 97777-'.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                'business_hours' => WashLocation::defaultBusinessHours(),
            ],
        );
    }

    /**
     * @param  array{key: string, name: string}  $scenario
     */
    private function ownerFor(WashLocation $location, array $scenario): User
    {
        return User::query()->updateOrCreate(
            ['email' => 'assinatura.demo.'.$scenario['key'].'@autoflow.test'],
            [
                'name' => 'Dono '.$scenario['name'],
                'phone' => '(11) 96666-'.str_pad((string) $location->id, 4, '0', STR_PAD_LEFT),
                'role' => User::ROLE_OWNER,
                'wash_location_id' => $location->id,
                'is_active' => true,
                'password' => bcrypt('password'),
            ],
        );
    }

    /**
     * @param  array{key: string, account_status: string, subscription_days: ?int, subscription_status: ?string}  $scenario
     */
    private function subscriptionFor(WashLocation $location, Plan $plan, array $scenario): ?Subscription
    {
        if ($scenario['subscription_status'] === null) {
            return null;
        }

        $status = $scenario['subscription_status'];
        $startedAt = now()->subDays(20);
        $endsAt = $scenario['subscription_days'] === null ? null : now()->addDays($scenario['subscription_days']);

        return Subscription::query()->updateOrCreate(
            ['external_reference' => 'subscription-demo-'.$scenario['key']],
            [
                'wash_location_id' => $location->id,
                'plan_id' => $plan->id,
                'status' => $status,
                'started_at' => $status === Subscription::STATUS_PENDING ? null : $startedAt,
                'ends_at' => $endsAt,
                'payment_provider' => 'mercado_pago_sandbox',
                'provider_preference_id' => 'pref_demo_'.Str::slug($scenario['key'], '_'),
                'provider_payment_id' => in_array($status, [Subscription::STATUS_ACTIVE, Subscription::STATUS_EXPIRED], true)
                    ? 'pay_demo_'.Str::slug($scenario['key'], '_')
                    : null,
                'checkout_url' => $status === Subscription::STATUS_PENDING
                    ? 'https://sandbox.mercadopago.test/checkout/'.$scenario['key']
                    : null,
                'paid_at' => in_array($status, [Subscription::STATUS_ACTIVE, Subscription::STATUS_EXPIRED], true)
                    ? now()->subDays(20)
                    : null,
                'provider_payload' => [
                    'seeded_by' => self::class,
                    'scenario' => $scenario['key'],
                ],
            ],
        );
    }
}
