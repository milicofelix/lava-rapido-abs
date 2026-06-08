<?php

namespace Tests\Feature\App;

use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WashOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WashOrderManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendant_can_open_wash_order_with_selected_services(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $vehicle = Vehicle::factory()->for($customer)->create(['plate' => 'ABC1D23']);
        $services = Service::factory()->count(2)->sequence(
            ['name' => 'Lavagem completa', 'base_price' => 80, 'estimated_minutes' => 70],
            ['name' => 'Cera', 'base_price' => 45, 'estimated_minutes' => 35],
        )->create(['active' => true]);

        $this->actingAs($user)->post(route('wash-orders.store'), [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'assigned_user_id' => $user->id,
            'service_ids' => $services->pluck('id')->all(),
            'notes' => 'Cliente vai buscar no fim da tarde.',
        ])->assertRedirect();

        $washOrder = WashOrder::query()->firstOrFail();

        $this->assertSame('125.00', (string) $washOrder->total_amount);
        $this->assertSame(WashOrder::STATUS_AWAITING, $washOrder->status);
        $this->assertCount(2, $washOrder->services);
        $this->assertDatabaseHas('status_histories', [
            'wash_order_id' => $washOrder->id,
            'from_status' => null,
            'to_status' => WashOrder::STATUS_AWAITING,
        ]);
    }

    public function test_operator_can_change_wash_order_status(): void
    {
        $user = User::factory()->create(['role' => 'operator']);
        $washOrder = WashOrder::factory()->create();

        $this->actingAs($user)->patch(route('wash-orders.update-status', $washOrder), [
            'status' => WashOrder::STATUS_WASHING,
            'notes' => 'Lavagem iniciada.',
        ])->assertRedirect();

        $washOrder->refresh();

        $this->assertSame(WashOrder::STATUS_WASHING, $washOrder->status);
        $this->assertDatabaseHas('status_histories', [
            'wash_order_id' => $washOrder->id,
            'user_id' => $user->id,
            'from_status' => WashOrder::STATUS_AWAITING,
            'to_status' => WashOrder::STATUS_WASHING,
            'notes' => 'Lavagem iniciada.',
        ]);
    }

    public function test_vehicle_must_belong_to_selected_customer(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $otherCustomer = Customer::factory()->create();
        $vehicle = Vehicle::factory()->for($otherCustomer)->create();
        $service = Service::factory()->create(['active' => true]);

        $this->actingAs($user)->post(route('wash-orders.store'), [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'service_ids' => [$service->id],
        ])->assertSessionHasErrors('vehicle_id');

        $this->assertDatabaseCount('wash_orders', 0);
    }
}
