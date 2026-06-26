<?php

namespace Tests\Feature\App;

use App\Models\Customer;
use App\Models\LoyaltyCoupon;
use App\Models\LoyaltyProgram;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WashLocation;
use App\Models\WashOrder;
use App\Services\Loyalty\EvaluateLoyaltyProgramService;
use App\Support\Loyalty\LoyaltyProgress;
use App\Support\WashOrders\WashOrderStatusFlow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoyaltyProgramTest extends TestCase
{
    use RefreshDatabase;

    public function test_coupon_is_generated_when_customer_reaches_loyalty_goal(): void
    {
        $location = WashLocation::factory()->create();
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'wash_location_id' => $location->id,
        ]);
        $customer = Customer::factory()->create(['wash_location_id' => $location->id]);
        $vehicle = Vehicle::factory()->for($customer)->create(['wash_location_id' => $location->id]);
        $service = Service::factory()->create([
            'wash_location_id' => $location->id,
            'name' => 'Ducha simples',
            'category' => 'Lavagem',
            'active' => true,
        ]);

        LoyaltyProgram::query()->create([
            'wash_location_id' => $location->id,
            'is_active' => true,
            'threshold' => 3,
            'count_scope' => LoyaltyProgram::COUNT_ANY,
            'reward_type' => LoyaltyProgram::REWARD_FIXED_SERVICE,
            'reward_service_id' => $service->id,
            'coupon_valid_days' => 30,
        ]);

        $this->createDeliveredWashOrder($location, $customer, $vehicle, $service);
        $this->createDeliveredWashOrder($location, $customer, $vehicle, $service);
        $currentOrder = $this->createReadyPaidWashOrder($location, $customer, $vehicle, $service);

        $this->actingAs($admin)
            ->patch(route('wash-orders.update-status', $currentOrder), [
                'status' => WashOrder::STATUS_DELIVERED,
            ])
            ->assertRedirect();

        $coupon = LoyaltyCoupon::query()->firstOrFail();

        $this->assertSame($location->id, $coupon->wash_location_id);
        $this->assertSame($customer->id, $coupon->customer_id);
        $this->assertSame($currentOrder->id, $coupon->source_wash_order_id);
        $this->assertSame($service->id, $coupon->reward_service_id);
        $this->assertSame(LoyaltyCoupon::STATUS_ACTIVE, $coupon->status);
        $this->assertStringStartsWith('FID-'.$location->id.'-', $coupon->code);
    }

    public function test_coupon_is_not_generated_when_service_scope_does_not_match(): void
    {
        $location = WashLocation::factory()->create();
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'wash_location_id' => $location->id,
        ]);
        $customer = Customer::factory()->create(['wash_location_id' => $location->id]);
        $vehicle = Vehicle::factory()->for($customer)->create(['wash_location_id' => $location->id]);
        $ducha = Service::factory()->create([
            'wash_location_id' => $location->id,
            'name' => 'Ducha simples',
            'category' => 'Lavagem',
            'active' => true,
        ]);
        $completa = Service::factory()->create([
            'wash_location_id' => $location->id,
            'name' => 'Lavagem completa',
            'category' => 'Lavagem',
            'active' => true,
        ]);

        LoyaltyProgram::query()->create([
            'wash_location_id' => $location->id,
            'is_active' => true,
            'threshold' => 2,
            'count_scope' => LoyaltyProgram::COUNT_SERVICE,
            'qualifying_service_id' => $completa->id,
            'reward_type' => LoyaltyProgram::REWARD_SAME_SERVICE,
            'coupon_valid_days' => 30,
        ]);

        $this->createDeliveredWashOrder($location, $customer, $vehicle, $ducha);
        $currentOrder = $this->createReadyPaidWashOrder($location, $customer, $vehicle, $ducha);

        $this->actingAs($admin)
            ->patch(route('wash-orders.update-status', $currentOrder), [
                'status' => WashOrder::STATUS_DELIVERED,
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('loyalty_coupons', 0);
    }

    public function test_accumulated_customer_history_generates_available_coupons(): void
    {
        $location = WashLocation::factory()->create();
        $customer = Customer::factory()->create(['wash_location_id' => $location->id]);
        $vehicle = Vehicle::factory()->for($customer)->create(['wash_location_id' => $location->id]);
        $service = Service::factory()->create([
            'wash_location_id' => $location->id,
            'name' => 'Ducha simples',
            'category' => 'Lavagem',
            'active' => true,
        ]);
        $program = LoyaltyProgram::query()->create([
            'wash_location_id' => $location->id,
            'is_active' => true,
            'threshold' => 3,
            'count_scope' => LoyaltyProgram::COUNT_ANY,
            'reward_type' => LoyaltyProgram::REWARD_FIXED_SERVICE,
            'reward_service_id' => $service->id,
            'coupon_valid_days' => 30,
        ]);

        for ($i = 0; $i < 6; $i++) {
            $this->createDeliveredWashOrder($location, $customer, $vehicle, $service);
        }

        $created = app(EvaluateLoyaltyProgramService::class)->handleEligibleCustomers($program);
        $progress = LoyaltyProgress::forCustomer($customer, $program);

        $this->assertSame(2, $created);
        $this->assertDatabaseCount('loyalty_coupons', 2);
        $this->assertSame(0, $progress['current']);
        $this->assertSame(2, $progress['active_coupons']);
        $this->assertTrue($progress['has_active_coupon']);
    }

    public function test_active_coupon_is_visible_on_wash_order_page(): void
    {
        $location = WashLocation::factory()->create();
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'wash_location_id' => $location->id,
        ]);
        $customer = Customer::factory()->create(['wash_location_id' => $location->id]);
        $vehicle = Vehicle::factory()->for($customer)->create(['wash_location_id' => $location->id]);
        $service = Service::factory()->create([
            'wash_location_id' => $location->id,
            'name' => 'Ducha simples',
        ]);
        $washOrder = $this->createReadyPaidWashOrder($location, $customer, $vehicle, $service);
        $program = LoyaltyProgram::query()->create([
            'wash_location_id' => $location->id,
            'is_active' => true,
            'threshold' => 10,
            'count_scope' => LoyaltyProgram::COUNT_ANY,
            'reward_type' => LoyaltyProgram::REWARD_FIXED_SERVICE,
            'reward_service_id' => $service->id,
            'coupon_valid_days' => 30,
        ]);

        LoyaltyCoupon::query()->create([
            'wash_location_id' => $location->id,
            'loyalty_program_id' => $program->id,
            'customer_id' => $customer->id,
            'source_wash_order_id' => $washOrder->id,
            'reward_service_id' => $service->id,
            'code' => 'FID-TESTE-123',
            'status' => LoyaltyCoupon::STATUS_ACTIVE,
            'earned_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $this->actingAs($admin)
            ->get(route('wash-orders.show', $washOrder))
            ->assertOk()
            ->assertSee('Cupons ativos')
            ->assertSee('Cupom disponível para próxima lavagem')
            ->assertSee('Lavagem paga')
            ->assertSee('Pagamento já registrado')
            ->assertSee('FID-TESTE-123')
            ->assertSee('Ducha simples');
    }

    public function test_coupon_can_be_applied_to_compatible_wash_order_and_is_marked_as_used(): void
    {
        $location = WashLocation::factory()->create();
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'wash_location_id' => $location->id,
        ]);
        $customer = Customer::factory()->create(['wash_location_id' => $location->id]);
        $vehicle = Vehicle::factory()->for($customer)->create(['wash_location_id' => $location->id]);
        $service = Service::factory()->create([
            'wash_location_id' => $location->id,
            'name' => 'Ducha + aspiracao',
            'base_price' => 45,
        ]);
        $program = LoyaltyProgram::query()->create([
            'wash_location_id' => $location->id,
            'is_active' => true,
            'threshold' => 3,
            'count_scope' => LoyaltyProgram::COUNT_ANY,
            'reward_type' => LoyaltyProgram::REWARD_FIXED_SERVICE,
            'reward_service_id' => $service->id,
            'coupon_valid_days' => 30,
        ]);
        $sourceOrder = $this->createDeliveredWashOrder($location, $customer, $vehicle, $service);
        $washOrder = $this->createReadyPaidWashOrder($location, $customer, $vehicle, $service);
        $washOrder->forceFill([
            'payment_status' => WashOrder::PAYMENT_PENDING,
            'total_amount' => 45,
        ])->save();
        $coupon = LoyaltyCoupon::query()->create([
            'wash_location_id' => $location->id,
            'loyalty_program_id' => $program->id,
            'customer_id' => $customer->id,
            'source_wash_order_id' => $sourceOrder->id,
            'reward_service_id' => $service->id,
            'code' => 'FID-APLICAR-1',
            'status' => LoyaltyCoupon::STATUS_ACTIVE,
            'earned_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $this->actingAs($admin)
            ->get(route('wash-orders.show', $washOrder))
            ->assertOk()
            ->assertSee('Aplicar cupom')
            ->assertSee('Pagamento pendente')
            ->assertSee('FID-APLICAR-1');

        $this->actingAs($admin)
            ->post(route('wash-orders.loyalty-coupons.apply', $washOrder), [
                'loyalty_coupon_id' => $coupon->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('loyalty_coupons', [
            'id' => $coupon->id,
            'status' => LoyaltyCoupon::STATUS_USED,
            'used_wash_order_id' => $washOrder->id,
            'used_by_user_id' => $admin->id,
        ]);
        $this->assertDatabaseHas('wash_orders', [
            'id' => $washOrder->id,
            'loyalty_coupon_id' => $coupon->id,
            'loyalty_discount_amount' => 45,
            'payment_status' => WashOrder::PAYMENT_COURTESY,
        ]);
        $this->assertDatabaseHas('payments', [
            'wash_order_id' => $washOrder->id,
            'method' => \App\Models\Payment::METHOD_COURTESY,
            'amount' => 0,
        ]);
        $this->assertSame(0.0, $washOrder->refresh()->payableAmount());
    }

    public function test_coupon_cannot_be_applied_when_reward_service_is_not_in_wash_order(): void
    {
        $location = WashLocation::factory()->create();
        $admin = User::factory()->create(['wash_location_id' => $location->id]);
        $customer = Customer::factory()->create(['wash_location_id' => $location->id]);
        $vehicle = Vehicle::factory()->for($customer)->create(['wash_location_id' => $location->id]);
        $ducha = Service::factory()->create([
            'wash_location_id' => $location->id,
            'name' => 'Ducha simples',
            'base_price' => 30,
        ]);
        $cera = Service::factory()->create([
            'wash_location_id' => $location->id,
            'name' => 'Cera',
            'base_price' => 25,
        ]);
        $program = LoyaltyProgram::query()->create([
            'wash_location_id' => $location->id,
            'is_active' => true,
            'threshold' => 3,
            'count_scope' => LoyaltyProgram::COUNT_ANY,
            'reward_type' => LoyaltyProgram::REWARD_FIXED_SERVICE,
            'reward_service_id' => $cera->id,
            'coupon_valid_days' => 30,
        ]);
        $sourceOrder = $this->createDeliveredWashOrder($location, $customer, $vehicle, $cera);
        $washOrder = $this->createReadyPaidWashOrder($location, $customer, $vehicle, $ducha);
        $washOrder->forceFill(['payment_status' => WashOrder::PAYMENT_PENDING])->save();
        $coupon = LoyaltyCoupon::query()->create([
            'wash_location_id' => $location->id,
            'loyalty_program_id' => $program->id,
            'customer_id' => $customer->id,
            'source_wash_order_id' => $sourceOrder->id,
            'reward_service_id' => $cera->id,
            'code' => 'FID-CERA-1',
            'status' => LoyaltyCoupon::STATUS_ACTIVE,
            'earned_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $this->actingAs($admin)
            ->post(route('wash-orders.loyalty-coupons.apply', $washOrder), [
                'loyalty_coupon_id' => $coupon->id,
            ])
            ->assertSessionHasErrors('loyalty_coupon_id');

        $this->assertSame(LoyaltyCoupon::STATUS_ACTIVE, $coupon->refresh()->status);
        $this->assertNull($washOrder->refresh()->loyalty_coupon_id);

        $this->actingAs($admin)
            ->get(route('wash-orders.show', $washOrder))
            ->assertOk()
            ->assertSee('O cliente tem cupom ativo, mas nenhum é compatível com os serviços desta lavagem.')
            ->assertSee('Sem abatimento')
            ->assertSee('Cupom nao gera abatimento para esta lavagem.')
            ->assertDontSee('Cupom aplicável nesta lavagem');
    }

    public function test_coupon_without_explicit_reward_service_uses_source_wash_service(): void
    {
        $location = WashLocation::factory()->create();
        $admin = User::factory()->create(['wash_location_id' => $location->id]);
        $customer = Customer::factory()->create(['wash_location_id' => $location->id]);
        $vehicle = Vehicle::factory()->for($customer)->create(['wash_location_id' => $location->id]);
        $service = Service::factory()->create([
            'wash_location_id' => $location->id,
            'name' => 'Ducha simples',
            'base_price' => 35,
        ]);
        $program = LoyaltyProgram::query()->create([
            'wash_location_id' => $location->id,
            'is_active' => true,
            'threshold' => 3,
            'count_scope' => LoyaltyProgram::COUNT_ANY,
            'reward_type' => LoyaltyProgram::REWARD_FIXED_SERVICE,
            'reward_service_id' => null,
            'coupon_valid_days' => 30,
        ]);
        $sourceOrder = $this->createDeliveredWashOrder($location, $customer, $vehicle, $service);
        $washOrder = $this->createReadyPaidWashOrder($location, $customer, $vehicle, $service);
        $washOrder->forceFill([
            'payment_status' => WashOrder::PAYMENT_PENDING,
            'total_amount' => 35,
        ])->save();
        $coupon = LoyaltyCoupon::query()->create([
            'wash_location_id' => $location->id,
            'loyalty_program_id' => $program->id,
            'customer_id' => $customer->id,
            'source_wash_order_id' => $sourceOrder->id,
            'reward_service_id' => null,
            'code' => 'FID-LEGADO-1',
            'status' => LoyaltyCoupon::STATUS_ACTIVE,
            'earned_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $this->actingAs($admin)
            ->post(route('wash-orders.loyalty-coupons.apply', $washOrder), [
                'loyalty_coupon_id' => $coupon->id,
            ])
            ->assertRedirect();

        $this->assertSame(LoyaltyCoupon::STATUS_USED, $coupon->refresh()->status);
        $this->assertSame(35.0, (float) $washOrder->refresh()->loyalty_discount_amount);
    }

    public function test_applied_coupon_can_be_removed_before_real_payment_or_delivery(): void
    {
        $location = WashLocation::factory()->create();
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'wash_location_id' => $location->id,
        ]);
        $customer = Customer::factory()->create(['wash_location_id' => $location->id]);
        $vehicle = Vehicle::factory()->for($customer)->create(['wash_location_id' => $location->id]);
        $service = Service::factory()->create([
            'wash_location_id' => $location->id,
            'name' => 'Ducha simples',
            'base_price' => 35,
        ]);
        $program = LoyaltyProgram::query()->create([
            'wash_location_id' => $location->id,
            'is_active' => true,
            'threshold' => 3,
            'count_scope' => LoyaltyProgram::COUNT_ANY,
            'reward_type' => LoyaltyProgram::REWARD_FIXED_SERVICE,
            'reward_service_id' => $service->id,
            'coupon_valid_days' => 30,
        ]);
        $sourceOrder = $this->createDeliveredWashOrder($location, $customer, $vehicle, $service);
        $washOrder = $this->createReadyPaidWashOrder($location, $customer, $vehicle, $service);
        $washOrder->forceFill([
            'payment_status' => WashOrder::PAYMENT_PENDING,
            'total_amount' => 35,
        ])->save();
        $coupon = LoyaltyCoupon::query()->create([
            'wash_location_id' => $location->id,
            'loyalty_program_id' => $program->id,
            'customer_id' => $customer->id,
            'source_wash_order_id' => $sourceOrder->id,
            'reward_service_id' => $service->id,
            'code' => 'FID-REMOVE-1',
            'status' => LoyaltyCoupon::STATUS_ACTIVE,
            'earned_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $this->actingAs($admin)
            ->post(route('wash-orders.loyalty-coupons.apply', $washOrder), [
                'loyalty_coupon_id' => $coupon->id,
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->get(route('wash-orders.show', $washOrder))
            ->assertOk()
            ->assertSee('Remover cupom aplicado');

        $this->actingAs($admin)
            ->delete(route('wash-orders.loyalty-coupons.remove', $washOrder))
            ->assertRedirect();

        $this->assertDatabaseHas('loyalty_coupons', [
            'id' => $coupon->id,
            'status' => LoyaltyCoupon::STATUS_ACTIVE,
            'used_wash_order_id' => null,
            'used_by_user_id' => null,
            'used_at' => null,
        ]);
        $this->assertDatabaseHas('wash_orders', [
            'id' => $washOrder->id,
            'loyalty_coupon_id' => null,
            'loyalty_discount_amount' => 0,
            'payment_status' => WashOrder::PAYMENT_PENDING,
        ]);
        $this->assertDatabaseMissing('payments', [
            'wash_order_id' => $washOrder->id,
            'notes' => 'Lavagem quitada com cupom de fidelidade FID-REMOVE-1.',
        ]);
    }

    public function test_applied_coupon_cannot_be_removed_after_real_payment(): void
    {
        $location = WashLocation::factory()->create();
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'wash_location_id' => $location->id,
        ]);
        $customer = Customer::factory()->create(['wash_location_id' => $location->id]);
        $vehicle = Vehicle::factory()->for($customer)->create(['wash_location_id' => $location->id]);
        $service = Service::factory()->create([
            'wash_location_id' => $location->id,
            'name' => 'Ducha simples',
            'base_price' => 20,
        ]);
        $program = LoyaltyProgram::query()->create([
            'wash_location_id' => $location->id,
            'is_active' => true,
            'threshold' => 3,
            'count_scope' => LoyaltyProgram::COUNT_ANY,
            'reward_type' => LoyaltyProgram::REWARD_FIXED_SERVICE,
            'reward_service_id' => $service->id,
            'coupon_valid_days' => 30,
        ]);
        $sourceOrder = $this->createDeliveredWashOrder($location, $customer, $vehicle, $service);
        $washOrder = $this->createReadyPaidWashOrder($location, $customer, $vehicle, $service);
        $washOrder->forceFill([
            'payment_status' => WashOrder::PAYMENT_PENDING,
            'total_amount' => 50,
        ])->save();
        $coupon = LoyaltyCoupon::query()->create([
            'wash_location_id' => $location->id,
            'loyalty_program_id' => $program->id,
            'customer_id' => $customer->id,
            'source_wash_order_id' => $sourceOrder->id,
            'reward_service_id' => $service->id,
            'code' => 'FID-BLOCK-1',
            'status' => LoyaltyCoupon::STATUS_ACTIVE,
            'earned_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $this->actingAs($admin)
            ->post(route('wash-orders.loyalty-coupons.apply', $washOrder), [
                'loyalty_coupon_id' => $coupon->id,
            ])
            ->assertRedirect();

        $washOrder->payments()->create([
            'user_id' => $admin->id,
            'method' => \App\Models\Payment::METHOD_PIX,
            'amount' => 30,
            'paid_at' => now(),
            'notes' => 'Pagamento restante.',
        ]);
        $washOrder->forceFill(['payment_status' => WashOrder::PAYMENT_PAID])->save();

        $this->actingAs($admin)
            ->delete(route('wash-orders.loyalty-coupons.remove', $washOrder))
            ->assertSessionHasErrors('loyalty_coupon_id');

        $this->assertSame(LoyaltyCoupon::STATUS_USED, $coupon->refresh()->status);
        $this->assertSame($coupon->id, $washOrder->refresh()->loyalty_coupon_id);
    }

    public function test_wax_status_is_hidden_and_blocked_when_wash_order_has_no_wax_service(): void
    {
        $location = WashLocation::factory()->create();
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'wash_location_id' => $location->id,
        ]);
        $customer = Customer::factory()->create(['wash_location_id' => $location->id]);
        $vehicle = Vehicle::factory()->for($customer)->create(['wash_location_id' => $location->id]);
        $service = Service::factory()->create([
            'wash_location_id' => $location->id,
            'name' => 'Ducha simples',
            'category' => 'Lavagem',
        ]);
        $washOrder = $this->createReadyPaidWashOrder($location, $customer, $vehicle, $service);
        $washOrder->forceFill([
            'status' => WashOrder::STATUS_WASHING,
            'payment_status' => WashOrder::PAYMENT_PENDING,
        ])->save();

        $this->assertFalse(WashOrderStatusFlow::washOrderCanUseStatus($washOrder->load('services'), WashOrder::STATUS_WAXING));

        $this->actingAs($admin)
            ->get(route('wash-orders.show', $washOrder))
            ->assertOk()
            ->assertDontSee('Aplicando cera');

        $this->actingAs($admin)
            ->patch(route('wash-orders.update-status', $washOrder), [
                'status' => WashOrder::STATUS_WAXING,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('wash_orders', [
            'id' => $washOrder->id,
            'status' => WashOrder::STATUS_WASHING,
        ]);
    }

    private function createDeliveredWashOrder(
        WashLocation $location,
        Customer $customer,
        Vehicle $vehicle,
        Service $service,
    ): WashOrder {
        $washOrder = WashOrder::factory()->for($customer)->for($vehicle)->create([
            'wash_location_id' => $location->id,
            'status' => WashOrder::STATUS_DELIVERED,
            'payment_status' => WashOrder::PAYMENT_PAID,
            'completed_at' => now()->subDay(),
        ]);

        $this->attachService($washOrder, $service);

        return $washOrder;
    }

    private function createReadyPaidWashOrder(
        WashLocation $location,
        Customer $customer,
        Vehicle $vehicle,
        Service $service,
    ): WashOrder {
        $washOrder = WashOrder::factory()->for($customer)->for($vehicle)->create([
            'wash_location_id' => $location->id,
            'status' => WashOrder::STATUS_READY,
            'payment_status' => WashOrder::PAYMENT_PAID,
        ]);

        $this->attachService($washOrder, $service);

        return $washOrder;
    }

    private function attachService(WashOrder $washOrder, Service $service): void
    {
        $washOrder->services()->attach($service->id, [
            'service_name' => $service->name,
            'price' => $service->base_price,
            'estimated_minutes' => $service->estimated_minutes,
        ]);
    }
}
