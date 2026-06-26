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

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendant_can_create_customer(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('customers.store'), [
            'name' => 'Maria Silva',
            'phone' => '(11) 99999-0000',
            'email' => 'maria@example.com',
            'cpf' => '123.456.789-00',
            'notes' => 'Prefere contato por WhatsApp.',
        ])->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'name' => 'Maria Silva',
            'phone' => '(11) 99999-0000',
        ]);
    }

    public function test_customer_search_finds_vehicle_plate(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->hasVehicles(1, ['plate' => 'ABC1D23'])->create();

        $this->actingAs($user)->get(route('customers.index', ['search' => 'ABC1D23']))
            ->assertOk()
            ->assertSee($customer->name);
    }

    public function test_customer_index_shows_loyalty_progress(): void
    {
        $location = WashLocation::factory()->create();
        $user = User::factory()->create(['wash_location_id' => $location->id]);
        $customer = Customer::factory()->create([
            'wash_location_id' => $location->id,
            'name' => 'Cliente Fidelidade',
        ]);
        $vehicle = Vehicle::factory()->for($customer)->create(['wash_location_id' => $location->id]);
        $service = Service::factory()->create([
            'wash_location_id' => $location->id,
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

        $this->createDeliveredOrder($location, $customer, $vehicle, $service);
        $this->createDeliveredOrder($location, $customer, $vehicle, $service);

        $this->actingAs($user)
            ->get(route('customers.index'))
            ->assertOk()
            ->assertSee('Cliente Fidelidade')
            ->assertSee('2/3 lavadas');
    }

    public function test_customer_edit_shows_loyalty_details_and_coupons(): void
    {
        $location = WashLocation::factory()->create();
        $user = User::factory()->create(['wash_location_id' => $location->id]);
        $customer = Customer::factory()->create([
            'wash_location_id' => $location->id,
            'name' => 'Cliente Cupom',
        ]);
        $vehicle = Vehicle::factory()->for($customer)->create(['wash_location_id' => $location->id]);
        $service = Service::factory()->create([
            'wash_location_id' => $location->id,
            'name' => 'Ducha simples',
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
        $washOrder = $this->createDeliveredOrder($location, $customer, $vehicle, $service);

        LoyaltyCoupon::query()->create([
            'wash_location_id' => $location->id,
            'loyalty_program_id' => $program->id,
            'customer_id' => $customer->id,
            'source_wash_order_id' => $washOrder->id,
            'reward_service_id' => $service->id,
            'code' => 'FID-CLI-123',
            'status' => LoyaltyCoupon::STATUS_ACTIVE,
            'earned_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $this->actingAs($user)
            ->get(route('customers.edit', $customer))
            ->assertOk()
            ->assertSee('Progresso do cliente')
            ->assertSee('Últimos cupons')
            ->assertSee('FID-CLI-123')
            ->assertSee('Ducha simples');
    }

    private function createDeliveredOrder(
        WashLocation $location,
        Customer $customer,
        Vehicle $vehicle,
        Service $service,
    ): WashOrder {
        $washOrder = WashOrder::factory()->for($customer)->for($vehicle)->create([
            'wash_location_id' => $location->id,
            'status' => WashOrder::STATUS_DELIVERED,
            'payment_status' => WashOrder::PAYMENT_PAID,
        ]);

        $washOrder->services()->attach($service->id, [
            'service_name' => $service->name,
            'price' => $service->base_price,
            'estimated_minutes' => $service->estimated_minutes,
        ]);

        return $washOrder;
    }
}
