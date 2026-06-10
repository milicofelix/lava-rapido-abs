<?php

namespace Tests\Feature\App;

use App\Models\Payment;
use App\Models\User;
use App\Models\WashOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendant_can_register_a_pix_payment_for_a_wash_order(): void
    {
        $user = User::factory()->create();
        $washOrder = WashOrder::factory()->create(['total_amount' => 80]);

        $this->actingAs($user)->post(route('payments.store', $washOrder), [
            'method' => Payment::METHOD_PIX,
            'amount' => 80,
            'notes' => 'Pago no balcao.',
        ])->assertRedirect();

        $washOrder->refresh();

        $this->assertSame(WashOrder::PAYMENT_PAID, $washOrder->payment_status);
        $this->assertDatabaseHas('payments', [
            'wash_order_id' => $washOrder->id,
            'user_id' => $user->id,
            'method' => Payment::METHOD_PIX,
            'amount' => 80,
            'notes' => 'Pago no balcao.',
        ]);
    }

    public function test_courtesy_payment_marks_order_as_courtesy_with_zero_amount(): void
    {
        $user = User::factory()->create();
        $washOrder = WashOrder::factory()->create(['total_amount' => 120]);

        $this->actingAs($user)->post(route('payments.store', $washOrder), [
            'method' => Payment::METHOD_COURTESY,
            'amount' => 120,
        ])->assertRedirect();

        $payment = Payment::query()->firstOrFail();
        $washOrder->refresh();

        $this->assertSame(WashOrder::PAYMENT_COURTESY, $washOrder->payment_status);
        $this->assertSame('0.00', (string) $payment->amount);
    }

    public function test_credit_pending_payment_marks_order_as_credit_pending(): void
    {
        $user = User::factory()->create();
        $washOrder = WashOrder::factory()->create(['total_amount' => 70]);

        $this->actingAs($user)->post(route('payments.store', $washOrder), [
            'method' => Payment::METHOD_CREDIT_PENDING,
        ])->assertRedirect();

        $payment = Payment::query()->firstOrFail();
        $washOrder->refresh();

        $this->assertSame(WashOrder::PAYMENT_CREDIT_PENDING, $washOrder->payment_status);
        $this->assertSame(Payment::METHOD_CREDIT_PENDING, $payment->method);
        $this->assertSame('0.00', (string) $payment->amount);
    }
}
