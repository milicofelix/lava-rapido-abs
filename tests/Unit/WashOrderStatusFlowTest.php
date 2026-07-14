<?php

namespace Tests\Unit;

use App\Models\WashOrder;
use App\Support\WashOrders\WashOrderStatusFlow;
use Tests\TestCase;

class WashOrderStatusFlowTest extends TestCase
{
    public function test_it_exposes_wash_order_status_labels(): void
    {
        $this->assertSame([
            WashOrder::STATUS_AWAITING => 'Aguardando',
            WashOrder::STATUS_PREPARING => 'Em preparacao',
            WashOrder::STATUS_WASHING => 'Lavando',
            WashOrder::STATUS_VACUUMING => 'Aspirando',
            WashOrder::STATUS_WAXING => 'Aplicando cera',
            WashOrder::STATUS_FINISHING => 'Finalizando',
            WashOrder::STATUS_READY => 'Pronto para retirada',
            WashOrder::STATUS_DELIVERED => 'Entregue',
            WashOrder::STATUS_CANCELED => 'Cancelado',
        ], WashOrderStatusFlow::labels());
    }

    public function test_it_identifies_active_public_and_completion_statuses(): void
    {
        $this->assertSame([
            WashOrder::STATUS_AWAITING,
            WashOrder::STATUS_PREPARING,
            WashOrder::STATUS_WASHING,
            WashOrder::STATUS_VACUUMING,
            WashOrder::STATUS_WAXING,
            WashOrder::STATUS_FINISHING,
            WashOrder::STATUS_READY,
        ], array_values(WashOrderStatusFlow::activeStatuses()));

        $this->assertSame([
            WashOrder::STATUS_AWAITING,
            WashOrder::STATUS_PREPARING,
            WashOrder::STATUS_WASHING,
            WashOrder::STATUS_VACUUMING,
            WashOrder::STATUS_WAXING,
            WashOrder::STATUS_FINISHING,
            WashOrder::STATUS_READY,
        ], WashOrderStatusFlow::publicProgressStatuses());

        $this->assertTrue(WashOrderStatusFlow::isCompletionStatus(WashOrder::STATUS_READY));
        $this->assertTrue(WashOrderStatusFlow::isCompletionStatus(WashOrder::STATUS_DELIVERED));
        $this->assertTrue(WashOrderStatusFlow::isCompletionStatus(WashOrder::STATUS_CANCELED));
        $this->assertFalse(WashOrderStatusFlow::isCompletionStatus(WashOrder::STATUS_WASHING));
    }

    public function test_it_controls_allowed_status_transitions(): void
    {
        $this->assertTrue(WashOrderStatusFlow::canTransition(WashOrder::STATUS_AWAITING, WashOrder::STATUS_WASHING));
        $this->assertTrue(WashOrderStatusFlow::canTransition(WashOrder::STATUS_WASHING, WashOrder::STATUS_FINISHING));
        $this->assertTrue(WashOrderStatusFlow::canTransition(WashOrder::STATUS_FINISHING, WashOrder::STATUS_READY));
        $this->assertTrue(WashOrderStatusFlow::canTransition(WashOrder::STATUS_READY, WashOrder::STATUS_DELIVERED));
        $this->assertTrue(WashOrderStatusFlow::canTransition(WashOrder::STATUS_AWAITING, WashOrder::STATUS_CANCELED));

        $this->assertFalse(WashOrderStatusFlow::canTransition(WashOrder::STATUS_AWAITING, WashOrder::STATUS_DELIVERED));
        $this->assertFalse(WashOrderStatusFlow::canTransition(WashOrder::STATUS_WASHING, WashOrder::STATUS_CANCELED));
        $this->assertFalse(WashOrderStatusFlow::canTransition(WashOrder::STATUS_READY, WashOrder::STATUS_CANCELED));
        $this->assertFalse(WashOrderStatusFlow::canTransition(WashOrder::STATUS_DELIVERED, WashOrder::STATUS_WASHING));
    }

    public function test_model_status_methods_still_delegate_to_current_flow(): void
    {
        $this->assertSame(WashOrderStatusFlow::labels(), WashOrder::statuses());
        $this->assertSame(WashOrderStatusFlow::activeStatuses(), WashOrder::activeStatuses());
        $this->assertSame(WashOrderStatusFlow::publicProgressStatuses(), WashOrder::publicProgressStatuses());
    }
}
