<?php

namespace Tests\Unit;

use App\Events\WashOrderStatusChanged;
use App\Models\User;
use App\Models\WashOrder;
use App\Services\WashOrders\ChangeWashOrderStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ChangeWashOrderStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_changes_status_and_records_history(): void
    {
        Event::fake([WashOrderStatusChanged::class]);

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
        Event::assertDispatched(WashOrderStatusChanged::class, fn (WashOrderStatusChanged $event) => $event->washOrder->id === $washOrder->id
            && $event->fromStatus === WashOrder::STATUS_AWAITING
            && $event->washOrder->status === WashOrder::STATUS_READY);
    }
}
