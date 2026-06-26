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
            ->assertSee('FID-TESTE-123')
            ->assertSee('Ducha simples');
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
