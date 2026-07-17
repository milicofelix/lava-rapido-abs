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
            ->assertSee('Pendente de análise')
            ->assertSee('data-onboarding-tour', false)
            ->assertSee('super-admin.location-requests.index.v1')
            ->assertSee('data-tour="super-requests-intro"', false)
            ->assertSee('data-tour="super-requests-summary"', false)
            ->assertSee('data-tour="super-requests-filters"', false)
            ->assertSee('data-tour="super-requests-list"', false)
            ->assertSee('data-tour="super-requests-row"', false);
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
            ->assertSee('Quero testar o AutoFlow.')
            ->assertSee('Carregar latitude/longitude')
            ->assertSee('URL do Google Maps')
            ->assertSee('data-onboarding-tour', false)
            ->assertSee('super-admin.location-requests.show.v1')
            ->assertSee('data-tour="super-request-navigation"', false)
            ->assertSee('data-tour="super-request-detail"', false)
            ->assertSee('data-tour="super-request-approval"', false)
            ->assertSee('data-tour="super-request-geocode"', false)
            ->assertSee('data-tour="super-request-coordinates"', false)
            ->assertSee('data-tour="super-request-rejection"', false)
            ->assertSee('data-tour="super-request-data"', false);
    }

    public function test_super_admin_dashboard_redirects_to_location_requests(): void
    {
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->actingAs($superAdmin)
            ->get(route('dashboard'))
            ->assertRedirect(route('super-admin.location-requests.index'));
    }

    public function test_super_admin_menu_shows_product_admin_items_only(): void
    {
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.location-requests.index'))
            ->assertOk()
            ->assertSee('Solicitações de cadastros')
            ->assertDontSee('Kanban de Lavagens')
            ->assertDontSee('Lavagens')
            ->assertDontSee('Financeiro');
    }

    public function test_regular_admin_cannot_access_super_admin_requests(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('super-admin.location-requests.index'))
            ->assertForbidden();
    }
}
