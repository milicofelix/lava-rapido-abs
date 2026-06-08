<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\WashOrder;
use App\Services\WashOrders\ChangeWashOrderStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChangeWashOrderStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_changes_status_and_records_history(): void
    {
        $user = User::factory()->create();
        $washOrder = WashOrder::factory()->create(['status' => WashOrder::STATUS_AWAITING]);

        app(ChangeWashOrderStatusService::class)->handle($washOrder, WashOrder::STATUS_READY, $user, 'Pronto no patio.');

        $this->assertSame(WashOrder::STATUS_READY, $washOrder->refresh()->status);
        $this->assertNotNull($washOrder->completed_at);
        $this->assertDatabaseHas('status_histories', [
            'wash_order_id' => $washOrder->id,
            'user_id' => $user->id,
            'from_status' => WashOrder::STATUS_AWAITING,
            'to_status' => WashOrder::STATUS_READY,
            'notes' => 'Pronto no patio.',
        ]);
    }
}
