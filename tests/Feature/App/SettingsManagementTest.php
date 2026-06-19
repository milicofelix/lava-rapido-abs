<?php

namespace Tests\Feature\App;

use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\WashLocation;
use App\Support\Access\AccessControl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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
            ->assertSee('Configuracoes')
            ->assertSee('Habilitar Caixa')
            ->assertSee('Habilitar Fiado')
            ->assertSee('Tema do painel')
            ->assertDontSee('URL do Google Maps');
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
