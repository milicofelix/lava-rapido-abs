<?php

namespace Tests\Feature\App;

use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\LoyaltyProgram;
use App\Models\RolePermissionSetting;
use App\Models\Service;
use App\Models\User;
use App\Models\WashLocation;
use App\Support\Access\AccessControl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_settings_page(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('settings.edit'))
            ->assertOk()
            ->assertSee('Configurações')
            ->assertSee('Habilitar Caixa')
            ->assertSee('Habilitar Fiado')
            ->assertSee('Programa de fidelidade')
            ->assertSee('Tema do painel')
            ->assertSee('Matriz de acesso por perfil')
            ->assertSee('Exceções configuráveis')
            ->assertDontSee('URL do Google Maps');
    }

    public function test_settings_page_shows_permission_matrix_by_profile(): void
    {
        $location = WashLocation::factory()->create();
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'wash_location_id' => $location->id,
        ]);

        RolePermissionSetting::setForLocation($location->id, User::ROLE_OPERATOR, [
            AccessControl::VIEW_WASH_ORDERS => true,
            AccessControl::CREATE_WASH_ORDER => false,
            AccessControl::SEND_WASH_NOTIFICATIONS => false,
        ]);

        $this->actingAs($admin)
            ->get(route('settings.edit'))
            ->assertOk()
            ->assertSee('Matriz de acesso por perfil')
            ->assertSee('Dono')
            ->assertSee('Administrador')
            ->assertSee('Atendente')
            ->assertSee('Operador')
            ->assertSee('Avançar status da lavagem')
            ->assertSee('+ Visualizar detalhes da lavagem')
            ->assertSee('Bloqueado por configuração')
            ->assertSee('Abrir e listar lavagens');
    }

    public function test_settings_page_tolerates_cached_settings_without_new_modules(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Cache::forever('app_settings.all', [
            'company_name' => 'Lava Antigo',
            'company_whatsapp' => '',
            'theme' => AppSetting::THEME_LIGHT,
            'module_cash_register' => true,
            'module_credit_receivables' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('settings.edit'))
            ->assertOk()
            ->assertSee('Habilitar Agenda');
    }

    public function test_settings_page_exposes_guided_tour(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('settings.edit'))
            ->assertOk()
            ->assertSee('data-onboarding-tour', false)
            ->assertSee('"key":"settings.edit.v1', false)
            ->assertSee('settings.edit.v1')
            ->assertSee('Configurações da unidade')
            ->assertSee('data-tour="settings-unit"', false)
            ->assertSee('data-tour="settings-hours"', false)
            ->assertSee('data-tour="settings-modules"', false)
            ->assertSee('data-tour="settings-loyalty"', false)
            ->assertSee('data-tour="settings-permissions"', false)
            ->assertSee('data-tour="settings-save"', false);
    }

    public function test_admin_can_update_modules_and_theme(): void
    {
        $location = WashLocation::factory()->create();
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'wash_location_id' => $location->id,
        ]);

        $this->actingAs($admin)
            ->put(route('settings.update'), [
                'company_name' => 'Auto Spa Teste',
                'company_whatsapp' => '(11) 98888-7777',
                'legal_name' => 'Auto Spa Teste Ltda',
                'document' => '12.345.678/0001-90',
                'address' => 'Rua Premium',
                'address_number' => '123',
                'district' => 'Centro',
                'city' => 'Sao Paulo',
                'state' => 'SP',
                'latitude' => -23.54891,
                'longitude' => -46.63412,
                'opening_hours' => 'Seg a sex: 08:00 as 18:00',
                'theme' => AppSetting::THEME_DARK,
                'module_schedule' => '1',
                'module_cash_register' => '1',
                'module_credit_receivables' => '1',
            ])
            ->assertRedirect();

        $this->assertSame('Auto Spa Teste', AppSetting::getValue('company_name'));
        $this->assertSame('(11) 98888-7777', AppSetting::getValue('company_whatsapp'));
        $this->assertSame(AppSetting::THEME_DARK, AppSetting::theme());
        $this->assertTrue(AppSetting::isModuleEnabled('module_schedule'));
        $this->assertTrue(AppSetting::isModuleEnabled('module_cash_register'));
        $this->assertTrue(AppSetting::isModuleEnabled('module_credit_receivables'));

        $location->refresh();

        $this->assertSame('Auto Spa Teste', $location->name);
        $this->assertSame('Auto Spa Teste Ltda', $location->legal_name);
        $this->assertSame('12.345.678/0001-90', $location->document);
        $this->assertSame('Rua Premium', $location->address);
        $this->assertSame('123', $location->address_number);
        $this->assertSame('Centro', $location->district);
        $this->assertSame('Sao Paulo', $location->city);
        $this->assertSame('SP', $location->state);
        $this->assertSame('-23.5489100', (string) $location->latitude);
        $this->assertSame('-46.6341200', (string) $location->longitude);
        $this->assertSame('Seg a sex: 08:00 as 18:00', $location->opening_hours);

        $this->assertDatabaseHas('audit_logs', [
            'wash_location_id' => $location->id,
            'user_id' => $admin->id,
            'action' => AuditLog::ACTION_LOCATION_PROFILE_UPDATED,
            'subject_label' => 'Auto Spa Teste',
        ]);
    }

    public function test_admin_can_update_coordinates_from_google_maps_url(): void
    {
        $location = WashLocation::factory()->create([
            'latitude' => null,
            'longitude' => null,
        ]);
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'wash_location_id' => $location->id,
        ]);
        $mapsUrl = 'https://www.google.com/maps/place/Av.+Nordestina,+4660+-+Vila+Nova+Curuca,+S%C3%A3o+Paulo+-+SP,+08032-000/@-23.5191405,-46.4207678,17z/data=!3m1!4b1!4m6!3m5!1s0x94ce640cd8043355:0x58a019a956587895!8m2!3d-23.5191405!4d-46.4207678!16s%2Fg%2F11c4dryd_5?entry=ttu';

        $this->actingAs($admin)
            ->put(route('settings.update'), [
                'company_name' => $location->name,
                'company_whatsapp' => $location->phone,
                'address' => $location->address,
                'district' => $location->district,
                'city' => $location->city,
                'state' => $location->state ?? 'SP',
                'google_maps_url' => $mapsUrl,
                'theme' => AppSetting::THEME_LIGHT,
                'module_schedule' => '1',
            ])
            ->assertRedirect();

        $location->refresh();

        $this->assertSame('-23.5191405', (string) $location->latitude);
        $this->assertSame('-46.4207678', (string) $location->longitude);
    }

    public function test_admin_can_update_structured_business_hours(): void
    {
        $location = WashLocation::factory()->create();
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'wash_location_id' => $location->id,
        ]);

        $this->actingAs($admin)
            ->put(route('settings.update'), [
                'company_name' => $location->name,
                'company_whatsapp' => $location->phone,
                'address' => $location->address,
                'address_number' => $location->address_number,
                'district' => $location->district,
                'city' => $location->city,
                'state' => $location->state ?? 'SP',
                'theme' => AppSetting::THEME_LIGHT,
                'business_hours' => [
                    'monday' => ['is_open' => '1', 'opens' => '07:30', 'closes' => '18:30'],
                    'tuesday' => ['is_open' => '1', 'opens' => '07:30', 'closes' => '18:30'],
                    'wednesday' => ['is_open' => '1', 'opens' => '07:30', 'closes' => '18:30'],
                    'thursday' => ['is_open' => '1', 'opens' => '07:30', 'closes' => '18:30'],
                    'friday' => ['is_open' => '1', 'opens' => '07:30', 'closes' => '18:30'],
                    'saturday' => ['is_open' => '1', 'opens' => '08:00', 'closes' => '14:00'],
                    'sunday' => ['is_open' => '0', 'opens' => '08:00', 'closes' => '14:00'],
                ],
            ])
            ->assertRedirect();

        $location->refresh();

        $this->assertSame('07:30', $location->business_hours['monday']['opens']);
        $this->assertTrue($location->business_hours['saturday']['is_open']);
        $this->assertFalse($location->business_hours['sunday']['is_open']);
        $this->assertStringContainsString('Segunda: 07:30 as 18:30', $location->opening_hours);
        $this->assertStringContainsString('Domingo: fechado', $location->opening_hours);
    }

    public function test_admin_can_upload_valid_unit_logo(): void
    {
        Storage::fake('public');

        $location = WashLocation::factory()->create();
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'wash_location_id' => $location->id,
        ]);

        $this->actingAs($admin)
            ->put(route('settings.update'), [
                'company_name' => $location->name,
                'company_whatsapp' => $location->phone,
                'address' => $location->address,
                'district' => $location->district,
                'city' => $location->city,
                'state' => $location->state ?? 'SP',
                'theme' => AppSetting::THEME_LIGHT,
                'module_schedule' => '1',
                'logo' => UploadedFile::fake()->image('logo.png', 900, 500)->size(700),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $location->refresh();

        $this->assertNotNull($location->logo_path);
        Storage::disk('public')->assertExists($location->logo_path);
    }

    public function test_unit_logo_upload_rejects_unsupported_file_type(): void
    {
        Storage::fake('public');

        $location = WashLocation::factory()->create();
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'wash_location_id' => $location->id,
        ]);

        $this->actingAs($admin)
            ->from(route('settings.edit'))
            ->put(route('settings.update'), [
                'company_name' => $location->name,
                'company_whatsapp' => $location->phone,
                'address' => $location->address,
                'district' => $location->district,
                'city' => $location->city,
                'state' => $location->state ?? 'SP',
                'theme' => AppSetting::THEME_LIGHT,
                'module_schedule' => '1',
                'logo' => UploadedFile::fake()->create('logo.svg', 20, 'image/svg+xml'),
            ])
            ->assertRedirect(route('settings.edit'))
            ->assertSessionHasErrors('logo');

        $this->assertNull($location->fresh()->logo_path);
    }

    public function test_unit_logo_upload_rejects_oversized_dimensions(): void
    {
        Storage::fake('public');

        $location = WashLocation::factory()->create();
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'wash_location_id' => $location->id,
        ]);

        $this->actingAs($admin)
            ->from(route('settings.edit'))
            ->put(route('settings.update'), [
                'company_name' => $location->name,
                'company_whatsapp' => $location->phone,
                'address' => $location->address,
                'district' => $location->district,
                'city' => $location->city,
                'state' => $location->state ?? 'SP',
                'theme' => AppSetting::THEME_LIGHT,
                'module_schedule' => '1',
                'logo' => UploadedFile::fake()->image('logo.png', 3200, 800)->size(900),
            ])
            ->assertRedirect(route('settings.edit'))
            ->assertSessionHasErrors('logo');

        $this->assertNull($location->fresh()->logo_path);
    }

    public function test_admin_can_update_loyalty_program_settings(): void
    {
        $location = WashLocation::factory()->create();
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'wash_location_id' => $location->id,
        ]);
        $service = Service::factory()->create([
            'wash_location_id' => $location->id,
            'name' => 'Ducha simples',
            'category' => 'Lavagem',
            'active' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('settings.update'), [
                'company_name' => $location->name,
                'company_whatsapp' => $location->phone,
                'address' => $location->address,
                'district' => $location->district,
                'city' => $location->city,
                'state' => $location->state ?? 'SP',
                'theme' => AppSetting::THEME_LIGHT,
                'module_schedule' => '1',
                'loyalty_is_active' => '1',
                'loyalty_threshold' => 10,
                'loyalty_coupon_valid_days' => 45,
                'loyalty_count_scope' => LoyaltyProgram::COUNT_ANY,
                'loyalty_reward_type' => LoyaltyProgram::REWARD_FIXED_SERVICE,
                'loyalty_reward_service_id' => $service->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('loyalty_programs', [
            'wash_location_id' => $location->id,
            'is_active' => true,
            'threshold' => 10,
            'count_scope' => LoyaltyProgram::COUNT_ANY,
            'reward_type' => LoyaltyProgram::REWARD_FIXED_SERVICE,
            'reward_service_id' => $service->id,
            'coupon_valid_days' => 45,
        ]);
    }

    public function test_admin_can_enable_loyalty_program_without_selecting_reward_service(): void
    {
        $location = WashLocation::factory()->create();
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'wash_location_id' => $location->id,
        ]);

        $this->actingAs($admin)
            ->put(route('settings.update'), [
                'company_name' => $location->name,
                'company_whatsapp' => $location->phone,
                'address' => $location->address,
                'district' => $location->district,
                'city' => $location->city,
                'state' => $location->state ?? 'SP',
                'theme' => AppSetting::THEME_LIGHT,
                'module_schedule' => '1',
                'loyalty_is_active' => '1',
                'loyalty_threshold' => 10,
                'loyalty_coupon_valid_days' => 30,
                'loyalty_count_scope' => LoyaltyProgram::COUNT_ANY,
                'loyalty_reward_type' => LoyaltyProgram::REWARD_FIXED_SERVICE,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('loyalty_programs', [
            'wash_location_id' => $location->id,
            'is_active' => true,
            'reward_type' => LoyaltyProgram::REWARD_FIXED_SERVICE,
            'reward_service_id' => null,
        ]);
    }

    public function test_admin_can_update_operator_permissions_and_audit_change(): void
    {
        $location = WashLocation::factory()->create();
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'wash_location_id' => $location->id,
        ]);

        $this->actingAs($admin)
            ->put(route('settings.update'), [
                'company_name' => $location->name,
                'company_whatsapp' => $location->phone,
                'address' => $location->address,
                'district' => $location->district,
                'city' => $location->city,
                'state' => $location->state ?? 'SP',
                'theme' => AppSetting::THEME_LIGHT,
                'module_schedule' => '1',
                'role_permissions' => [
                    User::ROLE_OPERATOR => [
                        AccessControl::VIEW_WASH_ORDERS => '1',
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('role_permission_settings', [
            'wash_location_id' => $location->id,
            'role' => User::ROLE_OPERATOR,
            'permission' => AccessControl::VIEW_WASH_ORDERS,
            'allowed' => true,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'wash_location_id' => $location->id,
            'user_id' => $admin->id,
            'action' => AuditLog::ACTION_ROLE_PERMISSIONS_UPDATED,
            'subject_label' => $location->name,
        ]);
    }

    public function test_cash_and_credit_links_are_hidden_when_modules_are_disabled(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        AppSetting::setValue('module_cash_register', false);
        AppSetting::setValue('module_credit_receivables', false);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Caixa')
            ->assertDontSee('Fiado')
            ->assertSee('Financeiro');
    }

    public function test_schedule_link_is_hidden_when_module_is_disabled(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        AppSetting::setValue('module_schedule', false);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('href="'.route('schedule.index').'"', false);
    }

    public function test_cash_and_credit_links_are_visible_when_modules_are_enabled(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        AppSetting::setValue('module_cash_register', true);
        AppSetting::setValue('module_credit_receivables', true);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Caixa')
            ->assertSee('Fiado');
    }
}
