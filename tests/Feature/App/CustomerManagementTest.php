<?php

namespace Tests\Feature\App;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\LoyaltyCoupon;
use App\Models\LoyaltyProgram;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WashLocation;
use App\Models\WashOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendant_can_create_customer(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('customers.store'), [
            'name' => 'Maria Silva',
            'phone' => '(11) 99999-0000',
            'email' => 'maria@example.com',
            'notes' => 'Prefere contato por WhatsApp.',
        ])->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'name' => 'Maria Silva',
            'phone' => '(11) 99999-0000',
        ]);
    }

    public function test_customer_search_finds_vehicle_plate(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->hasVehicles(1, ['plate' => 'ABC1D23'])->create();

        $this->actingAs($user)->get(route('customers.index', ['search' => 'ABC1D23']))
            ->assertOk()
            ->assertSee($customer->name)
            ->assertSee('data-onboarding-tour', false)
            ->assertSee('customers.index.v1')
            ->assertSee('data-tour="customers-search"', false)
            ->assertSee('data-tour="customers-list"', false)
            ->assertSee('Gerenciando clientes');
    }

    public function test_customer_form_has_guided_tour_and_no_cpf_field(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('customers.create'))
            ->assertOk()
            ->assertSee('data-onboarding-tour', false)
            ->assertSee('customers.create.v1')
            ->assertSee('data-tour="customer-form-name"', false)
            ->assertSee('data-tour="customer-form-phone"', false)
            ->assertDontSee('name="cpf"', false)
            ->assertDontSee('CPF');
    }

    public function test_user_can_import_customers_and_vehicles_from_csv(): void
    {
        $user = User::factory()->create();
        $csv = implode("\n", [
            'nome,telefone,email,observacao,placa,marca,modelo,cor,observacao_veiculo',
            'Maria Importada,(11) 99999-0000,maria.importada@example.com,Cliente antigo,abc-1d23,Hyundai,HB20,Prata,Sem adesivos',
            'Jose Sem Carro,(11) 98888-0000,,,,,,,',
        ]);

        $this->actingAs($user)->post(route('customers.import'), [
            'customers_file' => UploadedFile::fake()->createWithContent('clientes.csv', $csv),
        ])->assertRedirect(route('customers.index'))
            ->assertSessionHas('import_summary.imported_rows', 2)
            ->assertSessionHas('import_summary.created_customers', 2)
            ->assertSessionHas('import_summary.created_vehicles', 1);

        $this->assertDatabaseHas('customers', [
            'wash_location_id' => $user->wash_location_id,
            'name' => 'Maria Importada',
            'phone' => '(11) 99999-0000',
        ]);
        $this->assertDatabaseHas('customers', [
            'wash_location_id' => $user->wash_location_id,
            'name' => 'Jose Sem Carro',
        ]);
        $this->assertDatabaseHas('vehicles', [
            'wash_location_id' => $user->wash_location_id,
            'plate' => 'ABC1D23',
            'brand' => 'Hyundai',
            'model' => 'HB20',
            'type' => 'carro',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'wash_location_id' => $user->wash_location_id,
            'action' => 'customers.imported',
        ]);
    }

    public function test_customer_import_reports_invalid_rows_without_importing_them(): void
    {
        $user = User::factory()->create();
        $csv = implode("\n", [
            'nome;telefone;placa;marca;modelo;cor',
            'Cliente Valido;(11) 97777-0000;def-2g34;Toyota;Corolla;Branco',
            'Cliente Invalido;(11) 96666-0000;ghi-3h45;Fiat;HB20;Azul',
        ]);

        $this->actingAs($user)->post(route('customers.import'), [
            'customers_file' => UploadedFile::fake()->createWithContent('clientes.csv', $csv),
        ])->assertRedirect(route('customers.index'))
            ->assertSessionHas('import_summary.imported_rows', 1)
            ->assertSessionHas('import_summary.skipped_rows', 1);

        $this->assertDatabaseHas('customers', [
            'wash_location_id' => $user->wash_location_id,
            'name' => 'Cliente Valido',
        ]);
        $this->assertDatabaseHas('vehicles', [
            'wash_location_id' => $user->wash_location_id,
            'plate' => 'DEF2G34',
            'brand' => 'Toyota',
            'model' => 'Corolla',
        ]);
        $this->assertDatabaseMissing('customers', [
            'wash_location_id' => $user->wash_location_id,
            'name' => 'Cliente Invalido',
        ]);
    }

    public function test_user_can_download_customer_import_template(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('customers.import-template'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        $this->assertStringContainsString('nome,telefone,email,observacao,placa,marca,modelo,cor,observacao_veiculo', $content);
        $this->assertStringContainsString('Maria Silva', $content);
        $this->assertStringContainsString('Hyundai', $content);
        $this->assertStringContainsString('HB20', $content);
    }

    public function test_customer_index_shows_loyalty_progress(): void
    {
        $location = WashLocation::factory()->create();
        $user = User::factory()->create(['wash_location_id' => $location->id]);
        $customer = Customer::factory()->create([
            'wash_location_id' => $location->id,
            'name' => 'Cliente Fidelidade',
        ]);
        $vehicle = Vehicle::factory()->for($customer)->create(['wash_location_id' => $location->id]);
        $service = Service::factory()->create([
            'wash_location_id' => $location->id,
            'active' => true,
        ]);

        LoyaltyProgram::query()->create([
            'wash_location_id' => $location->id,
            'is_active' => true,
            'threshold' => 3,
            'count_scope' => LoyaltyProgram::COUNT_ANY,
            'reward_type' => LoyaltyProgram::REWARD_FIXED_SERVICE,
            'reward_service_id' => $service->id,
            'coupon_valid_days' => 30,
        ]);

        $this->createDeliveredOrder($location, $customer, $vehicle, $service);
        $this->createDeliveredOrder($location, $customer, $vehicle, $service);

        $this->actingAs($user)
            ->get(route('customers.index'))
            ->assertOk()
            ->assertSee('Cliente Fidelidade')
            ->assertSee('2/3 lavadas');
    }

    public function test_customer_edit_shows_loyalty_details_and_coupons(): void
    {
        $location = WashLocation::factory()->create();
        $user = User::factory()->create(['wash_location_id' => $location->id]);
        $customer = Customer::factory()->create([
            'wash_location_id' => $location->id,
            'name' => 'Cliente Cupom',
        ]);
        $vehicle = Vehicle::factory()->for($customer)->create(['wash_location_id' => $location->id]);
        $service = Service::factory()->create([
            'wash_location_id' => $location->id,
            'name' => 'Ducha simples',
            'active' => true,
        ]);
        $program = LoyaltyProgram::query()->create([
            'wash_location_id' => $location->id,
            'is_active' => true,
            'threshold' => 3,
            'count_scope' => LoyaltyProgram::COUNT_ANY,
            'reward_type' => LoyaltyProgram::REWARD_FIXED_SERVICE,
            'reward_service_id' => $service->id,
            'coupon_valid_days' => 30,
        ]);
        $washOrder = $this->createDeliveredOrder($location, $customer, $vehicle, $service);

        LoyaltyCoupon::query()->create([
            'wash_location_id' => $location->id,
            'loyalty_program_id' => $program->id,
            'customer_id' => $customer->id,
            'source_wash_order_id' => $washOrder->id,
            'reward_service_id' => $service->id,
            'code' => 'FID-CLI-123',
            'status' => LoyaltyCoupon::STATUS_ACTIVE,
            'earned_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $this->actingAs($user)
            ->get(route('customers.edit', $customer))
            ->assertOk()
            ->assertSee('Progresso do cliente')
            ->assertSee('Cupom disponível')
            ->assertSee('Faltam para o próximo cupom')
            ->assertSee('Últimos cupons')
            ->assertSee('FID-CLI-123')
            ->assertSee('Ducha simples');
    }

    public function test_customer_edit_shows_consolidated_history_insights(): void
    {
        $location = WashLocation::factory()->create();
        $user = User::factory()->create(['wash_location_id' => $location->id]);
        $customer = Customer::factory()->create([
            'wash_location_id' => $location->id,
            'name' => 'Cliente Histórico',
        ]);
        $vehicle = Vehicle::factory()->for($customer)->create([
            'wash_location_id' => $location->id,
            'plate' => 'ABC1D23',
            'brand' => 'Hyundai',
            'model' => 'HB20',
        ]);
        $service = Service::factory()->create([
            'wash_location_id' => $location->id,
            'name' => 'Ducha premium',
            'base_price' => 90,
            'active' => true,
        ]);
        $firstOrder = $this->createDeliveredOrder($location, $customer, $vehicle, $service);
        $firstOrder->forceFill([
            'entered_at' => now()->subDays(8),
            'total_amount' => 90,
        ])->save();
        $secondOrder = $this->createDeliveredOrder($location, $customer, $vehicle, $service);
        $secondOrder->forceFill([
            'entered_at' => now()->subDay(),
            'total_amount' => 90,
        ])->save();

        Payment::factory()->for($firstOrder)->for($user)->create([
            'amount' => 120,
            'paid_at' => now()->subDays(8),
        ]);
        Payment::factory()->for($secondOrder)->for($user)->create([
            'amount' => 60,
            'paid_at' => now()->subDay(),
        ]);

        $this->actingAs($user)
            ->get(route('customers.edit', $customer))
            ->assertOk()
            ->assertSee('Histórico consolidado')
            ->assertSee('Resumo do relacionamento')
            ->assertSee('Lavagens totais')
            ->assertSee('Receita gerada')
            ->assertSee('R$ 180,00')
            ->assertSee('Ticket médio')
            ->assertSee('R$ 90,00')
            ->assertSee('Serviço favorito: Ducha premium')
            ->assertSee('Veículos do cliente')
            ->assertSee('ABC1D23')
            ->assertSee('Hyundai HB20')
            ->assertSee('2 lavagens')
            ->assertSee('Serviços mais consumidos')
            ->assertSee('Ducha premium')
            ->assertSee('Últimas lavagens')
            ->assertSee($secondOrder->code);
    }

    public function test_loyalty_coupon_page_shows_personalized_coupon_and_whatsapp_action(): void
    {
        $location = WashLocation::factory()->create(['name' => 'Lava Rapido Central']);
        $user = User::factory()->create(['wash_location_id' => $location->id]);
        $customer = Customer::factory()->create([
            'wash_location_id' => $location->id,
            'name' => 'Cliente Cupom',
            'phone' => '(11) 99999-0000',
        ]);
        $vehicle = Vehicle::factory()->for($customer)->create(['wash_location_id' => $location->id]);
        $service = Service::factory()->create([
            'wash_location_id' => $location->id,
            'name' => 'Ducha simples',
            'active' => true,
        ]);
        $program = LoyaltyProgram::query()->create([
            'wash_location_id' => $location->id,
            'is_active' => true,
            'threshold' => 3,
            'count_scope' => LoyaltyProgram::COUNT_ANY,
            'reward_type' => LoyaltyProgram::REWARD_FIXED_SERVICE,
            'reward_service_id' => $service->id,
            'coupon_valid_days' => 30,
        ]);
        $washOrder = $this->createDeliveredOrder($location, $customer, $vehicle, $service);
        $coupon = LoyaltyCoupon::query()->create([
            'wash_location_id' => $location->id,
            'loyalty_program_id' => $program->id,
            'customer_id' => $customer->id,
            'source_wash_order_id' => $washOrder->id,
            'reward_service_id' => $service->id,
            'code' => 'FID-CLI-123',
            'status' => LoyaltyCoupon::STATUS_ACTIVE,
            'earned_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $this->actingAs($user)
            ->get(route('loyalty-coupons.show', $coupon))
            ->assertOk()
            ->assertSee('Cupom de fidelidade')
            ->assertSee('FID-CLI-123')
            ->assertSee('Cliente Cupom')
            ->assertSee('Lava Rapido Central')
            ->assertSee('Ducha simples')
            ->assertSee('Código do cupom')
            ->assertSee('Status do cupom')
            ->assertSee('Agradecemos sua preferência')
            ->assertSee('Compartilhar via WhatsApp')
            ->assertSee('Baixar imagem')
            ->assertSee('https://wa.me/5511999990000', false)
            ->assertSee('data-coupon-download', false)
            ->assertSee('data-coupon-card', false)
            ->assertSee('data-onboarding-tour', false)
            ->assertSee('loyalty-coupons.show.v1')
            ->assertSee('data-tour="loyalty-coupon-actions"', false)
            ->assertSee('data-tour="loyalty-coupon-card"', false)
            ->assertSee('data-tour="loyalty-coupon-main"', false)
            ->assertSee('data-tour="loyalty-coupon-code"', false)
            ->assertSee('data-tour="loyalty-coupon-details"', false)
            ->assertSee('data-tour="loyalty-coupon-status"', false)
            ->assertSee('data-tour="loyalty-coupon-message"', false)
            ->assertSee('data-tour="loyalty-coupon-internal-control"', false);
    }

    public function test_user_cannot_open_loyalty_coupon_from_another_location(): void
    {
        $location = WashLocation::factory()->create();
        $otherLocation = WashLocation::factory()->create();
        $user = User::factory()->create(['wash_location_id' => $otherLocation->id]);
        $customer = Customer::factory()->create(['wash_location_id' => $location->id]);
        $vehicle = Vehicle::factory()->for($customer)->create(['wash_location_id' => $location->id]);
        $service = Service::factory()->create(['wash_location_id' => $location->id]);
        $program = LoyaltyProgram::query()->create([
            'wash_location_id' => $location->id,
            'is_active' => true,
            'threshold' => 3,
            'count_scope' => LoyaltyProgram::COUNT_ANY,
            'reward_type' => LoyaltyProgram::REWARD_FIXED_SERVICE,
            'reward_service_id' => $service->id,
            'coupon_valid_days' => 30,
        ]);
        $washOrder = $this->createDeliveredOrder($location, $customer, $vehicle, $service);
        $coupon = LoyaltyCoupon::query()->create([
            'wash_location_id' => $location->id,
            'loyalty_program_id' => $program->id,
            'customer_id' => $customer->id,
            'source_wash_order_id' => $washOrder->id,
            'reward_service_id' => $service->id,
            'code' => 'FID-OUTRA-123',
            'status' => LoyaltyCoupon::STATUS_ACTIVE,
            'earned_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $this->actingAs($user)
            ->get(route('loyalty-coupons.show', $coupon))
            ->assertNotFound();
    }

    public function test_active_loyalty_coupon_can_be_canceled(): void
    {
        $location = WashLocation::factory()->create();
        $user = User::factory()->create(['wash_location_id' => $location->id]);
        $customer = Customer::factory()->create(['wash_location_id' => $location->id]);
        $vehicle = Vehicle::factory()->for($customer)->create(['wash_location_id' => $location->id]);
        $service = Service::factory()->create(['wash_location_id' => $location->id]);
        $program = LoyaltyProgram::query()->create([
            'wash_location_id' => $location->id,
            'is_active' => true,
            'threshold' => 3,
            'count_scope' => LoyaltyProgram::COUNT_ANY,
            'reward_type' => LoyaltyProgram::REWARD_FIXED_SERVICE,
            'reward_service_id' => $service->id,
            'coupon_valid_days' => 30,
        ]);
        $washOrder = $this->createDeliveredOrder($location, $customer, $vehicle, $service);
        $coupon = LoyaltyCoupon::query()->create([
            'wash_location_id' => $location->id,
            'loyalty_program_id' => $program->id,
            'customer_id' => $customer->id,
            'source_wash_order_id' => $washOrder->id,
            'reward_service_id' => $service->id,
            'code' => 'FID-CANCEL-123',
            'status' => LoyaltyCoupon::STATUS_ACTIVE,
            'earned_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $this->actingAs($user)
            ->patch(route('loyalty-coupons.cancel', $coupon), [
                'reason' => 'Emitido por engano.',
            ])
            ->assertRedirect(route('loyalty-coupons.show', $coupon));

        $coupon->refresh();

        $this->assertSame(LoyaltyCoupon::STATUS_CANCELED, $coupon->status);
        $this->assertSame('Emitido por engano.', $coupon->metadata['canceled_reason']);
        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_LOYALTY_COUPON_CANCELED,
            'subject_type' => LoyaltyCoupon::class,
            'subject_id' => $coupon->id,
        ]);
    }

    public function test_used_loyalty_coupon_cannot_be_canceled(): void
    {
        $location = WashLocation::factory()->create();
        $user = User::factory()->create(['wash_location_id' => $location->id]);
        $customer = Customer::factory()->create(['wash_location_id' => $location->id]);
        $vehicle = Vehicle::factory()->for($customer)->create(['wash_location_id' => $location->id]);
        $service = Service::factory()->create(['wash_location_id' => $location->id]);
        $program = LoyaltyProgram::query()->create([
            'wash_location_id' => $location->id,
            'is_active' => true,
            'threshold' => 3,
            'count_scope' => LoyaltyProgram::COUNT_ANY,
            'reward_type' => LoyaltyProgram::REWARD_FIXED_SERVICE,
            'reward_service_id' => $service->id,
            'coupon_valid_days' => 30,
        ]);
        $washOrder = $this->createDeliveredOrder($location, $customer, $vehicle, $service);
        $coupon = LoyaltyCoupon::query()->create([
            'wash_location_id' => $location->id,
            'loyalty_program_id' => $program->id,
            'customer_id' => $customer->id,
            'source_wash_order_id' => $washOrder->id,
            'used_wash_order_id' => $washOrder->id,
            'used_by_user_id' => $user->id,
            'reward_service_id' => $service->id,
            'code' => 'FID-USADO-123',
            'status' => LoyaltyCoupon::STATUS_USED,
            'earned_at' => now(),
            'expires_at' => now()->addDays(30),
            'used_at' => now(),
        ]);

        $this->actingAs($user)
            ->from(route('loyalty-coupons.show', $coupon))
            ->patch(route('loyalty-coupons.cancel', $coupon))
            ->assertRedirect(route('loyalty-coupons.show', $coupon))
            ->assertSessionHasErrors('coupon');

        $this->assertSame(LoyaltyCoupon::STATUS_USED, $coupon->fresh()->status);
    }

    private function createDeliveredOrder(
        WashLocation $location,
        Customer $customer,
        Vehicle $vehicle,
        Service $service,
    ): WashOrder {
        $washOrder = WashOrder::factory()->for($customer)->for($vehicle)->create([
            'wash_location_id' => $location->id,
            'status' => WashOrder::STATUS_DELIVERED,
            'payment_status' => WashOrder::PAYMENT_PAID,
        ]);

        $washOrder->services()->attach($service->id, [
            'service_name' => $service->name,
            'price' => $service->base_price,
            'estimated_minutes' => $service->estimated_minutes,
        ]);

        return $washOrder;
    }
}
