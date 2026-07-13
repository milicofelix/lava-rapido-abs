<?php

namespace Tests\Feature\App;

use App\Models\LoyaltyCoupon;
use App\Models\LoyaltyProgram;
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

    public function test_courtesy_payment_requires_an_applied_loyalty_coupon(): void
    {
        $user = User::factory()->create();
        $washOrder = WashOrder::factory()->create(['total_amount' => 120]);

        $this->actingAs($user)
            ->from(route('wash-orders.show', $washOrder))
            ->post(route('payments.store', $washOrder), [
                'method' => Payment::METHOD_COURTESY,
                'amount' => 120,
            ])
            ->assertRedirect(route('wash-orders.show', $washOrder))
            ->assertSessionHasErrors('method');

        $this->assertDatabaseCount('payments', 0);
        $this->assertSame(WashOrder::PAYMENT_PENDING, $washOrder->refresh()->payment_status);
    }

    public function test_courtesy_option_is_hidden_when_order_has_no_applied_coupon(): void
    {
        $user = User::factory()->create();
        $washOrder = WashOrder::factory()->create([
            'wash_location_id' => $user->wash_location_id,
            'payment_status' => WashOrder::PAYMENT_PENDING,
            'total_amount' => 120,
        ]);

        $this->actingAs($user)
            ->get(route('wash-orders.show', $washOrder))
            ->assertOk()
            ->assertDontSee('Cortesia');
    }

    public function test_courtesy_payment_marks_order_as_courtesy_when_coupon_covers_total_amount(): void
    {
        $user = User::factory()->create();
        $washOrder = WashOrder::factory()->create([
            'wash_location_id' => $user->wash_location_id,
            'total_amount' => 120,
            'loyalty_discount_amount' => 120,
        ]);
        $program = LoyaltyProgram::query()->create([
            'wash_location_id' => $user->wash_location_id,
            'is_active' => true,
        ]);
        $coupon = LoyaltyCoupon::query()->create([
            'wash_location_id' => $user->wash_location_id,
            'loyalty_program_id' => $program->id,
            'customer_id' => $washOrder->customer_id,
            'source_wash_order_id' => $washOrder->id,
            'code' => 'FID-CORTESIA-OK',
            'status' => LoyaltyCoupon::STATUS_USED,
            'earned_at' => now(),
            'used_at' => now(),
        ]);
        $washOrder->forceFill(['loyalty_coupon_id' => $coupon->id])->save();

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

    public function test_user_can_reverse_payment_and_wash_order_returns_to_pending(): void
    {
        $user = User::factory()->create();
        $washOrder = WashOrder::factory()->create([
            'wash_location_id' => $user->wash_location_id,
            'payment_status' => WashOrder::PAYMENT_PAID,
            'total_amount' => 80,
        ]);
        $payment = Payment::factory()->create([
            'wash_order_id' => $washOrder->id,
            'user_id' => $user->id,
            'method' => Payment::METHOD_PIX,
            'amount' => 80,
        ]);

        $this->actingAs($user)->patch(route('payments.reverse', [$washOrder, $payment]), [
            'reversal_reason' => 'Pagamento lançado na lavagem errada.',
        ])->assertRedirect();

        $payment->refresh();
        $washOrder->refresh();

        $this->assertTrue($payment->isReversed());
        $this->assertSame($user->id, $payment->reversed_by_user_id);
        $this->assertSame('Pagamento lançado na lavagem errada.', $payment->reversal_reason);
        $this->assertSame(WashOrder::PAYMENT_PENDING, $washOrder->payment_status);
        $this->assertDatabaseHas('audit_logs', [
            'wash_location_id' => $user->wash_location_id,
            'action' => 'payment.reversed',
            'subject_id' => $washOrder->id,
        ]);

        $this->actingAs($user)->get(route('wash-orders.show', $washOrder))
            ->assertOk()
            ->assertSee('Estornado')
            ->assertSee('Pagamento lançado na lavagem errada.');
    }

    public function test_reversed_payment_is_not_counted_in_finance_report(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $washOrder = WashOrder::factory()->create([
            'wash_location_id' => $user->wash_location_id,
            'payment_status' => WashOrder::PAYMENT_PAID,
            'total_amount' => 80,
        ]);
        $payment = Payment::factory()->create([
            'wash_order_id' => $washOrder->id,
            'user_id' => $user->id,
            'method' => Payment::METHOD_PIX,
            'amount' => 80,
            'paid_at' => now(),
            'reversed_at' => now(),
            'reversed_by_user_id' => $user->id,
            'reversal_reason' => 'Teste de conciliação.',
        ]);

        $this->actingAs($user)
            ->get(route('finance.index'))
            ->assertOk()
            ->assertSee('R$ 0,00')
            ->assertDontSee($payment->washOrder->code);
    }
}
