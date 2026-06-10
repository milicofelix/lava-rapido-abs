<?php

namespace Tests\Feature\App;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

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
}
