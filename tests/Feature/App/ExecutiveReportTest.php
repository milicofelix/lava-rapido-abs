<?php

namespace Tests\Feature\App;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WashLocation;
use App\Models\WashOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ExecutiveReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_executive_report_with_period_metrics(): void
    {
        Carbon::setTestNow('2026-06-28 12:00:00');

        try {
            $location = WashLocation::factory()->create(['name' => 'Lava Executivo']);
            $otherLocation = WashLocation::factory()->create(['name' => 'Outra Unidade']);
            $admin = User::factory()->create([
                'role' => User::ROLE_ADMIN,
                'wash_location_id' => $location->id,
            ]);
            $customer = Customer::factory()->create([
                'wash_location_id' => $location->id,
                'name' => 'Carlos Recorrente',
                'phone' => '(11) 98888-7777',
            ]);
            $vehicle = Vehicle::factory()->for($customer)->create([
                'wash_location_id' => $location->id,
            ]);
            $service = Service::factory()->create([
                'wash_location_id' => $location->id,
                'name' => 'Ducha Premium',
                'base_price' => 90,
                'estimated_minutes' => 45,
            ]);

            $this->createPaidOrder($location, $customer, $vehicle, $service, $admin, '2026-06-10 10:00:00', 90, Payment::METHOD_PIX);
            $this->createPaidOrder($location, $customer, $vehicle, $service, $admin, '2026-06-20 11:00:00', 110, Payment::METHOD_CASH);
            $this->createPaidOrder($location, $customer, $vehicle, $service, $admin, '2026-05-12 09:00:00', 50, Payment::METHOD_PIX);

            $otherCustomer = Customer::factory()->create(['wash_location_id' => $otherLocation->id, 'name' => 'Cliente de Fora']);
            $otherVehicle = Vehicle::factory()->for($otherCustomer)->create(['wash_location_id' => $otherLocation->id]);
            $otherService = Service::factory()->create(['wash_location_id' => $otherLocation->id, 'name' => 'Servico de Outra Unidade']);
            $this->createPaidOrder($otherLocation, $otherCustomer, $otherVehicle, $otherService, $admin, '2026-06-15 10:00:00', 999, Payment::METHOD_CREDIT_CARD);

            $this->actingAs($admin)
                ->get(route('reports.executive'))
                ->assertOk()
                ->assertSee('Relatórios executivos')
                ->assertSee('R$ 200,00')
                ->assertSee('Ducha Premium')
                ->assertSee('Carlos Recorrente')
                ->assertSee('Clientes recorrentes')
                ->assertSee('Pix')
                ->assertSee('Dinheiro')
                ->assertSee('data-onboarding-tour', false)
                ->assertSee('reports.executive.v1')
                ->assertSee('data-tour="executive-report-period"', false)
                ->assertSee('data-tour="executive-report-filters"', false)
                ->assertSee('data-tour="executive-report-kpis"', false)
                ->assertSee('data-tour="executive-report-operational-kpis"', false)
                ->assertSee('data-tour="executive-report-top-services"', false)
                ->assertSee('data-tour="executive-report-payments"', false)
                ->assertSee('data-tour="executive-report-top-customers"', false)
                ->assertSee('data-tour="executive-report-statuses"', false)
                ->assertSee('data-tour="executive-report-daily-volume"', false)
                ->assertDontSee('Servico de Outra Unidade')
                ->assertDontSee('R$ 999,00');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_operator_cannot_access_executive_report(): void
    {
        $operator = User::factory()->create(['role' => User::ROLE_OPERATOR]);

        $this->actingAs($operator)
            ->get(route('reports.executive'))
            ->assertForbidden();
    }

    public function test_admin_can_export_executive_report_as_pdf(): void
    {
        Carbon::setTestNow('2026-06-28 12:00:00');

        try {
            $location = WashLocation::factory()->create(['name' => 'Lava PDF']);
            $otherLocation = WashLocation::factory()->create(['name' => 'Outra PDF']);
            $admin = User::factory()->create([
                'role' => User::ROLE_ADMIN,
                'wash_location_id' => $location->id,
            ]);
            $customer = Customer::factory()->create([
                'wash_location_id' => $location->id,
                'name' => 'Cliente PDF',
            ]);
            $vehicle = Vehicle::factory()->for($customer)->create([
                'wash_location_id' => $location->id,
            ]);
            $service = Service::factory()->create([
                'wash_location_id' => $location->id,
                'name' => 'Lavagem PDF',
                'base_price' => 80,
            ]);

            $this->createPaidOrder($location, $customer, $vehicle, $service, $admin, '2026-06-22 10:00:00', 80, Payment::METHOD_PIX);

            $otherCustomer = Customer::factory()->create(['wash_location_id' => $otherLocation->id, 'name' => 'Cliente Fora PDF']);
            $otherVehicle = Vehicle::factory()->for($otherCustomer)->create(['wash_location_id' => $otherLocation->id]);
            $otherService = Service::factory()->create(['wash_location_id' => $otherLocation->id, 'name' => 'Servico Fora PDF']);
            $this->createPaidOrder($otherLocation, $otherCustomer, $otherVehicle, $otherService, $admin, '2026-06-22 10:00:00', 999, Payment::METHOD_PIX);

            $response = $this->actingAs($admin)
                ->get(route('reports.executive.pdf', [
                    'start' => '2026-06-01',
                    'end' => '2026-06-28',
                ]));

            $response->assertOk()
                ->assertHeader('content-type', 'application/pdf');

            $content = $response->getContent();

            $this->assertStringStartsWith('%PDF-1.4', $content);
            $this->assertStringContainsString('/WinAnsiEncoding', $content);
            $this->assertStringContainsString('Relat', $content);
            $this->assertStringContainsString('Lavagem PDF', $content);
            $this->assertStringContainsString('Cliente PDF', $content);
            $this->assertStringNotContainsString('Servico Fora PDF', $content);
            $this->assertStringNotContainsString('999,00', $content);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_operator_cannot_export_executive_report_as_pdf(): void
    {
        $operator = User::factory()->create(['role' => User::ROLE_OPERATOR]);

        $this->actingAs($operator)
            ->get(route('reports.executive.pdf'))
            ->assertForbidden();
    }

    public function test_executive_report_rejects_inverted_period(): void
    {
        Carbon::setTestNow('2026-06-28 12:00:00');

        try {
            $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

            $this->actingAs($admin)
                ->from(route('reports.executive'))
                ->get(route('reports.executive', [
                    'start' => '2026-06-25',
                    'end' => '2026-06-24',
                ]))
                ->assertRedirect(route('reports.executive'))
                ->assertSessionHasErrors('end');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_executive_report_rejects_future_period(): void
    {
        Carbon::setTestNow('2026-06-28 12:00:00');

        try {
            $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

            $this->actingAs($admin)
                ->from(route('reports.executive'))
                ->get(route('reports.executive', [
                    'start' => '2026-06-29',
                    'end' => '2026-06-29',
                ]))
                ->assertRedirect(route('reports.executive'))
                ->assertSessionHasErrors('start');
        } finally {
            Carbon::setTestNow();
        }
    }

    private function createPaidOrder(
        WashLocation $location,
        Customer $customer,
        Vehicle $vehicle,
        Service $service,
        User $user,
        string $enteredAt,
        float $amount,
        string $method,
    ): WashOrder {
        $order = WashOrder::factory()->create([
            'wash_location_id' => $location->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'assigned_user_id' => $user->id,
            'status' => WashOrder::STATUS_DELIVERED,
            'payment_status' => WashOrder::PAYMENT_PAID,
            'entered_at' => Carbon::parse($enteredAt),
            'completed_at' => Carbon::parse($enteredAt)->addMinutes(45),
            'total_amount' => $amount,
        ]);

        $order->services()->attach($service->id, [
            'service_name' => $service->name,
            'price' => $amount,
            'estimated_minutes' => $service->estimated_minutes,
            'created_at' => Carbon::parse($enteredAt),
            'updated_at' => Carbon::parse($enteredAt),
        ]);

        Payment::factory()->for($order)->for($user)->create([
            'method' => $method,
            'amount' => $amount,
            'paid_at' => Carbon::parse($enteredAt)->addMinutes(50),
        ]);

        return $order;
    }
}
