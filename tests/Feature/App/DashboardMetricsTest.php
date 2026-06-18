<?php

namespace Tests\Feature\App;

use App\Models\Payment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WashOrder;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_operational_and_financial_metrics(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['name' => 'Cliente Recorrente']);
        $vehicle = Vehicle::factory()->for($customer)->create(['plate' => 'HOJ1A23']);
        $oldVehicle = Vehicle::factory()->for($customer)->create(['plate' => 'OLD1A23']);
        $deliveredVehicle = Vehicle::factory()->for($customer)->create(['plate' => 'ENT2A34']);
        $service = Service::factory()->create(['name' => 'Lavagem premium']);
        $washOrder = WashOrder::factory()->create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
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

        $secondWashOrder = WashOrder::factory()->create([
            'customer_id' => $customer->id,
            'vehicle_id' => $oldVehicle->id,
            'entered_at' => now()->subDays(2),
            'completed_at' => now()->subDays(2)->addMinutes(70),
            'total_amount' => 80,
            'status' => WashOrder::STATUS_AWAITING,
            'payment_status' => WashOrder::PAYMENT_PENDING,
        ]);

        $secondWashOrder->services()->attach($service, [
            'service_name' => $service->name,
            'price' => 80,
            'estimated_minutes' => 70,
        ]);

        WashOrder::factory()->create([
            'customer_id' => $customer->id,
            'vehicle_id' => $oldVehicle->id,
            'entered_at' => now()->subDay(),
            'total_amount' => 80,
            'status' => WashOrder::STATUS_WASHING,
            'payment_status' => WashOrder::PAYMENT_PENDING,
        ]);

        $deliveredWashOrder = WashOrder::factory()->create([
            'customer_id' => $customer->id,
            'vehicle_id' => $deliveredVehicle->id,
            'entered_at' => now()->subMinutes(30),
            'completed_at' => now()->subMinutes(5),
            'total_amount' => 95,
            'status' => WashOrder::STATUS_DELIVERED,
            'payment_status' => WashOrder::PAYMENT_PAID,
        ]);

        $deliveredWashOrder->services()->attach($service, [
            'service_name' => $service->name,
            'price' => 95,
            'estimated_minutes' => 60,
        ]);

        Payment::factory()->for($washOrder)->for($user)->create([
            'method' => Payment::METHOD_PIX,
            'amount' => 120,
            'paid_at' => now(),
        ]);
        Payment::factory()->for($secondWashOrder)->for($user)->create([
            'method' => Payment::METHOD_CASH,
            'amount' => 60,
            'paid_at' => now()->subMonth(),
        ]);
        WashOrder::factory()->create([
            'customer_id' => $customer->id,
            'vehicle_id' => $oldVehicle->id,
            'entered_at' => now()->subMonth(),
            'total_amount' => 60,
            'status' => WashOrder::STATUS_DELIVERED,
            'payment_status' => WashOrder::PAYMENT_PAID,
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard executivo')
            ->assertSee('Lavagens do mes')
            ->assertSee('Receita do mes')
            ->assertSee('Ticket medio')
            ->assertSee('Clientes recorrentes')
            ->assertSee('+300% vs mes anterior')
            ->assertSee('+100% vs mes anterior')
            ->assertSee('Top servicos do mes')
            ->assertSee('Cliente Recorrente')
            ->assertSee('Faturamento hoje')
            ->assertSee('Entregues hoje')
            ->assertSee('aria-label="Em andamento: 0"', false)
            ->assertSee('aria-label="Prontas: 1"', false)
            ->assertSee('aria-label="Entregues hoje: 1"', false)
            ->assertSee('R$ 120,00')
            ->assertSee('Fluxo de Lavagens')
            ->assertSee('HOJ1A23')
            ->assertSee('ENT2A34')
            ->assertDontSee('OLD1A23')
            ->assertSee('Resumo Financeiro')
            ->assertSee('Servicos mais realizados')
            ->assertSee('Lavagem premium')
            ->assertSee('Atividades recentes');
    }

    public function test_dashboard_greeting_follows_current_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-18 15:00:00'));

        $user = User::factory()->create(['name' => 'Adriano']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Boa tarde, Adriano!');

        Carbon::setTestNow();
    }
}
