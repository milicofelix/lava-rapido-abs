<?php

namespace Tests\Feature\App;

use App\Models\User;
use App\Models\WashOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WashKanbanTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_user_can_see_operational_kanban_columns_and_cards(): void
    {
        $user = User::factory()->create();
        $washOrder = WashOrder::factory()->create([
            'status' => WashOrder::STATUS_WASHING,
            'total_amount' => 80,
        ]);

        $this->actingAs($user)->get(route('kanban'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Kanban')
                ->has('columns', 5)
                ->where('columns.1.title', 'Em lavagem')
                ->where('columns.1.orders.0.vehicle.plate', $washOrder->vehicle->plate)
                ->where('columns.1.orders.0.customer.name', $washOrder->customer->name)
                ->where('columns.1.orders.0.can_update_status', false)
                ->where('logoutUrl', route('logout'))
                ->where('onboardingTour.key', 'kanban.operational.v1')
                ->where('onboardingTour.steps.0.title', 'Painel operacional')
                ->where('onboardingTour.steps.4.target', '[data-tour="kanban-board"]')
                ->has('csrfToken')
            );
    }

    public function test_kanban_opens_with_today_orders_only(): void
    {
        Carbon::setTestNow('2026-06-17 10:00:00');

        $user = User::factory()->create();
        $todayOrder = WashOrder::factory()->create([
            'status' => WashOrder::STATUS_WASHING,
            'entered_at' => now(),
        ]);
        WashOrder::factory()->create([
            'status' => WashOrder::STATUS_DELIVERED,
            'entered_at' => now()->subDay(),
        ]);
        WashOrder::factory()->create([
            'status' => WashOrder::STATUS_AWAITING,
            'entered_at' => now()->subDays(4),
        ]);

        $this->actingAs($user)->get(route('kanban'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Kanban')
                ->where('filters.period', 'today')
                ->where('filters.show_outside_day_badge', false)
                ->has('columns.1.orders', 1)
                ->where('columns.1.orders.0.id', $todayOrder->id)
                ->has('columns.4.orders', 0)
                ->has('columns.0.orders', 0)
            );

        Carbon::setTestNow();
    }

    public function test_kanban_can_query_previous_periods_and_marks_orders_outside_today(): void
    {
        Carbon::setTestNow('2026-06-17 10:00:00');

        $user = User::factory()->create();
        $oldOrder = WashOrder::factory()->create([
            'status' => WashOrder::STATUS_AWAITING,
            'entered_at' => now()->subDays(3),
        ]);

        $this->actingAs($user)->get(route('kanban', ['period' => '7_days']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Kanban')
                ->where('filters.period', '7_days')
                ->where('filters.show_outside_day_badge', true)
                ->has('columns.0.orders', 1)
                ->where('columns.0.orders.0.id', $oldOrder->id)
                ->where('columns.0.orders.0.is_outside_today', true)
                ->where('columns.0.orders.0.entered_at_date_label', '14/06/2026')
            );

        Carbon::setTestNow();
    }

    public function test_kanban_feed_respects_date_filter(): void
    {
        Carbon::setTestNow('2026-06-17 10:00:00');

        $user = User::factory()->create();
        $oldOrder = WashOrder::factory()->create([
            'status' => WashOrder::STATUS_READY,
            'entered_at' => now()->subDay(),
        ]);
        WashOrder::factory()->create([
            'status' => WashOrder::STATUS_READY,
            'entered_at' => now(),
        ]);

        $this->actingAs($user)->getJson(route('kanban.feed', [
            'period' => 'date',
            'date' => '2026-06-16',
        ]))->assertOk()
            ->assertJsonPath('filters.period', 'date')
            ->assertJsonPath('columns.3.orders.0.id', $oldOrder->id)
            ->assertJsonCount(1, 'columns.3.orders');

        Carbon::setTestNow();
    }

    public function test_user_can_move_card_to_next_status_from_kanban_action(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OPERATOR]);
        $washOrder = WashOrder::factory()->create(['status' => WashOrder::STATUS_AWAITING]);
        $washOrder->teamMembers()->attach($user);

        $this->actingAs($user)->patch(route('wash-orders.update-status', $washOrder), [
            'status' => WashOrder::STATUS_WASHING,
            'notes' => 'Status atualizado pelo Kanban.',
        ])->assertRedirect();

        $this->assertSame(WashOrder::STATUS_WASHING, $washOrder->refresh()->status);
        $this->assertDatabaseHas('status_histories', [
            'wash_order_id' => $washOrder->id,
            'from_status' => WashOrder::STATUS_AWAITING,
            'to_status' => WashOrder::STATUS_WASHING,
            'notes' => 'Status atualizado pelo Kanban.',
        ]);
    }

    public function test_operator_cannot_move_card_when_not_on_team(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OPERATOR]);
        $washOrder = WashOrder::factory()->create(['status' => WashOrder::STATUS_AWAITING]);

        $this->actingAs($user)->patch(route('wash-orders.update-status', $washOrder), [
            'status' => WashOrder::STATUS_WASHING,
            'notes' => 'Status atualizado pelo Kanban.',
        ])->assertSessionHasErrors('status');

        $this->assertSame(WashOrder::STATUS_AWAITING, $washOrder->refresh()->status);

        $this->actingAs($user)->get(route('kanban'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('columns.0.orders.0.id', $washOrder->id)
                ->where('columns.0.orders.0.can_update_status', false)
                ->where('createUrl', null)
                ->where('logoutUrl', route('logout'))
            );
    }

    public function test_kanban_hides_create_action_when_location_is_closed(): void
    {
        Carbon::setTestNow('2026-06-15 10:00:00');

        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user->washLocation->forceFill([
            'business_hours' => [
                'monday' => ['is_open' => false, 'opens' => '08:00', 'closes' => '18:00'],
            ],
        ])->save();

        $this->actingAs($user)->get(route('kanban'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Kanban')
                ->where('createUrl', null)
            );
    }

    public function test_kanban_disables_status_actions_when_location_is_closed_by_business_hours(): void
    {
        Carbon::setTestNow('2026-06-15 20:00:00');

        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user->washLocation->forceFill([
            'business_hours' => [
                'monday' => ['is_open' => true, 'opens' => '08:00', 'closes' => '18:00'],
            ],
        ])->save();
        $washOrder = WashOrder::factory()->create([
            'wash_location_id' => $user->wash_location_id,
            'status' => WashOrder::STATUS_AWAITING,
            'entered_at' => now()->subHours(2),
        ]);

        $this->actingAs($user)->get(route('kanban'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Kanban')
                ->where('columns.0.orders.0.id', $washOrder->id)
                ->where('columns.0.orders.0.can_update_status', false)
            );
    }
}
