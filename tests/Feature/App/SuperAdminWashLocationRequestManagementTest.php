<?php

namespace Tests\Feature\App;

use App\Models\User;
use App\Models\WashLocationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminWashLocationRequestManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_location_requests_page(): void
    {
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        WashLocationRequest::factory()->create([
            'responsible_name' => 'Adriano Freitas',
            'business_name' => 'Lava Rapido Central',
            'city' => 'Sao Paulo',
            'state' => 'SP',
            'status' => WashLocationRequest::STATUS_PENDING_REVIEW,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.location-requests.index'))
            ->assertOk()
            ->assertSee('Solicitações de lava-rápidos')
            ->assertSee('Lava Rapido Central')
            ->assertSee('Pendente de análise');
    }

    public function test_super_admin_can_filter_requests_by_status(): void
    {
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        WashLocationRequest::factory()->create([
            'business_name' => 'Lava Pendente',
            'status' => WashLocationRequest::STATUS_PENDING_REVIEW,
        ]);

        WashLocationRequest::factory()->create([
            'business_name' => 'Lava Aprovado',
            'status' => WashLocationRequest::STATUS_APPROVED,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.location-requests.index', ['status' => WashLocationRequest::STATUS_APPROVED]))
            ->assertOk()
            ->assertSee('Lava Aprovado')
            ->assertDontSee('Lava Pendente');
    }

    public function test_super_admin_can_open_request_details(): void
    {
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $request = WashLocationRequest::factory()->create([
            'responsible_name' => 'Adriano Freitas',
            'email' => 'adriano@autoflow.com.br',
            'phone' => '(11) 98888-2200',
            'business_name' => 'Lava Rapido Central',
            'address' => 'Av. das Nacoes, 1580',
            'city' => 'Sao Paulo',
            'state' => 'SP',
            'notes' => 'Quero testar o AutoFlow.',
        ]);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.location-requests.show', $request))
            ->assertOk()
            ->assertSee('Detalhes da solicitação')
            ->assertSee('Lava Rapido Central')
            ->assertSee('Adriano Freitas')
            ->assertSee('Quero testar o AutoFlow.');
    }

    public function test_regular_admin_cannot_access_super_admin_requests(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('super-admin.location-requests.index'))
            ->assertForbidden();
    }
}
