<?php

namespace Tests\Feature\App;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\LoyaltyCoupon;
use App\Models\LoyaltyProgram;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WashLocation;
use App\Models\WashOrder;
use App\Support\AppNotificationCenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoyaltyCouponExpirationTest extends TestCase
{
    use RefreshDatabase;

    public function test_expire_command_marks_overdue_active_coupons_as_expired(): void
    {
        $location = WashLocation::factory()->create();
        $customer = Customer::factory()->create(['wash_location_id' => $location->id]);
        $vehicle = Vehicle::factory()->for($customer)->create(['wash_location_id' => $location->id]);
        $service = Service::factory()->create(['wash_location_id' => $location->id]);
        $program = $this->program($location, $service);
        $washOrder = $this->deliveredOrder($location, $customer, $vehicle, $service);
        $expiredCoupon = $this->coupon($location, $program, $customer, $washOrder, $service, [
            'code' => 'FID-VENCIDO',
            'expires_at' => now()->subDay(),
        ]);
        $validCoupon = $this->coupon($location, $program, $customer, $washOrder, $service, [
            'code' => 'FID-VALIDO',
            'expires_at' => now()->addDay(),
        ]);

        $this->artisan('loyalty:expire-coupons')
            ->expectsOutput('1 cupom(ns) expirado(s).')
            ->assertSuccessful();

        $this->assertSame(LoyaltyCoupon::STATUS_EXPIRED, $expiredCoupon->fresh()->status);
        $this->assertSame(LoyaltyCoupon::STATUS_ACTIVE, $validCoupon->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_LOYALTY_COUPON_EXPIRED,
            'subject_type' => LoyaltyCoupon::class,
            'subject_id' => $expiredCoupon->id,
        ]);
    }

    public function test_owner_receives_notification_for_loyalty_coupons_expiring_soon(): void
    {
        $location = WashLocation::factory()->create();
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $location->id,
        ]);
        $customer = Customer::factory()->create(['wash_location_id' => $location->id]);
        $vehicle = Vehicle::factory()->for($customer)->create(['wash_location_id' => $location->id]);
        $service = Service::factory()->create(['wash_location_id' => $location->id]);
        $program = $this->program($location, $service);
        $washOrder = $this->deliveredOrder($location, $customer, $vehicle, $service);

        $this->coupon($location, $program, $customer, $washOrder, $service, [
            'code' => 'FID-VENCE-LOGO',
            'expires_at' => now()->addDays(2),
        ]);

        $this->actingAs($owner);

        $notifications = AppNotificationCenter::for($owner);

        $this->assertTrue(collect($notifications)->contains(
            fn (array $notification) => $notification['title'] === '1 cupom vencendo'
                && $notification['url'] === route('loyalty-reports.index', ['status' => LoyaltyCoupon::STATUS_ACTIVE])
        ));
    }

    public function test_owner_expired_coupon_notification_points_to_expired_filter(): void
    {
        $location = WashLocation::factory()->create();
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $location->id,
        ]);
        $customer = Customer::factory()->create(['wash_location_id' => $location->id]);
        $vehicle = Vehicle::factory()->for($customer)->create(['wash_location_id' => $location->id]);
        $service = Service::factory()->create(['wash_location_id' => $location->id]);
        $program = $this->program($location, $service);
        $washOrder = $this->deliveredOrder($location, $customer, $vehicle, $service);

        $this->coupon($location, $program, $customer, $washOrder, $service, [
            'code' => 'FID-JA-VENCEU',
            'expires_at' => now()->subDay(),
        ]);

        $this->actingAs($owner);

        $notifications = AppNotificationCenter::for($owner);

        $this->assertTrue(collect($notifications)->contains(
            fn (array $notification) => $notification['title'] === '1 cupom vencido'
                && $notification['url'] === route('loyalty-reports.index', ['status' => LoyaltyCoupon::STATUS_EXPIRED])
        ));
    }

    private function program(WashLocation $location, Service $service): LoyaltyProgram
    {
        return LoyaltyProgram::query()->create([
            'wash_location_id' => $location->id,
            'is_active' => true,
            'threshold' => 3,
            'count_scope' => LoyaltyProgram::COUNT_ANY,
            'reward_type' => LoyaltyProgram::REWARD_FIXED_SERVICE,
            'reward_service_id' => $service->id,
            'coupon_valid_days' => 30,
        ]);
    }

    private function deliveredOrder(WashLocation $location, Customer $customer, Vehicle $vehicle, Service $service): WashOrder
    {
        $washOrder = WashOrder::factory()->for($customer)->for($vehicle)->create([
            'wash_location_id' => $location->id,
            'assigned_user_id' => User::factory()->create(['wash_location_id' => $location->id])->id,
            'status' => WashOrder::STATUS_DELIVERED,
            'payment_status' => WashOrder::PAYMENT_PAID,
            'total_amount' => $service->base_price,
        ]);

        $washOrder->services()->attach($service->id, [
            'service_name' => $service->name,
            'price' => $service->base_price,
            'estimated_minutes' => $service->estimated_minutes,
        ]);

        return $washOrder;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function coupon(
        WashLocation $location,
        LoyaltyProgram $program,
        Customer $customer,
        WashOrder $washOrder,
        Service $service,
        array $overrides = [],
    ): LoyaltyCoupon {
        return LoyaltyCoupon::query()->create(array_merge([
            'wash_location_id' => $location->id,
            'loyalty_program_id' => $program->id,
            'customer_id' => $customer->id,
            'source_wash_order_id' => $washOrder->id,
            'reward_service_id' => $service->id,
            'code' => 'FID-TESTE',
            'status' => LoyaltyCoupon::STATUS_ACTIVE,
            'earned_at' => now(),
            'expires_at' => now()->addDays(30),
        ], $overrides));
    }
}
