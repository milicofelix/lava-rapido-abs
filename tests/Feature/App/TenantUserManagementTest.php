<?php

namespace Tests\Feature\App;

use App\Models\User;
use App\Models\WashLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TenantUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_cria_usuario_para_a_mesma_unidade(): void
    {
        $location = WashLocation::factory()->create(['name' => 'Lava Rapido Central']);
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $location->id,
        ]);

        $this->actingAs($owner)->post(route('employees.store'), [
            'name' => 'Maria Atendimento',
            'email' => 'maria@autoflow.test',
            'phone' => '(11) 90000-0000',
            'role' => User::ROLE_ATTENDANT,
            'password' => 'senha123',
        ])->assertRedirect(route('employees.index'));

        $user = User::query()->where('email', 'maria@autoflow.test')->firstOrFail();

        $this->assertSame($location->id, $user->wash_location_id);
        $this->assertSame(User::ROLE_ATTENDANT, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('senha123', $user->password));
    }

    public function test_admin_nao_cria_owner(): void
    {
        $location = WashLocation::factory()->create();
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'wash_location_id' => $location->id,
        ]);

        $this->actingAs($admin)->post(route('employees.store'), [
            'name' => 'Novo Owner',
            'email' => 'owner@autoflow.test',
            'role' => User::ROLE_OWNER,
            'password' => 'senha123',
        ])->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'owner@autoflow.test']);
    }

    public function test_owner_nao_desativa_a_si_mesmo(): void
    {
        $location = WashLocation::factory()->create();
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $location->id,
            'is_active' => true,
        ]);

        $this->actingAs($owner)->delete(route('employees.destroy', $owner))
            ->assertSessionHasErrors('employee');

        $this->assertTrue($owner->refresh()->is_active);
    }

    public function test_admin_nao_remove_owner_da_unidade(): void
    {
        $location = WashLocation::factory()->create();
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $location->id,
            'is_active' => true,
        ]);
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'wash_location_id' => $location->id,
            'is_active' => true,
        ]);

        $this->actingAs($admin)->delete(route('employees.destroy', $owner))
            ->assertForbidden();

        $this->assertTrue($owner->refresh()->is_active);
    }

    public function test_usuario_nao_ve_equipe_de_outra_unidade(): void
    {
        $ownLocation = WashLocation::factory()->create(['name' => 'Lava Rapido Central']);
        $otherLocation = WashLocation::factory()->create(['name' => 'Auto Spa Mogi']);
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $ownLocation->id,
        ]);

        User::factory()->create([
            'name' => 'Carlos Central',
            'role' => User::ROLE_OPERATOR,
            'wash_location_id' => $ownLocation->id,
        ]);
        User::factory()->create([
            'name' => 'Pedro Outra Unidade',
            'role' => User::ROLE_OPERATOR,
            'wash_location_id' => $otherLocation->id,
        ]);

        $this->actingAs($owner)->get(route('employees.index'))
            ->assertOk()
            ->assertSee('Carlos Central')
            ->assertDontSee('Pedro Outra Unidade');
    }
}
