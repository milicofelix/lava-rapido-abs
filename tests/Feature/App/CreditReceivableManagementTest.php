<?php

namespace Tests\Feature\App;

use App\Models\Payment;
use App\Models\User;
use App\Models\WashOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditReceivableManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_credit_receivables_page(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        WashOrder::factory()->create([
            'payment_status' => WashOrder::PAYMENT_CREDIT_PENDING,
            'total_amount' => 90,
        ]);

        $this->actingAs($admin)
            ->get(route('finance.credit-receivables.index'))
            ->assertOk()
            ->assertSee('Fiado / Contas a receber')
            ->assertSee('R$ 90,00')
            ->assertSee('data-onboarding-tour', false)
            ->assertSee('finance.credit-receivables.index.v1')
            ->assertSee('data-tour="credit-receivables-intro"', false)
            ->assertSee('data-tour="credit-receivables-summary"', false)
            ->assertSee('data-tour="credit-receivables-list"', false)
            ->assertSee('data-tour="credit-receivables-order"', false)
            ->assertSee('data-tour="credit-receivables-receive-form"', false);
    }

    public function test_admin_can_receive_a_credit_pending_wash_order(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $washOrder = WashOrder::factory()->create([
            'payment_status' => WashOrder::PAYMENT_CREDIT_PENDING,
            'total_amount' => 90,
        ]);

        $this->actingAs($admin)->patch(route('finance.credit-receivables.receive', $washOrder), [
            'method' => Payment::METHOD_PIX,
            'amount' => 90,
            'notes' => 'Cliente pagou depois via Pix.',
        ])->assertRedirect();

        $washOrder->refresh();

        $this->assertSame(WashOrder::PAYMENT_PAID, $washOrder->payment_status);
        $this->assertDatabaseHas('payments', [
            'wash_order_id' => $washOrder->id,
            'user_id' => $admin->id,
            'method' => Payment::METHOD_PIX,
            'amount' => 90,
            'notes' => 'Cliente pagou depois via Pix.',
        ]);
    }

    public function test_cannot_receive_credit_for_order_that_is_not_credit_pending(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $washOrder = WashOrder::factory()->create([
            'payment_status' => WashOrder::PAYMENT_PENDING,
            'total_amount' => 90,
        ]);

        $this->actingAs($admin)->patch(route('finance.credit-receivables.receive', $washOrder), [
            'method' => Payment::METHOD_PIX,
            'amount' => 90,
        ])->assertSessionHasErrors('credit_payment');
    }
}
