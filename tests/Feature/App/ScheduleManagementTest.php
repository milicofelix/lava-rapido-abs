<?php

namespace Tests\Feature\App;

use App\Models\AppSetting;
use App\Models\Service;
use App\Models\User;
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
