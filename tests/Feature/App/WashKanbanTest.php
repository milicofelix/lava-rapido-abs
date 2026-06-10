<?php

namespace Tests\Feature\App;

use App\Models\User;
use App\Models\WashOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WashKanbanTest extends TestCase
{
    use RefreshDatabase;

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
            );
    }

    public function test_user_can_move_card_to_next_status_from_kanban_action(): void
    {
        $user = User::factory()->create();
        $washOrder = WashOrder::factory()->create(['status' => WashOrder::STATUS_AWAITING]);

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
}
