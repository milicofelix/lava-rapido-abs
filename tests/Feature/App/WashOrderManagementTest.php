<?php

namespace Tests\Feature\App;

use App\Models\AppSetting;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\RolePermissionSetting;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WashOrder;
use App\Support\Access\AccessControl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WashOrderManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_create_form_embeds_customer_vehicle_mapping(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['name' => 'Maria Cliente']);
        $otherCustomer = Customer::factory()->create(['name' => 'Outro Cliente']);
        $vehicle = Vehicle::factory()->for($customer)->create([
            'plate' => 'ABC1D23',
            'brand' => 'Toyota',
            'model' => 'Corolla',
        ]);
        $otherVehicle = Vehicle::factory()->for($otherCustomer)->create([
            'plate' => 'XYZ9K88',
            'brand' => 'Hyundai',
            'model' => 'HB20',
        ]);

        $response = $this->actingAs($user)->get(route('wash-orders.create'));

        $response
            ->assertOk()
            ->assertSee('data-customer-select', false)
            ->assertSee('data-vehicle-select', false)
            ->assertSee('data-onboarding-tour', false)
            ->assertSee('wash-orders.create.v1')
            ->assertSee('data-tour="wash-customer"', false)
            ->assertSee('data-tour="wash-summary"', false)
            ->assertSee('Comece pelo cliente')
            ->assertSee('assigned_user_ids[]', false)
            ->assertSee('Equipe da lavagem')
            ->assertSee('name="scheduled_at"', false)
            ->assertSee('Agendar para')
            ->assertSee('Selecione um cliente primeiro')
            ->assertSee((string) $customer->id)
            ->assertSee((string) $vehicle->id)
            ->assertSee('ABC1D23')
            ->assertSee('Toyota Corolla')
            ->assertSee((string) $otherCustomer->id)
            ->assertSee((string) $otherVehicle->id)
            ->assertSee('Hyundai HB20');
    }

    public function test_attendant_can_open_wash_order_with_selected_services(): void
    {
        $user = User::factory()->create();
        $teammate = User::factory()->create(['name' => 'Colega Lavador']);
        $customer = Customer::factory()->create();
        $vehicle = Vehicle::factory()->for($customer)->create(['plate' => 'ABC1D23']);
        $services = Service::factory()->count(2)->sequence(
            ['name' => 'Lavagem completa', 'base_price' => 80, 'estimated_minutes' => 70],
            ['name' => 'Cera', 'base_price' => 45, 'estimated_minutes' => 35],
        )->create(['active' => true]);

        $this->actingAs($user)->post(route('wash-orders.store'), [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'assigned_user_ids' => [$user->id, $teammate->id],
            'service_ids' => $services->pluck('id')->all(),
            'notes' => 'Cliente vai buscar no fim da tarde.',
        ])->assertRedirect();

        $washOrder = WashOrder::query()->firstOrFail();

        $this->assertSame('125.00', (string) $washOrder->total_amount);
        $this->assertSame($user->id, $washOrder->assigned_user_id);
        $this->assertSame(WashOrder::STATUS_AWAITING, $washOrder->status);
        $this->assertCount(2, $washOrder->services);
        $this->assertDatabaseHas('user_wash_order', [
            'wash_order_id' => $washOrder->id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('user_wash_order', [
            'wash_order_id' => $washOrder->id,
            'user_id' => $teammate->id,
        ]);
        $this->assertDatabaseHas('status_histories', [
            'wash_order_id' => $washOrder->id,
            'from_status' => null,
            'to_status' => WashOrder::STATUS_AWAITING,
        ]);
    }

    public function test_canceled_status_is_hidden_after_payment_is_identified(): void
    {
        $user = User::factory()->create();
        $washOrder = WashOrder::factory()->create([
            'wash_location_id' => $user->wash_location_id,
            'status' => WashOrder::STATUS_AWAITING,
            'payment_status' => WashOrder::PAYMENT_PAID,
        ]);
        Payment::factory()->create([
            'wash_order_id' => $washOrder->id,
            'user_id' => $user->id,
            'amount' => 80,
        ]);

        $this->actingAs($user)
            ->get(route('wash-orders.show', $washOrder))
            ->assertOk()
            ->assertDontSee('<option value="'.WashOrder::STATUS_CANCELED.'"', false);
    }

    public function test_canceled_status_is_hidden_after_operation_started(): void
    {
        $user = User::factory()->create();
        $washOrder = WashOrder::factory()->create([
            'wash_location_id' => $user->wash_location_id,
            'status' => WashOrder::STATUS_WASHING,
            'payment_status' => WashOrder::PAYMENT_PENDING,
        ]);

        $this->actingAs($user)
            ->get(route('wash-orders.show', $washOrder))
            ->assertOk()
            ->assertDontSee('<option value="'.WashOrder::STATUS_CANCELED.'"', false);
    }

    public function test_wash_order_opening_is_blocked_when_location_is_closed_by_business_hours(): void
    {
        Carbon::setTestNow('2026-06-15 10:00:00');

        $user = User::factory()->create();
        $user->washLocation->forceFill([
            'business_hours' => [
                'monday' => ['is_open' => false, 'opens' => '08:00', 'closes' => '18:00'],
            ],
        ])->save();
        $customer = Customer::factory()->create(['wash_location_id' => $user->wash_location_id]);
        $vehicle = Vehicle::factory()->for($customer)->create(['wash_location_id' => $user->wash_location_id]);
        $service = Service::factory()->create([
            'wash_location_id' => $user->wash_location_id,
            'active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('wash-orders.create'))
            ->assertOk()
            ->assertSee('Abertura imediata indisponível')
            ->assertSee('fora do horário de funcionamento');

        $this->actingAs($user)->post(route('wash-orders.store'), [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'service_ids' => [$service->id],
        ])->assertSessionHasErrors('wash_order');

        $this->assertDatabaseCount('wash_orders', 0);
    }

    public function test_scheduled_wash_order_is_blocked_outside_business_hours(): void
    {
        Carbon::setTestNow('2026-06-15 10:00:00');

        $user = User::factory()->create();
        $user->washLocation->forceFill([
            'business_hours' => [
                'monday' => ['is_open' => true, 'opens' => '08:00', 'closes' => '18:00'],
            ],
        ])->save();
        $customer = Customer::factory()->create(['wash_location_id' => $user->wash_location_id]);
        $vehicle = Vehicle::factory()->for($customer)->create(['wash_location_id' => $user->wash_location_id]);
        $service = Service::factory()->create([
            'wash_location_id' => $user->wash_location_id,
            'active' => true,
        ]);

        $this->actingAs($user)->post(route('wash-orders.store'), [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'service_ids' => [$service->id],
            'scheduled_at' => '2026-06-15T20:00',
        ])->assertSessionHasErrors('scheduled_at');

        $this->assertDatabaseCount('wash_orders', 0);
    }

    public function test_attendant_can_schedule_wash_order_for_future_date(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['wash_location_id' => $user->wash_location_id]);
        $vehicle = Vehicle::factory()->for($customer)->create([
            'wash_location_id' => $user->wash_location_id,
            'plate' => 'SCH1D23',
        ]);
        $service = Service::factory()->create([
            'wash_location_id' => $user->wash_location_id,
            'active' => true,
            'name' => 'Ducha agendada',
            'estimated_minutes' => 45,
        ]);

        $scheduledAt = now()->addDay()->setTime(14, 30);

        $this->actingAs($user)->post(route('wash-orders.store'), [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'service_ids' => [$service->id],
            'scheduled_at' => $scheduledAt->format('Y-m-d\TH:i'),
        ])->assertRedirect();

        $washOrder = WashOrder::query()->firstOrFail();

        $this->assertSame($scheduledAt->format('Y-m-d H:i'), $washOrder->entered_at->format('Y-m-d H:i'));
        $this->assertSame($scheduledAt->copy()->addMinutes(45)->format('Y-m-d H:i'), $washOrder->estimated_completion_at->format('Y-m-d H:i'));

        $this->actingAs($user)
            ->get(route('schedule.index'))
            ->assertOk()
            ->assertDontSee('SCH1D23');

        $this->actingAs($user)
            ->get(route('schedule.index', ['date' => $scheduledAt->toDateString()]))
            ->assertOk()
            ->assertSee('SCH1D23')
            ->assertSee('Ducha agendada');
    }

    public function test_schedule_field_is_hidden_and_ignored_when_module_is_disabled(): void
    {
        AppSetting::setValue('module_schedule', false);

        $user = User::factory()->create();
        $customer = Customer::factory()->create(['wash_location_id' => $user->wash_location_id]);
        $vehicle = Vehicle::factory()->for($customer)->create([
            'wash_location_id' => $user->wash_location_id,
            'plate' => 'NOW1D23',
        ]);
        $service = Service::factory()->create([
            'wash_location_id' => $user->wash_location_id,
            'active' => true,
            'name' => 'Ducha agora',
        ]);

        $this->actingAs($user)
            ->get(route('wash-orders.create'))
            ->assertOk()
            ->assertDontSee('name="scheduled_at"', false)
            ->assertDontSee('Agendar para');

        $future = now()->addDays(5)->setTime(14, 30);

        $this->actingAs($user)->post(route('wash-orders.store'), [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'service_ids' => [$service->id],
            'scheduled_at' => $future->format('Y-m-d\TH:i'),
        ])->assertRedirect();

        $washOrder = WashOrder::query()->firstOrFail();

        $this->assertTrue($washOrder->entered_at->isToday());
    }

    public function test_operator_can_change_wash_order_status(): void
    {
        $user = User::factory()->create(['role' => 'operator']);
        $washOrder = WashOrder::factory()->create();
        $washOrder->teamMembers()->attach($user);

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

    public function test_wash_order_status_cannot_advance_outside_business_hours(): void
    {
        Carbon::setTestNow('2026-06-15 20:00:00');

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $admin->washLocation->forceFill([
            'business_hours' => [
                'monday' => ['is_open' => true, 'opens' => '08:00', 'closes' => '18:00'],
            ],
        ])->save();
        $washOrder = WashOrder::factory()->create([
            'wash_location_id' => $admin->wash_location_id,
            'status' => WashOrder::STATUS_AWAITING,
            'entered_at' => now()->subHours(2),
        ]);

        $this->actingAs($admin)->get(route('wash-orders.show', $washOrder))
            ->assertOk()
            ->assertDontSee('Atualizar status')
            ->assertSee('A unidade está fechada agora. Avance etapas somente dentro do horário de funcionamento.');

        $this->actingAs($admin)->patch(route('wash-orders.update-status', $washOrder), [
            'status' => WashOrder::STATUS_WASHING,
        ])->assertSessionHasErrors('status');

        $this->assertSame(WashOrder::STATUS_AWAITING, $washOrder->refresh()->status);
        $this->assertDatabaseMissing('status_histories', [
            'wash_order_id' => $washOrder->id,
            'from_status' => WashOrder::STATUS_AWAITING,
            'to_status' => WashOrder::STATUS_WASHING,
        ]);
    }

    public function test_operator_cannot_change_status_when_not_on_wash_order_team(): void
    {
        $operator = User::factory()->create(['role' => User::ROLE_OPERATOR]);
        $washOrder = WashOrder::factory()->create(['status' => WashOrder::STATUS_AWAITING]);
        RolePermissionSetting::setForLocation((int) $operator->wash_location_id, User::ROLE_OPERATOR, [
            AccessControl::VIEW_WASH_ORDERS => true,
        ]);

        $this->actingAs($operator)->get(route('wash-orders.show', $washOrder))
            ->assertOk()
            ->assertDontSee('Atualizar status')
            ->assertSee('Status restrito a responsáveis da equipe desta lavagem.');

        $this->actingAs($operator)->patch(route('wash-orders.update-status', $washOrder), [
            'status' => WashOrder::STATUS_WASHING,
        ])->assertSessionHasErrors('status');

        $this->assertSame(WashOrder::STATUS_AWAITING, $washOrder->refresh()->status);
        $this->assertDatabaseMissing('user_wash_order', [
            'wash_order_id' => $washOrder->id,
            'user_id' => $operator->id,
        ]);
    }

    public function test_wash_order_cannot_be_delivered_without_identified_payment(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $washOrder = WashOrder::factory()->create([
            'status' => WashOrder::STATUS_READY,
            'payment_status' => WashOrder::PAYMENT_PENDING,
        ]);

        $this->actingAs($admin)->patch(route('wash-orders.update-status', $washOrder), [
            'status' => WashOrder::STATUS_DELIVERED,
        ])->assertSessionHasErrors('status');

        $this->assertSame(WashOrder::STATUS_READY, $washOrder->refresh()->status);
    }

    public function test_wash_order_can_be_delivered_with_identified_payment(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $washOrder = WashOrder::factory()->create([
            'status' => WashOrder::STATUS_READY,
            'payment_status' => WashOrder::PAYMENT_PAID,
        ]);

        $this->actingAs($admin)->patch(route('wash-orders.update-status', $washOrder), [
            'status' => WashOrder::STATUS_DELIVERED,
        ])->assertRedirect();

        $this->assertSame(WashOrder::STATUS_DELIVERED, $washOrder->refresh()->status);
    }

    public function test_invalid_wash_order_status_transition_returns_validation_error(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OPERATOR]);
        $washOrder = WashOrder::factory()->create(['status' => WashOrder::STATUS_AWAITING]);
        $washOrder->teamMembers()->attach($user);

        $this->actingAs($user)->patch(route('wash-orders.update-status', $washOrder), [
            'status' => WashOrder::STATUS_DELIVERED,
            'notes' => 'Pulando fluxo.',
        ])->assertSessionHasErrors('status');

        $this->assertSame(WashOrder::STATUS_AWAITING, $washOrder->refresh()->status);
        $this->assertDatabaseMissing('status_histories', [
            'wash_order_id' => $washOrder->id,
            'from_status' => WashOrder::STATUS_AWAITING,
            'to_status' => WashOrder::STATUS_DELIVERED,
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
