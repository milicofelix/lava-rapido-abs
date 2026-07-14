<?php

namespace Tests\Unit;

use App\Events\WashOrderStatusChanged;
use App\Models\User;
use App\Models\WashOrder;
use App\Services\WashOrders\ChangeWashOrderStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Tests\TestCase;

class ChangeWashOrderStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_changes_status_and_records_history(): void
    {
        Event::fake([WashOrderStatusChanged::class]);

        $user = User::factory()->create();
        $washOrder = WashOrder::factory()->create(['status' => WashOrder::STATUS_FINISHING]);

        app(ChangeWashOrderStatusService::class)->handle($washOrder, WashOrder::STATUS_READY, $user, 'Pronto no patio.');

        $this->assertSame(WashOrder::STATUS_READY, $washOrder->refresh()->status);
        $this->assertNotNull($washOrder->completed_at);
        $this->assertDatabaseHas('status_histories', [
            'wash_order_id' => $washOrder->id,
            'user_id' => $user->id,
            'from_status' => WashOrder::STATUS_FINISHING,
            'to_status' => WashOrder::STATUS_READY,
            'notes' => 'Pronto no patio.',
        ]);
        Event::assertDispatched(WashOrderStatusChanged::class, fn (WashOrderStatusChanged $event) => $event->washOrder->id === $washOrder->id
            && $event->fromStatus === WashOrder::STATUS_FINISHING
            && $event->washOrder->status === WashOrder::STATUS_READY);
    }

    public function test_it_rejects_invalid_status_transition(): void
    {
        Event::fake([WashOrderStatusChanged::class]);

        $user = User::factory()->create();
        $washOrder = WashOrder::factory()->create(['status' => WashOrder::STATUS_AWAITING]);

        try {
            app(ChangeWashOrderStatusService::class)->handle($washOrder, WashOrder::STATUS_DELIVERED, $user);
            $this->fail('A transicao invalida deveria ter sido bloqueada.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Transição de status não permitida.', $exception->getMessage());
        }

        $this->assertSame(WashOrder::STATUS_AWAITING, $washOrder->refresh()->status);
        Event::assertNotDispatched(WashOrderStatusChanged::class);
    }

    public function test_it_rejects_cancellation_after_payment_is_identified(): void
    {
        Event::fake([WashOrderStatusChanged::class]);

        $user = User::factory()->create();
        $washOrder = WashOrder::factory()->create([
            'status' => WashOrder::STATUS_AWAITING,
            'payment_status' => WashOrder::PAYMENT_PAID,
        ]);

        try {
            app(ChangeWashOrderStatusService::class)->handle($washOrder, WashOrder::STATUS_CANCELED, $user);
            $this->fail('A lavagem paga não deveria ser cancelada.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('A lavagem só pode ser cancelada enquanto estiver aguardando e sem pagamento registrado.', $exception->getMessage());
        }

        $this->assertSame(WashOrder::STATUS_AWAITING, $washOrder->refresh()->status);
        Event::assertNotDispatched(WashOrderStatusChanged::class);
    }

    public function test_it_rejects_cancellation_after_operation_started(): void
    {
        Event::fake([WashOrderStatusChanged::class]);

        $user = User::factory()->create();
        $washOrder = WashOrder::factory()->create(['status' => WashOrder::STATUS_WASHING]);

        try {
            app(ChangeWashOrderStatusService::class)->handle($washOrder, WashOrder::STATUS_CANCELED, $user);
            $this->fail('A lavagem em andamento não deveria ser cancelada.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('A lavagem só pode ser cancelada enquanto estiver aguardando e sem pagamento registrado.', $exception->getMessage());
        }

        $this->assertSame(WashOrder::STATUS_WASHING, $washOrder->refresh()->status);
        Event::assertNotDispatched(WashOrderStatusChanged::class);
    }
}
