<?php

namespace Tests\Feature\App;

use App\Models\Payment;
use App\Models\User;
use App\Models\WashOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FinanceReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_financial_report_for_period(): void
    {
        $user = User::factory()->create(['name' => 'Admin Caixa', 'role' => User::ROLE_ADMIN]);
        $washOrder = WashOrder::factory()->create(['total_amount' => 90]);

        Payment::factory()->for($washOrder)->for($user)->create([
            'method' => Payment::METHOD_PIX,
            'amount' => 90,
            'paid_at' => now(),
        ]);

        $this->actingAs($user)->get(route('finance.index', [
            'start' => today()->toDateString(),
            'end' => today()->toDateString(),
        ]))
            ->assertOk()
            ->assertSee('Financeiro')
            ->assertSee('Pix')
            ->assertSee('R$ 90,00')
            ->assertSee($washOrder->code);
    }

    public function test_admin_can_export_financial_report_as_csv(): void
    {
        $user = User::factory()->create(['name' => 'Admin Caixa', 'role' => User::ROLE_ADMIN]);
        $washOrder = WashOrder::factory()->create(['total_amount' => 45]);

        Payment::factory()->for($washOrder)->for($user)->create([
            'method' => Payment::METHOD_CASH,
            'amount' => 45,
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('finance.export', [
            'start' => today()->toDateString(),
            'end' => today()->toDateString(),
        ]));

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('Data,Código,Cliente,Placa,Método,Valor', $content);
        $this->assertStringContainsString($washOrder->code, $content);
        $this->assertStringContainsString('Dinheiro', $content);
    }

    public function test_financial_report_rejects_inverted_period(): void
    {
        Carbon::setTestNow('2026-06-26 12:00:00');

        try {
            $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

            $this->actingAs($user)
                ->from(route('finance.index'))
                ->get(route('finance.index', [
                    'start' => '2026-06-26',
                    'end' => '2026-06-25',
                ]))
                ->assertRedirect(route('finance.index'))
                ->assertSessionHasErrors('end');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_financial_report_rejects_future_period(): void
    {
        Carbon::setTestNow('2026-06-26 12:00:00');

        try {
            $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

            $this->actingAs($user)
                ->from(route('finance.index'))
                ->get(route('finance.index', [
                    'start' => '2026-06-27',
                    'end' => '2026-06-27',
                ]))
                ->assertRedirect(route('finance.index'))
                ->assertSessionHasErrors('start');
        } finally {
            Carbon::setTestNow();
        }
    }
}
