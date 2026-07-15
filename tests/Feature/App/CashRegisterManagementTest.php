<?php

namespace Tests\Feature\App;

use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\Payment;
use App\Models\User;
use App\Models\WashOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashRegisterManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_register_screen_exposes_guided_tour_when_register_is_closed(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('finance.cash-registers.index'))
            ->assertOk()
            ->assertSee('data-onboarding-tour', false)
            ->assertSee('finance.cash-registers.index.v1')
            ->assertSee('data-tour="cash-register-intro"', false)
            ->assertSee('data-tour="cash-register-open-form"', false)
            ->assertSee('data-tour="cash-register-opening-fields"', false)
            ->assertSee('data-tour="cash-register-history"', false);
    }

    public function test_admin_can_open_cash_register(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->post(route('finance.cash-registers.store'), [
            'opening_balance' => 120.50,
            'opening_notes' => 'Troco inicial.',
        ])->assertRedirect();

        $this->assertDatabaseHas('cash_registers', [
            'opened_by_user_id' => $admin->id,
            'status' => CashRegister::STATUS_OPEN,
            'opening_balance' => 120.50,
            'opening_notes' => 'Troco inicial.',
        ]);
    }

    public function test_system_does_not_allow_two_open_cash_registers(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        CashRegister::factory()->create(['opened_by_user_id' => $admin->id]);

        $this->actingAs($admin)->post(route('finance.cash-registers.store'), [
            'opening_balance' => 10,
        ])->assertSessionHasErrors('cash_register');

        $this->assertSame(1, CashRegister::query()->where('status', CashRegister::STATUS_OPEN)->count());
    }

    public function test_admin_can_register_cash_supply_and_withdrawal(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $cashRegister = CashRegister::factory()->create(['opened_by_user_id' => $admin->id]);

        $this->actingAs($admin)->post(route('finance.cash-registers.movements.store', $cashRegister), [
            'type' => CashMovement::TYPE_WITHDRAWAL,
            'amount' => 50,
            'description' => 'Sangria para cofre.',
        ])->assertRedirect();

        $this->assertDatabaseHas('cash_movements', [
            'cash_register_id' => $cashRegister->id,
            'user_id' => $admin->id,
            'type' => CashMovement::TYPE_WITHDRAWAL,
            'amount' => 50,
            'description' => 'Sangria para cofre.',
        ]);
    }

    public function test_admin_can_close_cash_register_with_expected_cash_difference(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $cashRegister = CashRegister::factory()->create([
            'opened_by_user_id' => $admin->id,
            'opening_balance' => 100,
            'opened_at' => now()->subHour(),
        ]);

        $cashOrder = WashOrder::factory()->create(['total_amount' => 80]);
        Payment::factory()->create([
            'wash_order_id' => $cashOrder->id,
            'user_id' => $admin->id,
            'method' => Payment::METHOD_CASH,
            'amount' => 80,
            'paid_at' => now()->subMinutes(20),
        ]);
        Payment::factory()->create([
            'wash_order_id' => $cashOrder->id,
            'user_id' => $admin->id,
            'method' => Payment::METHOD_CASH,
            'amount' => 40,
            'paid_at' => now()->subMinutes(10),
            'reversed_at' => now()->subMinutes(5),
            'reversed_by_user_id' => $admin->id,
            'reversal_reason' => 'Pagamento duplicado.',
        ]);
        CashMovement::factory()->create([
            'cash_register_id' => $cashRegister->id,
            'user_id' => $admin->id,
            'type' => CashMovement::TYPE_SUPPLY,
            'amount' => 20,
        ]);
        CashMovement::factory()->create([
            'cash_register_id' => $cashRegister->id,
            'user_id' => $admin->id,
            'type' => CashMovement::TYPE_WITHDRAWAL,
            'amount' => 30,
        ]);

        $this->actingAs($admin)->patch(route('finance.cash-registers.close', $cashRegister), [
            'counted_cash' => 175,
            'closing_notes' => 'Diferenca pequena conferida.',
        ])->assertRedirect();

        $cashRegister->refresh();

        $this->assertSame(CashRegister::STATUS_CLOSED, $cashRegister->status);
        $this->assertSame('170.00', (string) $cashRegister->expected_cash);
        $this->assertSame('175.00', (string) $cashRegister->counted_cash);
        $this->assertSame('5.00', (string) $cashRegister->cash_difference);
    }

    public function test_cash_register_screen_shows_closing_reconciliation_summary(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $cashRegister = CashRegister::factory()->create([
            'wash_location_id' => $admin->wash_location_id,
            'opened_by_user_id' => $admin->id,
            'opening_balance' => 100,
            'opened_at' => now()->subHour(),
        ]);
        $cashOrder = WashOrder::factory()->create([
            'wash_location_id' => $admin->wash_location_id,
            'total_amount' => 80,
        ]);
        $pixOrder = WashOrder::factory()->create([
            'wash_location_id' => $admin->wash_location_id,
            'total_amount' => 45,
        ]);

        Payment::factory()->create([
            'wash_order_id' => $cashOrder->id,
            'user_id' => $admin->id,
            'method' => Payment::METHOD_CASH,
            'amount' => 80,
            'paid_at' => now()->subMinutes(20),
        ]);
        Payment::factory()->create([
            'wash_order_id' => $pixOrder->id,
            'user_id' => $admin->id,
            'method' => Payment::METHOD_PIX,
            'amount' => 45,
            'paid_at' => now()->subMinutes(10),
        ]);
        CashMovement::factory()->create([
            'cash_register_id' => $cashRegister->id,
            'user_id' => $admin->id,
            'type' => CashMovement::TYPE_SUPPLY,
            'amount' => 20,
        ]);
        CashMovement::factory()->create([
            'cash_register_id' => $cashRegister->id,
            'user_id' => $admin->id,
            'type' => CashMovement::TYPE_WITHDRAWAL,
            'amount' => 30,
        ]);

        $this->actingAs($admin)
            ->get(route('finance.cash-registers.index'))
            ->assertOk()
            ->assertSee('Resumo para fechamento')
            ->assertSee('Vendas totais')
            ->assertSee('R$ 125,00')
            ->assertSee('Previsto em dinheiro')
            ->assertSee('R$ 170,00')
            ->assertSee('Dinheiro')
            ->assertSee('R$ 80,00')
            ->assertSee('Pix')
            ->assertSee('R$ 45,00')
            ->assertSee('Suprimentos')
            ->assertSee('Sangrias')
            ->assertSee('data-onboarding-tour', false)
            ->assertSee('finance.cash-registers.index.v1')
            ->assertSee('data-tour="cash-register-status"', false)
            ->assertSee('data-tour="cash-register-reconciliation"', false)
            ->assertSee('data-tour="cash-register-payment-methods"', false)
            ->assertSee('data-tour="cash-register-manual-movement"', false)
            ->assertSee('data-tour="cash-register-close-form"', false)
            ->assertSee('data-tour="cash-register-current-movements"', false)
            ->assertSee('data-tour="cash-register-history"', false);
    }
}
