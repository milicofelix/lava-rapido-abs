<?php

namespace Tests\Feature\App;

use App\Models\AppSetting;
use App\Models\User;
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
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->put(route('settings.update'), [
                'company_name' => 'Auto Spa Teste',
                'company_whatsapp' => '(11) 98888-7777',
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
