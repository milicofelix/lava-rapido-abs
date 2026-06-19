<?php

namespace Tests\Feature\App;

use App\Models\AppSetting;
use App\Models\Service;
use App\Models\User;
use App\Models\WashOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendant_can_view_daily_schedule(): void
    {
        $attendant = User::factory()->create(['role' => User::ROLE_ATTENDANT]);
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
            ->assertSee($todayOrder->vehicle->plate)
            ->assertSee('Ducha simples')
            ->assertDontSee($tomorrowOrder->vehicle->plate);
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
