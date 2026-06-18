<?php

namespace Tests\Feature\App;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WashOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WashHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendant_can_filter_operational_history(): void
    {
        $attendant = User::factory()->create(['name' => 'Carlos Atendente', 'role' => User::ROLE_ATTENDANT]);
        $teammate = User::factory()->create(['name' => 'Ana Secagem', 'role' => User::ROLE_OPERATOR]);
        $customer = Customer::factory()->create(['name' => 'Joao Historico']);
        $vehicle = Vehicle::factory()->for($customer)->create([
            'plate' => 'HST1A23',
            'brand' => 'Toyota',
            'model' => 'Corolla',
        ]);
        $service = Service::factory()->create([
            'name' => 'Lavagem Premium',
            'base_price' => 120,
            'estimated_minutes' => 90,
        ]);

        $matchingOrder = WashOrder::factory()->for($customer)->for($vehicle)->for($attendant, 'assignedUser')->create([
            'code' => 'ABS-HIST-001',
            'status' => WashOrder::STATUS_DELIVERED,
            'payment_status' => WashOrder::PAYMENT_PAID,
            'entered_at' => now()->subDay(),
            'total_amount' => 120,
        ]);
        $matchingOrder->services()->attach($service->id, [
            'service_name' => $service->name,
            'price' => 120,
            'estimated_minutes' => 90,
        ]);
        $matchingOrder->teamMembers()->attach($teammate->id);
        Payment::factory()->for($matchingOrder)->for($attendant)->create([
            'method' => Payment::METHOD_PIX,
            'amount' => 120,
            'paid_at' => now()->subDay(),
        ]);

        $otherOrder = WashOrder::factory()->create([
            'code' => 'ABS-HIST-999',
            'status' => WashOrder::STATUS_AWAITING,
            'entered_at' => now()->subDay(),
        ]);

        $this->actingAs($attendant)->get(route('history.index', [
            'start' => now()->subDays(2)->toDateString(),
            'end' => today()->toDateString(),
            'customer_id' => $customer->id,
            'plate' => 'hst1',
            'service_id' => $service->id,
            'status' => WashOrder::STATUS_DELIVERED,
            'employee_id' => $teammate->id,
            'payment_method' => Payment::METHOD_PIX,
        ]))
            ->assertOk()
            ->assertSee('Historico operacional')
            ->assertSee('ABS-HIST-001')
            ->assertSee('Joao Historico')
            ->assertSee('HST1A23')
            ->assertSee('Lavagem Premium')
            ->assertSee('Ana Secagem')
            ->assertDontSee($otherOrder->code);
    }

    public function test_attendant_can_export_operational_history_as_csv(): void
    {
        $attendant = User::factory()->create(['name' => 'Atendente Caixa']);
        $customer = Customer::factory()->create(['name' => 'Cliente Exportacao']);
        $vehicle = Vehicle::factory()->for($customer)->create([
            'plate' => 'CSV2B34',
            'brand' => 'Hyundai',
            'model' => 'HB20',
        ]);
        $service = Service::factory()->create(['name' => 'Ducha Completa']);

        $washOrder = WashOrder::factory()->for($customer)->for($vehicle)->for($attendant, 'assignedUser')->create([
            'code' => 'ABS-CSV-001',
            'entered_at' => now()->subDay(),
            'total_amount' => 65,
        ]);
        $washOrder->services()->attach($service->id, [
            'service_name' => $service->name,
            'price' => 65,
            'estimated_minutes' => 45,
        ]);
        Payment::factory()->for($washOrder)->for($attendant)->create([
            'method' => Payment::METHOD_CASH,
            'amount' => 65,
            'paid_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($attendant)->get(route('history.export', [
            'start' => now()->subDays(2)->toDateString(),
            'end' => today()->toDateString(),
        ]));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('Entrada,Codigo,Cliente,Placa,Veiculo,Servicos,Status,Equipe,Pagamento,Total', $content);
        $this->assertStringContainsString('ABS-CSV-001', $content);
        $this->assertStringContainsString('Cliente Exportacao', $content);
        $this->assertStringContainsString('CSV2B34', $content);
        $this->assertStringContainsString('Dinheiro', $content);
    }
}
