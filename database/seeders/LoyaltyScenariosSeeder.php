<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\LoyaltyCoupon;
use App\Models\LoyaltyProgram;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WashLocation;
use App\Models\WashOrder;
use App\Support\DefaultServices;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LoyaltyScenariosSeeder extends Seeder
{
    private const CODE_PREFIX = 'FIDELIDADE-DEMO';

    public function run(): void
    {
        $locations = WashLocation::query()->orderBy('id')->get();

        if ($locations->isEmpty()) {
            $this->command?->warn('Nenhum lava-rapido encontrado. Cadastre/aprove uma unidade antes de rodar este seeder.');

            return;
        }

        DB::transaction(function () use ($locations): void {
            $locations->each(function (WashLocation $location): void {
                if (LoyaltyCoupon::query()
                    ->where('wash_location_id', $location->id)
                    ->where('code', 'like', self::CODE_PREFIX.'-'.$location->id.'-%')
                    ->exists()
                ) {
                    $this->command?->info("Cenarios de fidelidade da unidade {$location->name} ja existem. Pulando.");

                    return;
                }

                DefaultServices::seedForLocation($location);

                $service = $this->serviceFor($location);
                $program = $this->programFor($location, $service);
                $user = $this->userFor($location);
                $customers = $this->customersFor($location);

                $this->createNearRewardScenario($location, $customers['near_reward'], $service, $user);
                $activeSource = $this->createCompletedCycle($location, $customers['active_coupon'], $service, $user, 'ATIVO', now()->subDays(8));
                $usedSource = $this->createCompletedCycle($location, $customers['used_coupon'], $service, $user, 'USADO', now()->subDays(20));
                $expiringSource = $this->createCompletedCycle($location, $customers['expiring_coupon'], $service, $user, 'VENCENDO', now()->subDays(15));
                $expiredSource = $this->createCompletedCycle($location, $customers['expired_coupon'], $service, $user, 'VENCIDO', now()->subDays(35));
                $canceledSource = $this->createCompletedCycle($location, $customers['canceled_coupon'], $service, $user, 'CANCELADO', now()->subDays(12));

                $this->couponFor($location, $program, $customers['active_coupon'], $activeSource, $service, 'ATIVO', [
                    'status' => LoyaltyCoupon::STATUS_ACTIVE,
                    'earned_at' => now()->subDay(),
                    'expires_at' => now()->addDays(28),
                ]);

                $usedCoupon = $this->couponFor($location, $program, $customers['used_coupon'], $usedSource, $service, 'USADO', [
                    'status' => LoyaltyCoupon::STATUS_USED,
                    'earned_at' => now()->subDays(10),
                    'expires_at' => now()->addDays(20),
                    'used_at' => now()->subDays(2),
                    'used_by_user_id' => $user->id,
                ]);
                $this->createCouponUsageOrder($location, $customers['used_coupon'], $service, $user, $usedCoupon);

                $this->couponFor($location, $program, $customers['expiring_coupon'], $expiringSource, $service, 'VENCENDO', [
                    'status' => LoyaltyCoupon::STATUS_ACTIVE,
                    'earned_at' => now()->subDays(25),
                    'expires_at' => now()->addDays(2),
                ]);

                $this->couponFor($location, $program, $customers['expired_coupon'], $expiredSource, $service, 'VENCIDO', [
                    'status' => LoyaltyCoupon::STATUS_EXPIRED,
                    'earned_at' => now()->subDays(40),
                    'expires_at' => now()->subDay(),
                ]);

                $this->couponFor($location, $program, $customers['canceled_coupon'], $canceledSource, $service, 'CANCELADO', [
                    'status' => LoyaltyCoupon::STATUS_CANCELED,
                    'earned_at' => now()->subDays(6),
                    'expires_at' => now()->addDays(24),
                ]);

                $this->command?->info("Cenarios de fidelidade criados para {$location->name}.");
            });
        });
    }

    private function serviceFor(WashLocation $location): Service
    {
        return Service::query()
            ->where('wash_location_id', $location->id)
            ->where('name', 'Ducha simples')
            ->first()
            ?? Service::query()
                ->where('wash_location_id', $location->id)
                ->where('active', true)
                ->orderBy('base_price')
                ->firstOrFail();
    }

    private function programFor(WashLocation $location, Service $service): LoyaltyProgram
    {
        return LoyaltyProgram::query()->updateOrCreate(
            ['wash_location_id' => $location->id],
            [
                'is_active' => true,
                'threshold' => 3,
                'count_scope' => LoyaltyProgram::COUNT_ANY,
                'qualifying_service_id' => null,
                'qualifying_category' => null,
                'reward_type' => LoyaltyProgram::REWARD_FIXED_SERVICE,
                'reward_service_id' => $service->id,
                'discount_value' => null,
                'coupon_valid_days' => 30,
            ],
        );
    }

    private function userFor(WashLocation $location): User
    {
        return User::query()
            ->where('wash_location_id', $location->id)
            ->whereIn('role', [User::ROLE_OWNER, User::ROLE_ADMIN, User::ROLE_ATTENDANT])
            ->first()
            ?? User::query()->create([
                'wash_location_id' => $location->id,
                'name' => 'Atendente Fidelidade Demo',
                'email' => 'fidelidade.demo.'.$location->id.'@autoflow.test',
                'role' => User::ROLE_ATTENDANT,
                'is_active' => true,
                'password' => bcrypt('password'),
            ]);
    }

    /**
     * @return array<string, Customer>
     */
    private function customersFor(WashLocation $location): array
    {
        return collect([
            'near_reward' => 'Cliente Quase Cupom',
            'active_coupon' => 'Cliente Cupom Ativo',
            'used_coupon' => 'Cliente Cupom Usado',
            'expiring_coupon' => 'Cliente Cupom Vencendo',
            'expired_coupon' => 'Cliente Cupom Vencido',
            'canceled_coupon' => 'Cliente Cupom Cancelado',
        ])->mapWithKeys(fn (string $name, string $key): array => [
            $key => Customer::query()->firstOrCreate(
                [
                    'wash_location_id' => $location->id,
                    'email' => strtolower(str_replace('_', '.', $key)).'.'.$location->id.'@autoflow.test',
                ],
                [
                    'name' => $name,
                    'phone' => '(11) 98888-'.str_pad((string) ($location->id + strlen($key)), 4, '0', STR_PAD_LEFT),
                    'notes' => 'Cliente criado pelo seeder de cenarios de fidelidade.',
                ],
            ),
        ])->all();
    }

    private function createNearRewardScenario(WashLocation $location, Customer $customer, Service $service, User $user): void
    {
        $this->createPaidWashOrder($location, $customer, $service, $user, 'QUASE-1', now()->subDays(6));
        $this->createPaidWashOrder($location, $customer, $service, $user, 'QUASE-2', now()->subDays(3));
    }

    private function createCompletedCycle(
        WashLocation $location,
        Customer $customer,
        Service $service,
        User $user,
        string $scenario,
        Carbon $firstDate,
    ): WashOrder {
        $this->createPaidWashOrder($location, $customer, $service, $user, $scenario.'-1', $firstDate);
        $this->createPaidWashOrder($location, $customer, $service, $user, $scenario.'-2', $firstDate->copy()->addDays(2));

        return $this->createPaidWashOrder($location, $customer, $service, $user, $scenario.'-3', $firstDate->copy()->addDays(4));
    }

    private function createCouponUsageOrder(
        WashLocation $location,
        Customer $customer,
        Service $service,
        User $user,
        LoyaltyCoupon $coupon,
    ): void {
        $washOrder = $this->createPaidWashOrder($location, $customer, $service, $user, 'USO-CUPOM', now()->subDays(2), [
            'payment_status' => WashOrder::PAYMENT_COURTESY,
            'loyalty_coupon_id' => $coupon->id,
            'loyalty_discount_amount' => $service->base_price,
        ]);

        $coupon->forceFill(['used_wash_order_id' => $washOrder->id])->save();

        $washOrder->payments()->create([
            'user_id' => $user->id,
            'method' => Payment::METHOD_COURTESY,
            'amount' => 0,
            'paid_at' => $washOrder->completed_at,
            'notes' => 'Lavagem quitada com cupom de fidelidade '.$coupon->code.'.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createPaidWashOrder(
        WashLocation $location,
        Customer $customer,
        Service $service,
        User $user,
        string $scenario,
        Carbon $enteredAt,
        array $overrides = [],
    ): WashOrder {
        $vehicle = $this->vehicleFor($location, $customer);
        $completedAt = $enteredAt->copy()->addMinutes(max(40, (int) $service->estimated_minutes));
        $washOrder = WashOrder::query()->create(array_merge([
            'code' => self::CODE_PREFIX.'-'.$location->id.'-'.$scenario,
            'wash_location_id' => $location->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'assigned_user_id' => $user->id,
            'total_amount' => $service->base_price,
            'status' => WashOrder::STATUS_DELIVERED,
            'payment_status' => WashOrder::PAYMENT_PAID,
            'entered_at' => $enteredAt,
            'estimated_completion_at' => $enteredAt->copy()->addMinutes((int) $service->estimated_minutes),
            'completed_at' => $completedAt,
            'notes' => 'Lavagem criada pelo seeder de cenarios de fidelidade.',
        ], $overrides));

        $washOrder->services()->syncWithoutDetaching([
            $service->id => [
                'service_name' => $service->name,
                'price' => $service->base_price,
                'estimated_minutes' => $service->estimated_minutes,
                'created_at' => $enteredAt,
                'updated_at' => $enteredAt,
            ],
        ]);
        $washOrder->teamMembers()->syncWithoutDetaching([$user->id]);

        if ($washOrder->payment_status === WashOrder::PAYMENT_PAID) {
            $washOrder->payments()->create([
                'user_id' => $user->id,
                'method' => Payment::METHOD_PIX,
                'amount' => $washOrder->total_amount,
                'paid_at' => $completedAt,
                'notes' => 'Pagamento criado pelo seeder de cenarios de fidelidade.',
            ]);
        }

        return $washOrder;
    }

    private function vehicleFor(WashLocation $location, Customer $customer): Vehicle
    {
        return $customer->vehicles()->first()
            ?? Vehicle::query()->create([
                'wash_location_id' => $location->id,
                'customer_id' => $customer->id,
                'plate' => 'FID'.str_pad((string) (($location->id + $customer->id) % 10000), 4, '0', STR_PAD_LEFT),
                'brand' => 'Toyota',
                'model' => 'Corolla',
                'type' => 'carro',
                'color' => 'Prata',
                'notes' => 'Veiculo criado pelo seeder de cenarios de fidelidade.',
            ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function couponFor(
        WashLocation $location,
        LoyaltyProgram $program,
        Customer $customer,
        WashOrder $sourceOrder,
        Service $service,
        string $scenario,
        array $overrides,
    ): LoyaltyCoupon {
        return LoyaltyCoupon::query()->create(array_merge([
            'wash_location_id' => $location->id,
            'loyalty_program_id' => $program->id,
            'customer_id' => $customer->id,
            'source_wash_order_id' => $sourceOrder->id,
            'reward_service_id' => $service->id,
            'code' => self::CODE_PREFIX.'-'.$location->id.'-'.$scenario,
            'metadata' => [
                'seeded_by' => self::class,
                'threshold' => $program->threshold,
                'reward_type' => $program->reward_type,
            ],
        ], $overrides));
    }
}
