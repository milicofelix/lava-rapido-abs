<?php

namespace Tests\Feature\App;

use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Models\WashOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_operational_and_financial_metrics(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create(['name' => 'Lavagem premium']);
        $washOrder = WashOrder::factory()->create([
            'entered_at' => now()->subMinutes(90),
            'completed_at' => now(),
            'total_amount' => 120,
            'status' => WashOrder::STATUS_READY,
            'payment_status' => WashOrder::PAYMENT_PAID,
        ]);

        $washOrder->services()->attach($service, [
            'service_name' => $service->name,
            'price' => 120,
            'estimated_minutes' => 90,
        ]);

        Payment::factory()->for($washOrder)->for($user)->create([
            'method' => Payment::METHOD_PIX,
            'amount' => 120,
            'paid_at' => now(),
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Faturamento hoje')
            ->assertSee('R$ 120,00')
            ->assertSee('Fluxo de Lavagens')
            ->assertSee('Resumo Financeiro')
            ->assertSee('Servicos mais realizados')
            ->assertSee('Lavagem premium')
            ->assertSee('Atividades recentes');
    }
}
