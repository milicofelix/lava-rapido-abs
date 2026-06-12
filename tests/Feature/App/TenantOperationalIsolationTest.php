<?php

namespace Tests\Feature\App;

use App\Models\CashRegister;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WashLocation;
use App\Models\WashOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantOperationalIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_lists_only_customers_vehicles_services_and_wash_orders_from_own_unit(): void
    {
        [$owner, $ownLocation, $otherLocation] = $this->ownerWithTwoLocations();

        $ownCustomer = Customer::factory()->create(['wash_location_id' => $ownLocation->id, 'name' => 'Cliente Unidade A']);
        $otherCustomer = Customer::factory()->create(['wash_location_id' => $otherLocation->id, 'name' => 'Cliente Unidade B']);

        $ownVehicle = Vehicle::factory()->create(['wash_location_id' => $ownLocation->id, 'customer_id' => $ownCustomer->id, 'plate' => 'AAA1A11']);
        $otherVehicle = Vehicle::factory()->create(['wash_location_id' => $otherLocation->id, 'customer_id' => $otherCustomer->id, 'plate' => 'BBB2B22']);

        $ownService = Service::factory()->create(['wash_location_id' => $ownLocation->id, 'name' => 'Lavagem Tenant A']);
        $otherService = Service::factory()->create(['wash_location_id' => $otherLocation->id, 'name' => 'Lavagem Tenant B']);

        $ownOrder = WashOrder::factory()->create(['wash_location_id' => $ownLocation->id, 'customer_id' => $ownCustomer->id, 'vehicle_id' => $ownVehicle->id]);
        $otherOrder = WashOrder::factory()->create(['wash_location_id' => $otherLocation->id, 'customer_id' => $otherCustomer->id, 'vehicle_id' => $otherVehicle->id]);

        $this->actingAs($owner)->get(route('customers.index'))
            ->assertOk()
            ->assertSee('Cliente Unidade A')
            ->assertDontSee('Cliente Unidade B');

        $this->actingAs($owner)->get(route('vehicles.index'))
            ->assertOk()
            ->assertSee('AAA1A11')
            ->assertDontSee('BBB2B22');

        $this->actingAs($owner)->get(route('services.index'))
            ->assertOk()
            ->assertSee('Lavagem Tenant A')
            ->assertDontSee('Lavagem Tenant B');

        $this->actingAs($owner)->get(route('wash-orders.index'))
            ->assertOk()
            ->assertSee($ownOrder->code)
            ->assertDontSee($otherOrder->code);
    }

    public function test_owner_cannot_open_wash_order_from_another_unit(): void
    {
        [$owner, , $otherLocation] = $this->ownerWithTwoLocations();

        $otherCustomer = Customer::factory()->create(['wash_location_id' => $otherLocation->id]);
        $otherVehicle = Vehicle::factory()->create(['wash_location_id' => $otherLocation->id, 'customer_id' => $otherCustomer->id]);
        $otherOrder = WashOrder::factory()->create(['wash_location_id' => $otherLocation->id, 'customer_id' => $otherCustomer->id, 'vehicle_id' => $otherVehicle->id]);

        $this->actingAs($owner)->get(route('wash-orders.show', $otherOrder))
            ->assertNotFound();
    }

    public function test_owner_finance_and_credit_are_scoped_by_own_unit(): void
    {
        [$owner, $ownLocation, $otherLocation] = $this->ownerWithTwoLocations();

        $ownOrder = $this->orderForLocation($ownLocation, ['payment_status' => WashOrder::PAYMENT_CREDIT_PENDING, 'total_amount' => 90]);
        $otherOrder = $this->orderForLocation($otherLocation, ['payment_status' => WashOrder::PAYMENT_CREDIT_PENDING, 'total_amount' => 200]);

        Payment::factory()->create(['wash_order_id' => $ownOrder->id, 'amount' => 50, 'paid_at' => now()]);
        Payment::factory()->create(['wash_order_id' => $otherOrder->id, 'amount' => 999, 'paid_at' => now()]);

        $this->actingAs($owner)->get(route('finance.index'))
            ->assertOk()
            ->assertSee('R$ 50,00')
            ->assertDontSee('R$ 999,00');

        $this->actingAs($owner)->get(route('finance.credit-receivables.index'))
            ->assertOk()
            ->assertSee('R$ 90,00')
            ->assertDontSee('R$ 200,00');
    }

    public function test_owner_cash_register_is_scoped_by_own_unit(): void
    {
        [$owner, $ownLocation, $otherLocation] = $this->ownerWithTwoLocations();

        CashRegister::factory()->create(['wash_location_id' => $otherLocation->id, 'opening_balance' => 999]);
        CashRegister::factory()->create(['wash_location_id' => $ownLocation->id, 'opening_balance' => 123]);

        $this->actingAs($owner)->get(route('finance.cash-registers.index'))
            ->assertOk()
            ->assertSee('R$ 123,00')
            ->assertDontSee('R$ 999,00');
    }

    /**
     * @return array{0: User, 1: WashLocation, 2: WashLocation}
     */
    private function ownerWithTwoLocations(): array
    {
        $ownLocation = WashLocation::factory()->create(['name' => 'Tenant A']);
        $otherLocation = WashLocation::factory()->create(['name' => 'Tenant B']);
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $ownLocation->id,
        ]);

        return [$owner, $ownLocation, $otherLocation];
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function orderForLocation(WashLocation $location, array $overrides = []): WashOrder
    {
        $customer = Customer::factory()->create(['wash_location_id' => $location->id]);
        $vehicle = Vehicle::factory()->create(['wash_location_id' => $location->id, 'customer_id' => $customer->id]);

        return WashOrder::factory()->create([
            ...$overrides,
            'wash_location_id' => $location->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'entered_at' => now(),
        ]);
    }
}
