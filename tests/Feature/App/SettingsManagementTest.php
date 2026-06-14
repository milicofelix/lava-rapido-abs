<?php

namespace Tests\Feature\App;

use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\WashLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('Tema do painel');
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
                'address' => 'Rua Premium, 123',
                'district' => 'Centro',
                'city' => 'Sao Paulo',
                'state' => 'SP',
                'latitude' => -23.54891,
                'longitude' => -46.63412,
                'opening_hours' => 'Seg a sex: 08:00 as 18:00',
                'theme' => AppSetting::THEME_DARK,
                'module_cash_register' => '1',
                'module_credit_receivables' => '1',
            ])
            ->assertRedirect();

        $this->assertSame('Auto Spa Teste', AppSetting::getValue('company_name'));
        $this->assertSame('(11) 98888-7777', AppSetting::getValue('company_whatsapp'));
        $this->assertSame(AppSetting::THEME_DARK, AppSetting::theme());
        $this->assertTrue(AppSetting::isModuleEnabled('module_cash_register'));
        $this->assertTrue(AppSetting::isModuleEnabled('module_credit_receivables'));

        $location->refresh();

        $this->assertSame('Auto Spa Teste', $location->name);
        $this->assertSame('Auto Spa Teste Ltda', $location->legal_name);
        $this->assertSame('12.345.678/0001-90', $location->document);
        $this->assertSame('Rua Premium, 123', $location->address);
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
