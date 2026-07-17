<?php

namespace Tests\Feature\App;

use App\Models\RolePermissionSetting;
use App\Models\User;
use App\Support\Access\AccessControl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_employees_index_exposes_guided_tour(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        User::factory()->create([
            'name' => 'Operador Tour',
            'role' => User::ROLE_OPERATOR,
            'wash_location_id' => $admin->wash_location_id,
        ]);

        $this->actingAs($admin)
            ->get(route('employees.index'))
            ->assertOk()
            ->assertSee('data-onboarding-tour', false)
            ->assertSee('employees.index.v1')
            ->assertSee('data-tour="employees-search"', false)
            ->assertSee('data-tour="employees-indicators"', false)
            ->assertSee('data-tour="employees-list"', false)
            ->assertSee('data-tour="employees-permission-audit"', false);
    }

    public function test_employee_form_exposes_guided_tour(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('employees.create'))
            ->assertOk()
            ->assertSee('data-onboarding-tour', false)
            ->assertSee('employees.create.v1')
            ->assertSee('data-tour="employee-form-name"', false)
            ->assertSee('data-tour="employee-form-email"', false)
            ->assertSee('data-tour="employee-form-role"', false)
            ->assertSee('data-tour="employee-form-password"', false);
    }

    public function test_admin_can_create_employee(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('employees.store'), [
            'name' => 'Joao Lavador',
            'email' => 'joao@lavaabs.test',
            'role' => 'operator',
            'password' => 'password',
        ])->assertRedirect(route('employees.index'));

        $employee = User::query()->where('email', 'joao@lavaabs.test')->firstOrFail();

        $this->assertSame('Joao Lavador', $employee->name);
        $this->assertSame('operator', $employee->role);
        $this->assertTrue(Hash::check('password', $employee->password));
    }

    public function test_admin_can_update_employee_without_changing_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create([
            'name' => 'Maria Caixa',
            'email' => 'maria@lavaabs.test',
            'role' => 'attendant',
            'password' => 'senha-antiga',
        ]);
        $originalPassword = $employee->password;

        $this->actingAs($admin)->put(route('employees.update', $employee), [
            'name' => 'Maria Atendimento',
            'email' => 'maria@lavaabs.test',
            'role' => 'operator',
            'password' => '',
        ])->assertRedirect(route('employees.index'));

        $employee->refresh();

        $this->assertSame('Maria Atendimento', $employee->name);
        $this->assertSame('operator', $employee->role);
        $this->assertSame($originalPassword, $employee->password);
    }

    public function test_employee_email_must_be_unique(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['email' => 'duplicado@lavaabs.test']);

        $this->actingAs($admin)->post(route('employees.store'), [
            'name' => 'Outro Funcionario',
            'email' => 'duplicado@lavaabs.test',
            'role' => 'operator',
            'password' => 'password',
        ])->assertSessionHasErrors('email');
    }

    public function test_employee_index_shows_visual_permission_audit(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $operator = User::factory()->create([
            'name' => 'Operador Kanban',
            'email' => 'operador@lavaabs.test',
            'role' => User::ROLE_OPERATOR,
            'wash_location_id' => $admin->wash_location_id,
        ]);

        RolePermissionSetting::setForLocation((int) $admin->wash_location_id, User::ROLE_OPERATOR, [
            AccessControl::VIEW_WASH_ORDERS => true,
            AccessControl::CREATE_WASH_ORDER => false,
            AccessControl::SEND_WASH_NOTIFICATIONS => false,
        ]);

        $this->actingAs($admin)
            ->get(route('employees.index'))
            ->assertOk()
            ->assertSee('Auditoria de permissões')
            ->assertSee('Operador Kanban')
            ->assertSee('Possui exceção configurada')
            ->assertSee('+ Visualizar detalhes da lavagem')
            ->assertSee('Bloqueadas pela configuração')
            ->assertSee('Abrir e listar lavagens')
            ->assertSee('Enviar notificações manuais ao cliente');
    }
}
