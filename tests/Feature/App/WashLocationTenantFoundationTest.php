<?php

namespace Tests\Feature\App;

use App\Models\User;
use App\Models\WashLocation;
use App\Models\WashLocationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WashLocationTenantFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_approving_request_creates_owner_user_linked_to_location(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $request = WashLocationRequest::query()->create([
            'responsible_name' => 'Maria Silva',
            'email' => 'maria@lavaexemplo.com',
            'phone' => '(11) 99999-0000',
            'business_name' => 'Lava Rápido Exemplo',
            'zip_code' => '08700-000',
            'address' => 'Rua das Águas, 100',
            'district' => 'Centro',
            'city' => 'Mogi das Cruzes',
            'state' => 'SP',
            'employees_count' => 3,
            'status' => WashLocationRequest::STATUS_PENDING_REVIEW,
        ]);

        $this->actingAs($superAdmin)
            ->patch(route('super-admin.location-requests.approve', $request), [
                'decision_notes' => 'Aprovado para teste.',
            ])
            ->assertRedirect(route('super-admin.location-requests.show', $request));

        $location = WashLocation::query()->where('name', 'Lava Rápido Exemplo')->firstOrFail();
        $owner = User::query()->where('email', 'maria@lavaexemplo.com')->firstOrFail();

        $this->assertSame(User::ROLE_OWNER, $owner->role);
        $this->assertTrue($owner->belongsToWashLocation($location));
        $this->assertSame($location->id, $owner->wash_location_id);
    }

    public function test_approval_reuses_existing_user_as_owner_of_location(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $existingUser = User::factory()->create([
            'email' => 'dono@lavaexemplo.com',
            'role' => User::ROLE_ATTENDANT,
            'wash_location_id' => null,
        ]);

        $request = WashLocationRequest::query()->create([
            'responsible_name' => 'Dono Existente',
            'email' => 'dono@lavaexemplo.com',
            'phone' => '(11) 98888-7777',
            'business_name' => 'Auto Spa Existente',
            'zip_code' => '08700-000',
            'address' => 'Avenida Teste, 200',
            'district' => 'Centro',
            'city' => 'Mogi das Cruzes',
            'state' => 'SP',
            'employees_count' => 4,
            'status' => WashLocationRequest::STATUS_PENDING_REVIEW,
        ]);

        $this->actingAs($superAdmin)
            ->patch(route('super-admin.location-requests.approve', $request), [
                'decision_notes' => 'Aprovado com usuário existente.',
            ])
            ->assertRedirect(route('super-admin.location-requests.show', $request));

        $existingUser->refresh();
        $location = WashLocation::query()->where('name', 'Auto Spa Existente')->firstOrFail();

        $this->assertSame(User::ROLE_OWNER, $existingUser->role);
        $this->assertSame($location->id, $existingUser->wash_location_id);
    }
}
