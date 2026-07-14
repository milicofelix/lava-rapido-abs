<?php

namespace Tests\Feature\App;

use App\Models\AppSetting;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Models\WashLocation;
use App\Models\WashOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ScheduleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_attendant_can_view_daily_schedule(): void
    {
        Carbon::setTestNow('2026-06-15 08:00:00');

        $attendant = User::factory()->create(['role' => User::ROLE_ATTENDANT]);
        $attendant->washLocation->forceFill([
            'business_hours' => [
                'monday' => ['is_open' => true, 'opens' => '08:00', 'closes' => '18:00'],
            ],
        ])->save();
        $service = Service::factory()->create(['name' => 'Ducha simples']);
        $todayOrder = WashOrder::factory()->create([
            'wash_location_id' => $attendant->wash_location_id,
            'entered_at' => now()->setTime(9, 15),
            'estimated_completion_at' => now()->setTime(10, 0),
            'status' => WashOrder::STATUS_WASHING,
        ]);
        $tomorrowOrder = WashOrder::factory()->create([
            'wash_location_id' => $attendant->wash_location_id,
            'entered_at' => now()->addDay()->setTime(9, 15),
        ]);

        $todayOrder->services()->attach($service, [
            'service_name' => $service->name,
            'price' => 35,
            'estimated_minutes' => 30,
        ]);

        $this->actingAs($attendant)
            ->get(route('schedule.index'))
            ->assertOk()
            ->assertSee('Agenda diaria')
            ->assertSee('Expediente do dia')
            ->assertSee('08:00 às 18:00')
            ->assertSee('Lavagens por horário')
            ->assertSee($todayOrder->vehicle->plate)
            ->assertSee('Ducha simples')
            ->assertDontSee($tomorrowOrder->vehicle->plate);
    }

    public function test_schedule_create_button_prefills_selected_date_opening_time(): void
    {
        Carbon::setTestNow('2026-06-15 08:00:00');

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $admin->washLocation->forceFill([
            'business_hours' => [
                'tuesday' => ['is_open' => true, 'opens' => '09:30', 'closes' => '17:00'],
            ],
        ])->save();

        $this->actingAs($admin)
            ->get(route('schedule.index', ['date' => '2026-06-16']))
            ->assertOk()
            ->assertSee('09:30 às 17:00')
            ->assertSee(route('wash-orders.create', ['scheduled_at' => '2026-06-16T09:30']), false);

        $this->actingAs($admin)
            ->get(route('wash-orders.create', ['scheduled_at' => '2026-06-16T09:30']))
            ->assertOk()
            ->assertSee('value="2026-06-16T09:30"', false);
    }

    public function test_schedule_disables_create_action_when_selected_date_is_closed(): void
    {
        Carbon::setTestNow('2026-06-15 08:00:00');

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $admin->washLocation->forceFill([
            'business_hours' => [
                'tuesday' => ['is_open' => false, 'opens' => '08:00', 'closes' => '18:00'],
            ],
        ])->save();

        $this->actingAs($admin)
            ->get(route('schedule.index', ['date' => '2026-06-16']))
            ->assertOk()
            ->assertSee('Fechado')
            ->assertSee('Data fechada pelo horário de funcionamento')
            ->assertDontSee(route('wash-orders.create', ['scheduled_at' => '2026-06-16T08:00']), false);
    }

    public function test_schedule_can_filter_by_date(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $todayOrder = WashOrder::factory()->create([
            'wash_location_id' => $admin->wash_location_id,
            'entered_at' => now(),
        ]);
        $tomorrowOrder = WashOrder::factory()->create([
            'wash_location_id' => $admin->wash_location_id,
            'entered_at' => now()->addDay()->setTime(8, 0),
        ]);

        $this->actingAs($admin)
            ->get(route('schedule.index', ['date' => now()->addDay()->toDateString()]))
            ->assertOk()
            ->assertSee($tomorrowOrder->vehicle->plate)
            ->assertDontSee($todayOrder->vehicle->plate);
    }

    public function test_schedule_highlights_delayed_open_orders(): void
    {
        Carbon::setTestNow('2026-06-25 14:00:00');

        try {
            $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
            $lateOrder = WashOrder::factory()->create([
                'wash_location_id' => $admin->wash_location_id,
                'entered_at' => now()->setTime(10, 0),
                'estimated_completion_at' => now()->setTime(11, 0),
                'status' => WashOrder::STATUS_WASHING,
            ]);

            $this->actingAs($admin)
                ->get(route('schedule.index'))
                ->assertOk()
                ->assertSee('Atrasadas')
                ->assertSee('Atrasada')
                ->assertSee($lateOrder->vehicle->plate);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_schedule_shows_employee_availability_for_selected_day(): void
    {
        Carbon::setTestNow('2026-06-15 08:00:00');

        $admin = User::factory()->create([
            'name' => 'Ana Administradora',
            'role' => User::ROLE_ADMIN,
        ]);
        $operator = User::factory()->create([
            'name' => 'Bruno Lavador',
            'role' => User::ROLE_OPERATOR,
            'wash_location_id' => $admin->wash_location_id,
        ]);
        User::factory()->create([
            'name' => 'Carla Livre',
            'role' => User::ROLE_OPERATOR,
            'wash_location_id' => $admin->wash_location_id,
        ]);
        $otherLocation = WashLocation::factory()->create();
        User::factory()->create([
            'name' => 'Outra Unidade',
            'role' => User::ROLE_OPERATOR,
            'wash_location_id' => $otherLocation->id,
        ]);
        $assignedOrder = WashOrder::factory()->create([
            'wash_location_id' => $admin->wash_location_id,
            'assigned_user_id' => $operator->id,
            'entered_at' => now()->setTime(9, 0),
            'estimated_completion_at' => now()->setTime(10, 0),
            'status' => WashOrder::STATUS_WASHING,
        ]);
        $assignedOrder->teamMembers()->attach($operator->id);
        WashOrder::factory()->create([
            'wash_location_id' => $admin->wash_location_id,
            'assigned_user_id' => null,
            'entered_at' => now()->setTime(11, 0),
            'estimated_completion_at' => now()->setTime(11, 40),
            'status' => WashOrder::STATUS_AWAITING,
        ]);

        $this->actingAs($admin)
            ->get(route('schedule.index'))
            ->assertOk()
            ->assertSee('Disponibilidade da equipe')
            ->assertSee('Bruno Lavador')
            ->assertSee('Ocupado')
            ->assertSee('Carla Livre')
            ->assertSee('Livre')
            ->assertSee('1 lavagem sem equipe definida')
            ->assertDontSee('Outra Unidade');
    }

    public function test_attendant_can_reschedule_awaiting_wash_order_inside_business_hours(): void
    {
        Carbon::setTestNow('2026-06-15 08:00:00');

        $attendant = User::factory()->create(['role' => User::ROLE_ATTENDANT]);
        $attendant->washLocation->forceFill([
            'business_hours' => [
                'monday' => ['is_open' => true, 'opens' => '08:00', 'closes' => '18:00'],
            ],
        ])->save();
        $washOrder = WashOrder::factory()->create([
            'wash_location_id' => $attendant->wash_location_id,
            'status' => WashOrder::STATUS_AWAITING,
            'payment_status' => WashOrder::PAYMENT_PENDING,
            'entered_at' => '2026-06-15 09:00:00',
            'estimated_completion_at' => '2026-06-15 10:00:00',
        ]);

        $this->actingAs($attendant)
            ->patch(route('schedule.reschedule', $washOrder), [
                'scheduled_at' => '2026-06-15T14:30',
                'reschedule_reason' => 'Cliente pediu outro horario.',
            ])
            ->assertRedirect(route('schedule.index', ['date' => '2026-06-15']));

        $washOrder->refresh();

        $this->assertSame('2026-06-15 14:30', $washOrder->entered_at->format('Y-m-d H:i'));
        $this->assertSame('2026-06-15 15:30', $washOrder->estimated_completion_at->format('Y-m-d H:i'));
        $this->assertDatabaseHas('status_histories', [
            'wash_order_id' => $washOrder->id,
            'from_status' => WashOrder::STATUS_AWAITING,
            'to_status' => WashOrder::STATUS_AWAITING,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'wash_location_id' => $attendant->wash_location_id,
            'action' => 'wash_order.rescheduled',
            'subject_id' => $washOrder->id,
        ]);
    }

    public function test_reschedule_is_blocked_outside_business_hours(): void
    {
        Carbon::setTestNow('2026-06-15 08:00:00');

        $attendant = User::factory()->create(['role' => User::ROLE_ATTENDANT]);
        $attendant->washLocation->forceFill([
            'business_hours' => [
                'monday' => ['is_open' => true, 'opens' => '08:00', 'closes' => '18:00'],
            ],
        ])->save();
        $washOrder = WashOrder::factory()->create([
            'wash_location_id' => $attendant->wash_location_id,
            'status' => WashOrder::STATUS_AWAITING,
            'payment_status' => WashOrder::PAYMENT_PENDING,
            'entered_at' => '2026-06-15 09:00:00',
            'estimated_completion_at' => '2026-06-15 10:00:00',
        ]);

        $this->actingAs($attendant)
            ->from(route('schedule.index'))
            ->patch(route('schedule.reschedule', $washOrder), [
                'scheduled_at' => '2026-06-15T20:00',
            ])
            ->assertRedirect(route('schedule.index'))
            ->assertSessionHasErrors('scheduled_at');

        $this->assertSame('2026-06-15 09:00', $washOrder->refresh()->entered_at->format('Y-m-d H:i'));
    }

    public function test_attendant_can_cancel_awaiting_unpaid_schedule_item(): void
    {
        Carbon::setTestNow('2026-06-15 08:00:00');

        $attendant = User::factory()->create(['role' => User::ROLE_ATTENDANT]);
        $washOrder = WashOrder::factory()->create([
            'wash_location_id' => $attendant->wash_location_id,
            'status' => WashOrder::STATUS_AWAITING,
            'payment_status' => WashOrder::PAYMENT_PENDING,
            'entered_at' => '2026-06-15 09:00:00',
        ]);

        $this->actingAs($attendant)
            ->patch(route('schedule.cancel', $washOrder), [
                'cancel_reason' => 'Cliente desistiu do horario.',
            ])
            ->assertRedirect(route('schedule.index', ['date' => '2026-06-15']));

        $this->assertSame(WashOrder::STATUS_CANCELED, $washOrder->refresh()->status);
        $this->assertDatabaseHas('status_histories', [
            'wash_order_id' => $washOrder->id,
            'from_status' => WashOrder::STATUS_AWAITING,
            'to_status' => WashOrder::STATUS_CANCELED,
            'notes' => 'Cliente desistiu do horario.',
        ]);
    }

    public function test_schedule_cancel_is_blocked_after_payment(): void
    {
        $attendant = User::factory()->create(['role' => User::ROLE_ATTENDANT]);
        $washOrder = WashOrder::factory()->create([
            'wash_location_id' => $attendant->wash_location_id,
            'status' => WashOrder::STATUS_AWAITING,
            'payment_status' => WashOrder::PAYMENT_PAID,
        ]);
        Payment::factory()->create([
            'wash_order_id' => $washOrder->id,
            'user_id' => $attendant->id,
            'amount' => 80,
        ]);

        $this->actingAs($attendant)
            ->from(route('schedule.index'))
            ->patch(route('schedule.cancel', $washOrder), [
                'cancel_reason' => 'Cliente desistiu.',
            ])
            ->assertRedirect(route('schedule.index'))
            ->assertSessionHasErrors('schedule');

        $this->assertSame(WashOrder::STATUS_AWAITING, $washOrder->refresh()->status);
    }

    public function test_operator_cannot_access_schedule(): void
    {
        $operator = User::factory()->create(['role' => User::ROLE_OPERATOR]);

        $this->actingAs($operator)
            ->get(route('schedule.index'))
            ->assertForbidden();
    }

    public function test_schedule_route_is_blocked_when_module_is_disabled(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        AppSetting::setValue('module_schedule', false);

        $this->actingAs($admin)
            ->get(route('schedule.index'))
            ->assertForbidden();
    }
}
