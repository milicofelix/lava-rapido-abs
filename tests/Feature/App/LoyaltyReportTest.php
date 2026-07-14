<?php

namespace Tests\Feature\App;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\LoyaltyCoupon;
use App\Models\LoyaltyProgram;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WashLocation;
use App\Models\WashOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoyaltyReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_loyalty_report_metrics(): void
    {
        $location = WashLocation::factory()->create();
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $location->id,
        ]);
        $customer = Customer::factory()->create([
            'wash_location_id' => $location->id,
            'name' => 'Cliente Relatorio',
            'phone' => '(11) 99999-1111',
        ]);
        $vehicle = Vehicle::factory()->for($customer)->create(['wash_location_id' => $location->id]);
        $service = Service::factory()->create([
            'wash_location_id' => $location->id,
            'name' => 'Ducha simples',
            'base_price' => 35,
        ]);
        $program = $this->program($location, $service);
        $sourceOrder = $this->deliveredOrder($location, $customer, $vehicle, $service, now()->subDays(2));
        $usedOrder = $this->deliveredOrder($location, $customer, $vehicle, $service, now()->subDay());

        LoyaltyCoupon::query()->create([
            'wash_location_id' => $location->id,
            'loyalty_program_id' => $program->id,
            'customer_id' => $customer->id,
            'source_wash_order_id' => $sourceOrder->id,
            'reward_service_id' => $service->id,
            'code' => 'FID-ATIVO-001',
            'status' => LoyaltyCoupon::STATUS_ACTIVE,
            'earned_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $usedCoupon = LoyaltyCoupon::query()->create([
            'wash_location_id' => $location->id,
            'loyalty_program_id' => $program->id,
            'customer_id' => $customer->id,
            'source_wash_order_id' => $sourceOrder->id,
            'used_wash_order_id' => $usedOrder->id,
            'used_by_user_id' => $owner->id,
            'reward_service_id' => $service->id,
            'code' => 'FID-USADO-001',
            'status' => LoyaltyCoupon::STATUS_USED,
            'earned_at' => now(),
            'expires_at' => now()->addDays(30),
            'used_at' => now(),
        ]);
        $usedOrder->forceFill([
            'loyalty_coupon_id' => $usedCoupon->id,
            'loyalty_discount_amount' => 35,
        ])->save();

        $this->actingAs($owner)
            ->get(route('loyalty-reports.index'))
            ->assertOk()
            ->assertSee('Relatorio gerencial')
            ->assertSee('Cliente Relatorio')
            ->assertSee('FID-ATIVO-001')
            ->assertSee('FID-USADO-001')
            ->assertSee('R$ 35,00');
    }

    public function test_loyalty_report_filters_by_customer_and_status(): void
    {
        $location = WashLocation::factory()->create();
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'wash_location_id' => $location->id,
        ]);
        $service = Service::factory()->create(['wash_location_id' => $location->id]);
        $program = $this->program($location, $service);
        $customer = Customer::factory()->create(['wash_location_id' => $location->id, 'name' => 'Cliente Filtrado']);
        $otherCustomer = Customer::factory()->create(['wash_location_id' => $location->id, 'name' => 'Cliente Oculto']);
        $vehicle = Vehicle::factory()->for($customer)->create(['wash_location_id' => $location->id]);
        $otherVehicle = Vehicle::factory()->for($otherCustomer)->create(['wash_location_id' => $location->id]);
        $sourceOrder = $this->deliveredOrder($location, $customer, $vehicle, $service, now());
        $otherSourceOrder = $this->deliveredOrder($location, $otherCustomer, $otherVehicle, $service, now());

        LoyaltyCoupon::query()->create([
            'wash_location_id' => $location->id,
            'loyalty_program_id' => $program->id,
            'customer_id' => $customer->id,
            'source_wash_order_id' => $sourceOrder->id,
            'reward_service_id' => $service->id,
            'code' => 'FID-FILTRO-001',
            'status' => LoyaltyCoupon::STATUS_ACTIVE,
            'earned_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);
        LoyaltyCoupon::query()->create([
            'wash_location_id' => $location->id,
            'loyalty_program_id' => $program->id,
            'customer_id' => $otherCustomer->id,
            'source_wash_order_id' => $otherSourceOrder->id,
            'reward_service_id' => $service->id,
            'code' => 'FID-OCULTO-001',
            'status' => LoyaltyCoupon::STATUS_USED,
            'earned_at' => now(),
            'expires_at' => now()->addDays(30),
            'used_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('loyalty-reports.index', [
                'customer_id' => $customer->id,
                'status' => LoyaltyCoupon::STATUS_ACTIVE,
            ]))
            ->assertOk()
            ->assertSee('FID-FILTRO-001')
            ->assertDontSee('FID-OCULTO-001');
    }

    public function test_loyalty_report_treats_overdue_active_coupon_as_expired(): void
    {
        $location = WashLocation::factory()->create();
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'wash_location_id' => $location->id,
        ]);
        $service = Service::factory()->create(['wash_location_id' => $location->id]);
        $program = $this->program($location, $service);
        $customer = Customer::factory()->create(['wash_location_id' => $location->id, 'name' => 'Cliente Vencido']);
        $vehicle = Vehicle::factory()->for($customer)->create(['wash_location_id' => $location->id]);
        $sourceOrder = $this->deliveredOrder($location, $customer, $vehicle, $service, now()->subDays(5));

        LoyaltyCoupon::query()->create([
            'wash_location_id' => $location->id,
            'loyalty_program_id' => $program->id,
            'customer_id' => $customer->id,
            'source_wash_order_id' => $sourceOrder->id,
            'reward_service_id' => $service->id,
            'code' => 'FID-ATIVO-VENCIDO',
            'status' => LoyaltyCoupon::STATUS_ACTIVE,
            'earned_at' => now()->subDays(5),
            'expires_at' => now()->subDay(),
        ]);

        $this->actingAs($user)
            ->get(route('loyalty-reports.index', ['status' => LoyaltyCoupon::STATUS_ACTIVE]))
            ->assertOk()
            ->assertDontSee('FID-ATIVO-VENCIDO');

        $this->actingAs($user)
            ->get(route('loyalty-reports.index', ['status' => LoyaltyCoupon::STATUS_EXPIRED]))
            ->assertOk()
            ->assertSee('FID-ATIVO-VENCIDO')
            ->assertSee('Expirado');
    }

    public function test_loyalty_report_csv_exports_effective_expired_status(): void
    {
        $location = WashLocation::factory()->create();
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $location->id,
        ]);
        $service = Service::factory()->create(['wash_location_id' => $location->id]);
        $program = $this->program($location, $service);
        $customer = Customer::factory()->create(['wash_location_id' => $location->id, 'name' => 'Cliente CSV Vencido']);
        $vehicle = Vehicle::factory()->for($customer)->create(['wash_location_id' => $location->id]);
        $sourceOrder = $this->deliveredOrder($location, $customer, $vehicle, $service, now()->subDays(5));

        LoyaltyCoupon::query()->create([
            'wash_location_id' => $location->id,
            'loyalty_program_id' => $program->id,
            'customer_id' => $customer->id,
            'source_wash_order_id' => $sourceOrder->id,
            'reward_service_id' => $service->id,
            'code' => 'FID-CSV-VENCIDO',
            'status' => LoyaltyCoupon::STATUS_ACTIVE,
            'earned_at' => now()->subDays(5),
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($owner)->get(route('loyalty-reports.export', [
            'status' => LoyaltyCoupon::STATUS_EXPIRED,
        ]));

        $content = $response->streamedContent();

        $this->assertStringContainsString('FID-CSV-VENCIDO', $content);
        $this->assertStringContainsString('Expirado', $content);
    }

    public function test_owner_can_export_filtered_loyalty_report_as_csv(): void
    {
        $location = WashLocation::factory()->create();
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $location->id,
        ]);
        $service = Service::factory()->create([
            'wash_location_id' => $location->id,
            'name' => 'Lavagem completa',
        ]);
        $program = $this->program($location, $service);
        $customer = Customer::factory()->create([
            'wash_location_id' => $location->id,
            'name' => 'Cliente Exportado',
        ]);
        $otherCustomer = Customer::factory()->create([
            'wash_location_id' => $location->id,
            'name' => 'Cliente Fora',
        ]);
        $vehicle = Vehicle::factory()->for($customer)->create(['wash_location_id' => $location->id]);
        $otherVehicle = Vehicle::factory()->for($otherCustomer)->create(['wash_location_id' => $location->id]);
        $sourceOrder = $this->deliveredOrder($location, $customer, $vehicle, $service, now());
        $otherSourceOrder = $this->deliveredOrder($location, $otherCustomer, $otherVehicle, $service, now());

        LoyaltyCoupon::query()->create([
            'wash_location_id' => $location->id,
            'loyalty_program_id' => $program->id,
            'customer_id' => $customer->id,
            'source_wash_order_id' => $sourceOrder->id,
            'reward_service_id' => $service->id,
            'code' => 'FID-CSV-001',
            'status' => LoyaltyCoupon::STATUS_ACTIVE,
            'earned_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);
        LoyaltyCoupon::query()->create([
            'wash_location_id' => $location->id,
            'loyalty_program_id' => $program->id,
            'customer_id' => $otherCustomer->id,
            'source_wash_order_id' => $otherSourceOrder->id,
            'reward_service_id' => $service->id,
            'code' => 'FID-CSV-FORA',
            'status' => LoyaltyCoupon::STATUS_ACTIVE,
            'earned_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $response = $this->actingAs($owner)->get(route('loyalty-reports.export', [
            'customer_id' => $customer->id,
            'status' => LoyaltyCoupon::STATUS_ACTIVE,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        $this->assertStringContainsString('FID-CSV-001', $content);
        $this->assertStringContainsString('Cliente Exportado', $content);
        $this->assertStringNotContainsString('FID-CSV-FORA', $content);
    }

    public function test_loyalty_report_searches_by_coupon_code_and_customer_data(): void
    {
        $location = WashLocation::factory()->create();
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'wash_location_id' => $location->id,
        ]);
        $service = Service::factory()->create(['wash_location_id' => $location->id]);
        $program = $this->program($location, $service);
        $customer = Customer::factory()->create([
            'wash_location_id' => $location->id,
            'name' => 'Cliente Busca',
            'phone' => '(11) 98888-7777',
            'cpf' => '111.222.333-44',
        ]);
        $otherCustomer = Customer::factory()->create([
            'wash_location_id' => $location->id,
            'name' => 'Cliente Invisivel',
        ]);
        $vehicle = Vehicle::factory()->for($customer)->create(['wash_location_id' => $location->id]);
        $otherVehicle = Vehicle::factory()->for($otherCustomer)->create(['wash_location_id' => $location->id]);
        $sourceOrder = $this->deliveredOrder($location, $customer, $vehicle, $service, now());
        $otherSourceOrder = $this->deliveredOrder($location, $otherCustomer, $otherVehicle, $service, now());

        LoyaltyCoupon::query()->create([
            'wash_location_id' => $location->id,
            'loyalty_program_id' => $program->id,
            'customer_id' => $customer->id,
            'source_wash_order_id' => $sourceOrder->id,
            'reward_service_id' => $service->id,
            'code' => 'FID-BUSCA-001',
            'status' => LoyaltyCoupon::STATUS_ACTIVE,
            'earned_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);
        LoyaltyCoupon::query()->create([
            'wash_location_id' => $location->id,
            'loyalty_program_id' => $program->id,
            'customer_id' => $otherCustomer->id,
            'source_wash_order_id' => $otherSourceOrder->id,
            'reward_service_id' => $service->id,
            'code' => 'FID-OUTRO-001',
            'status' => LoyaltyCoupon::STATUS_ACTIVE,
            'earned_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $this->actingAs($user)
            ->get(route('loyalty-reports.index', ['search' => '98888']))
            ->assertOk()
            ->assertSee('FID-BUSCA-001')
            ->assertSee('Cliente Busca')
            ->assertDontSee('FID-OUTRO-001');

        $this->actingAs($user)
            ->get(route('loyalty-reports.index', ['search' => 'BUSCA-001']))
            ->assertOk()
            ->assertSee('FID-BUSCA-001')
            ->assertDontSee('FID-OUTRO-001');
    }

    public function test_owner_can_process_pending_loyalty_coupons(): void
    {
        $location = WashLocation::factory()->create();
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $location->id,
        ]);
        $customer = Customer::factory()->create([
            'wash_location_id' => $location->id,
            'name' => 'Cliente Elegivel',
        ]);
        $vehicle = Vehicle::factory()->for($customer)->create(['wash_location_id' => $location->id]);
        $service = Service::factory()->create(['wash_location_id' => $location->id]);

        $this->program($location, $service);
        $this->deliveredOrder($location, $customer, $vehicle, $service, now()->subDays(3));
        $this->deliveredOrder($location, $customer, $vehicle, $service, now()->subDays(2));
        $this->deliveredOrder($location, $customer, $vehicle, $service, now()->subDay());

        $this->assertDatabaseCount('loyalty_coupons', 0);

        $this->actingAs($owner)
            ->post(route('loyalty-reports.process-coupons'))
            ->assertRedirect(route('loyalty-reports.index'))
            ->assertSessionHas('status', '1 cupom pendente foi gerado.');

        $this->assertDatabaseHas('loyalty_coupons', [
            'wash_location_id' => $location->id,
            'customer_id' => $customer->id,
            'status' => LoyaltyCoupon::STATUS_ACTIVE,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_LOYALTY_COUPONS_PROCESSED,
            'wash_location_id' => $location->id,
        ]);
    }

    public function test_process_pending_loyalty_coupons_requires_active_program(): void
    {
        $location = WashLocation::factory()->create();
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $location->id,
        ]);

        $this->actingAs($owner)
            ->from(route('loyalty-reports.index'))
            ->post(route('loyalty-reports.process-coupons'))
            ->assertRedirect(route('loyalty-reports.index'))
            ->assertSessionHasErrors('loyalty_program');
    }

    public function test_operator_cannot_view_loyalty_report(): void
    {
        $operator = User::factory()->create(['role' => User::ROLE_OPERATOR]);

        $this->actingAs($operator)
            ->get(route('loyalty-reports.index'))
            ->assertForbidden();
    }

    public function test_owner_can_view_retention_and_recurrence_report(): void
    {
        $location = WashLocation::factory()->create();
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $location->id,
        ]);
        $service = Service::factory()->create(['wash_location_id' => $location->id]);
        $this->program($location, $service);

        $returningCustomer = Customer::factory()->create([
            'wash_location_id' => $location->id,
            'name' => 'Cliente Recorrente',
        ]);
        $returningVehicle = Vehicle::factory()->for($returningCustomer)->create(['wash_location_id' => $location->id]);
        $singleVisitCustomer = Customer::factory()->create([
            'wash_location_id' => $location->id,
            'name' => 'Cliente Primeira Visita',
        ]);
        $singleVisitVehicle = Vehicle::factory()->for($singleVisitCustomer)->create(['wash_location_id' => $location->id]);

        $this->deliveredOrder($location, $returningCustomer, $returningVehicle, $service, now()->subMonth()->startOfMonth());
        $this->deliveredOrder($location, $returningCustomer, $returningVehicle, $service, now()->subDays(4));
        $this->deliveredOrder($location, $returningCustomer, $returningVehicle, $service, now()->subDays(2));
        $this->deliveredOrder($location, $singleVisitCustomer, $singleVisitVehicle, $service, now()->subDay());

        $otherLocation = WashLocation::factory()->create();
        $otherCustomer = Customer::factory()->create([
            'wash_location_id' => $otherLocation->id,
            'name' => 'Cliente Outra Unidade Retencao',
        ]);
        $otherVehicle = Vehicle::factory()->for($otherCustomer)->create(['wash_location_id' => $otherLocation->id]);
        $otherService = Service::factory()->create(['wash_location_id' => $otherLocation->id]);
        $this->deliveredOrder($otherLocation, $otherCustomer, $otherVehicle, $otherService, now()->subDay());

        $this->actingAs($owner)
            ->get(route('loyalty-reports.index', [
                'start' => now()->startOfMonth()->toDateString(),
                'end' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('Retenção e recorrência')
            ->assertSee('Clientes atendidos')
            ->assertSee('Clientes recorrentes')
            ->assertSee('Taxa de recorrência')
            ->assertSee('50,0%')
            ->assertSee('1 cliente já tinha histórico anterior.')
            ->assertSee('1 lavagem')
            ->assertSee('2 lavagens')
            ->assertDontSee('Cliente Outra Unidade Retencao');
    }

    public function test_owner_can_view_loyalty_campaign_segments(): void
    {
        $location = WashLocation::factory()->create();
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $location->id,
        ]);
        $service = Service::factory()->create([
            'wash_location_id' => $location->id,
            'name' => 'Ducha simples',
        ]);
        $program = $this->program($location, $service);

        $nearCustomer = Customer::factory()->create([
            'wash_location_id' => $location->id,
            'name' => 'Cliente Quase Premio',
            'phone' => '(11) 98888-0001',
        ]);
        $nearVehicle = Vehicle::factory()->for($nearCustomer)->create(['wash_location_id' => $location->id]);
        $this->deliveredOrder($location, $nearCustomer, $nearVehicle, $service, now()->subDays(4));
        $this->deliveredOrder($location, $nearCustomer, $nearVehicle, $service, now()->subDays(2));

        $couponCustomer = Customer::factory()->create([
            'wash_location_id' => $location->id,
            'name' => 'Cliente Cupom Vencendo',
            'phone' => '(11) 98888-0002',
        ]);
        $couponVehicle = Vehicle::factory()->for($couponCustomer)->create(['wash_location_id' => $location->id]);
        $sourceOrder = $this->deliveredOrder($location, $couponCustomer, $couponVehicle, $service, now()->subDays(8));
        LoyaltyCoupon::query()->create([
            'wash_location_id' => $location->id,
            'loyalty_program_id' => $program->id,
            'customer_id' => $couponCustomer->id,
            'source_wash_order_id' => $sourceOrder->id,
            'reward_service_id' => $service->id,
            'code' => 'FID-VENCE-001',
            'status' => LoyaltyCoupon::STATUS_ACTIVE,
            'earned_at' => now()->subDays(8),
            'expires_at' => now()->addDays(3),
        ]);

        $inactiveCustomer = Customer::factory()->create([
            'wash_location_id' => $location->id,
            'name' => 'Cliente Sumido',
            'phone' => '(11) 98888-0003',
        ]);
        $inactiveVehicle = Vehicle::factory()->for($inactiveCustomer)->create(['wash_location_id' => $location->id]);
        $this->deliveredOrder($location, $inactiveCustomer, $inactiveVehicle, $service, now()->subDays(70));

        $otherLocation = WashLocation::factory()->create();
        $otherCustomer = Customer::factory()->create([
            'wash_location_id' => $otherLocation->id,
            'name' => 'Cliente Outra Unidade',
        ]);
        $otherVehicle = Vehicle::factory()->for($otherCustomer)->create(['wash_location_id' => $otherLocation->id]);
        $otherService = Service::factory()->create(['wash_location_id' => $otherLocation->id]);
        $this->program($otherLocation, $otherService);
        $this->deliveredOrder($otherLocation, $otherCustomer, $otherVehicle, $otherService, now()->subDays(60));

        $this->actingAs($owner)
            ->get(route('loyalty-campaigns.index'))
            ->assertOk()
            ->assertSee('Campanhas e promoções')
            ->assertSee('Perto de ganhar')
            ->assertSee('Cliente Quase Premio')
            ->assertSee('Cupom vencendo')
            ->assertSee('FID-VENCE-001')
            ->assertSee('Sem retorno recente')
            ->assertSee('Cliente Sumido')
            ->assertSee('https://wa.me/5511988880001', false)
            ->assertDontSee('Cliente Outra Unidade');
    }

    public function test_operator_cannot_view_loyalty_campaigns(): void
    {
        $operator = User::factory()->create(['role' => User::ROLE_OPERATOR]);

        $this->actingAs($operator)
            ->get(route('loyalty-campaigns.index'))
            ->assertForbidden();
    }

    private function program(WashLocation $location, Service $service): LoyaltyProgram
    {
        return LoyaltyProgram::query()->create([
            'wash_location_id' => $location->id,
            'is_active' => true,
            'threshold' => 3,
            'count_scope' => LoyaltyProgram::COUNT_ANY,
            'reward_type' => LoyaltyProgram::REWARD_FIXED_SERVICE,
            'reward_service_id' => $service->id,
            'coupon_valid_days' => 30,
        ]);
    }

    private function deliveredOrder(
        WashLocation $location,
        Customer $customer,
        Vehicle $vehicle,
        Service $service,
        $enteredAt,
    ): WashOrder {
        $washOrder = WashOrder::factory()->for($customer)->for($vehicle)->create([
            'wash_location_id' => $location->id,
            'assigned_user_id' => User::factory()->create(['wash_location_id' => $location->id])->id,
            'status' => WashOrder::STATUS_DELIVERED,
            'payment_status' => WashOrder::PAYMENT_PAID,
            'entered_at' => $enteredAt,
            'total_amount' => $service->base_price,
        ]);

        $washOrder->services()->attach($service->id, [
            'service_name' => $service->name,
            'price' => $service->base_price,
            'estimated_minutes' => $service->estimated_minutes,
        ]);

        return $washOrder;
    }
}
