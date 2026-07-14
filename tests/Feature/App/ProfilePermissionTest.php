<?php

namespace Tests\Feature\App;

use App\Models\Customer;
use App\Models\CustomerNotification;
use App\Models\RolePermissionSetting;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WashOrder;
use App\Support\Access\AccessControl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfilePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_cannot_access_admin_only_screens(): void
    {
        $operator = User::factory()->create(['role' => User::ROLE_OPERATOR]);
        $washOrder = WashOrder::factory()->create();
        $notification = CustomerNotification::query()->create([
            'wash_order_id' => $washOrder->id,
            'customer_id' => $washOrder->customer_id,
            'user_id' => $operator->id,
            'channel' => CustomerNotification::CHANNEL_WHATSAPP_MANUAL,
            'template_key' => CustomerNotification::TEMPLATE_STATUS_UPDATE,
            'target' => '(11) 99999-0000',
            'message' => 'Teste',
            'status' => CustomerNotification::STATUS_PREPARED,
            'prepared_at' => now(),
        ]);

        $this->actingAs($operator)->get(route('dashboard'))->assertForbidden();
        $this->actingAs($operator)->get(route('wash-orders.index'))->assertForbidden();
        $this->actingAs($operator)->get(route('wash-orders.create'))->assertForbidden();
        $this->actingAs($operator)->get(route('history.index'))->assertForbidden();
        $this->actingAs($operator)->get(route('finance.index'))->assertForbidden();
        $this->actingAs($operator)->get(route('employees.index'))->assertForbidden();
        $this->actingAs($operator)->get(route('services.index'))->assertForbidden();
        $this->actingAs($operator)->post(route('payments.store', $washOrder), [
            'method' => 'pix',
            'amount' => 10,
        ])->assertForbidden();
        $this->actingAs($operator)->get(route('wash-orders.receipt', $washOrder))->assertForbidden();
        $this->actingAs($operator)->post(route('wash-orders.notifications.whatsapp-manual.store', $washOrder), [
            'template_key' => CustomerNotification::TEMPLATE_STATUS_UPDATE,
        ])->assertForbidden();
        $this->actingAs($operator)->patch(route('wash-orders.notifications.mark-as-sent', [$washOrder, $notification]))
            ->assertForbidden();
    }

    public function test_operator_access_is_limited_to_kanban_and_status_update_by_default(): void
    {
        $operator = User::factory()->create(['role' => User::ROLE_OPERATOR]);
        $washOrder = WashOrder::factory()->create(['status' => WashOrder::STATUS_AWAITING]);
        $washOrder->teamMembers()->attach($operator);

        $this->actingAs($operator)->get(route('kanban'))->assertOk();
        $this->actingAs($operator)->get(route('kanban'))
            ->assertOk()
            ->assertDontSee('href="'.route('dashboard').'"', false);

        $this->actingAs($operator)->get(route('wash-orders.show', $washOrder))->assertForbidden();

        $this->actingAs($operator)->patch(route('wash-orders.update-status', $washOrder), [
            'status' => WashOrder::STATUS_WASHING,
        ])->assertRedirect();

        $this->assertSame(WashOrder::STATUS_WASHING, $washOrder->refresh()->status);
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
        $washOrder->teamMembers()->attach($operator);

        $this->actingAs($operator)->patch(route('wash-orders.update-status', $washOrder), [
            'status' => WashOrder::STATUS_WASHING,
        ])->assertRedirect();

        $this->actingAs($operator)->get(route('wash-orders.create'))->assertForbidden();
    }

    public function test_owner_can_enable_operator_wash_order_detail_access(): void
    {
        $operator = User::factory()->create(['role' => User::ROLE_OPERATOR]);
        $washOrder = WashOrder::factory()->create([
            'wash_location_id' => $operator->wash_location_id,
            'status' => WashOrder::STATUS_AWAITING,
        ]);
        $washOrder->teamMembers()->attach($operator);

        RolePermissionSetting::setForLocation((int) $operator->wash_location_id, User::ROLE_OPERATOR, [
            AccessControl::VIEW_WASH_ORDERS => true,
        ]);

        $this->actingAs($operator)->get(route('wash-orders.show', $washOrder))
            ->assertOk()
            ->assertSee('Atualizar status')
            ->assertDontSee('href="'.route('finance.index').'"', false)
            ->assertDontSee('Registrar pagamento')
            ->assertDontSee('Compartilhar via WhatsApp');
    }
}
