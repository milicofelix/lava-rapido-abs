<?php

namespace Tests\Feature\App;

use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WashOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfilePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_cannot_access_admin_only_screens(): void
    {
        $operator = User::factory()->create(['role' => User::ROLE_OPERATOR]);

        $this->actingAs($operator)->get(route('finance.index'))->assertForbidden();
        $this->actingAs($operator)->get(route('employees.index'))->assertForbidden();
        $this->actingAs($operator)->get(route('services.index'))->assertForbidden();
    }

    public function test_attendant_can_create_wash_order_but_cannot_update_status(): void
    {
        $attendant = User::factory()->create(['role' => User::ROLE_ATTENDANT]);
        $customer = Customer::factory()->create();
        $vehicle = Vehicle::factory()->for($customer)->create();
        $service = Service::factory()->create(['active' => true]);
        $washOrder = WashOrder::factory()->create();

        $this->actingAs($attendant)->post(route('wash-orders.store'), [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'service_ids' => [$service->id],
        ])->assertRedirect();

        $this->actingAs($attendant)->patch(route('wash-orders.update-status', $washOrder), [
            'status' => WashOrder::STATUS_WASHING,
        ])->assertForbidden();
    }

    public function test_operator_can_update_status_but_cannot_create_wash_order(): void
    {
        $operator = User::factory()->create(['role' => User::ROLE_OPERATOR]);
        $washOrder = WashOrder::factory()->create();

        $this->actingAs($operator)->patch(route('wash-orders.update-status', $washOrder), [
            'status' => WashOrder::STATUS_WASHING,
        ])->assertRedirect();

        $this->actingAs($operator)->get(route('wash-orders.create'))->assertForbidden();
    }
}
