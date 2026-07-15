<?php

namespace Tests\Feature\App;

use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Models\WashOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WashOrderReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_printable_wash_order_receipt(): void
    {
        $user = User::factory()->create();
        $washOrder = WashOrder::factory()->create([
            'code' => 'ABS-RECIBO-1',
            'total_amount' => 90,
            'payment_status' => WashOrder::PAYMENT_PAID,
        ]);
        $service = Service::factory()->create([
            'name' => 'Lavagem completa',
            'base_price' => 90,
            'estimated_minutes' => 60,
        ]);

        $washOrder->services()->attach($service, [
            'service_name' => $service->name,
            'price' => $service->base_price,
            'estimated_minutes' => $service->estimated_minutes,
        ]);
        $washOrder->payments()->create([
            'user_id' => $user->id,
            'method' => Payment::METHOD_PIX,
            'amount' => 90,
            'paid_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('wash-orders.receipt', $washOrder))
            ->assertOk()
            ->assertSee('Recibo de lavagem')
            ->assertSee('ABS-RECIBO-1')
            ->assertSee($washOrder->customer->name)
            ->assertSee($washOrder->vehicle->plate)
            ->assertSee('Lavagem completa')
            ->assertSee('R$ 90,00')
            ->assertSee('Imprimir recibo');
    }

    public function test_wash_order_detail_shows_receipt_button(): void
    {
        $user = User::factory()->create();
        $washOrder = WashOrder::factory()->create();

        $this->actingAs($user)
            ->get(route('wash-orders.show', $washOrder))
            ->assertOk()
            ->assertSee('Recibo')
            ->assertSee('Imprimir recibo')
            ->assertSee(route('wash-orders.receipt', $washOrder))
            ->assertSee('data-onboarding-tour', false)
            ->assertSee('wash-orders.show.v1')
            ->assertSee('data-tour="wash-detail-summary"', false)
            ->assertSee('data-tour="wash-detail-receipt"', false)
            ->assertSee('Detalhes da lavagem');
    }
}
