<?php

namespace Tests\Feature\App;

use App\Models\User;
use App\Models\WashLocation;
use App\Models\WashLocationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicWashLocationRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitor_can_open_public_location_request_form(): void
    {
        $this->get(route('public.location-requests.create'))
            ->assertOk()
            ->assertSee('Cadastre seu lava-rápido no AutoFlow')
            ->assertSee('Trial sob aprovação')
            ->assertSee('Solicitação de cadastro')
            ->assertSee('Enviar solicitação')
            ->assertSee('Senha de primeiro acesso')
            ->assertSee('data-mask="phone"', false)
            ->assertSee('data-mask="cep"', false)
            ->assertSee('data-viacep-trigger', false)
            ->assertSee('data-address-field="address"', false)
            ->assertSee('name="address_number"', false)
            ->assertSee('data-onboarding-tour', false)
            ->assertSee('public.location-requests.create.v1')
            ->assertSee('data-tour="location-request-header"', false)
            ->assertSee('data-tour="location-request-flow"', false)
            ->assertSee('data-tour="location-request-form"', false)
            ->assertSee('data-tour="location-request-owner"', false)
            ->assertSee('data-tour="location-request-password"', false)
            ->assertSee('data-tour="location-request-business"', false)
            ->assertSee('data-tour="location-request-address"', false)
            ->assertSee('data-tour="location-request-terms"', false)
            ->assertSee('data-tour="location-request-submit"', false);
    }

    public function test_authenticated_user_sees_panel_action_on_location_request_form(): void
    {
        $user = User::factory()->create(['name' => 'Adriano Logado']);

        $this->actingAs($user)
            ->get(route('public.location-requests.create'))
            ->assertOk()
            ->assertSee('Adriano Logado')
            ->assertSee('Painel')
            ->assertSee(route('dashboard'), false)
            ->assertDontSee('>Entrar<', false);
    }

    public function test_visitor_can_submit_location_request_as_pending_review(): void
    {
        $payload = [
            'responsible_name' => 'Adriano Freitas',
            'email' => 'dono@lavacentral.com.br',
            'password' => 'senha-segura-123',
            'password_confirmation' => 'senha-segura-123',
            'phone' => '(11) 98888-2200',
            'business_name' => 'Lava Rapido Central',
            'zip_code' => '08000-000',
            'address' => 'Av. das Nacoes',
            'address_number' => '1580',
            'district' => 'Centro',
            'city' => 'Sao Paulo',
            'state' => 'sp',
            'employees_count' => 4,
            'notes' => 'Quero testar o AutoFlow.',
            'accept_terms' => '1',
        ];

        $this->post(route('public.location-requests.store'), $payload)
            ->assertRedirect(route('public.location-requests.thank-you'));

        $this->assertDatabaseHas('wash_location_requests', [
            'responsible_name' => 'Adriano Freitas',
            'email' => 'dono@lavacentral.com.br',
            'phone' => '(11) 98888-2200',
            'business_name' => 'Lava Rapido Central',
            'city' => 'Sao Paulo',
            'state' => 'SP',
            'status' => WashLocationRequest::STATUS_PENDING_REVIEW,
        ]);

        $this->assertNotSame('senha-segura-123', WashLocationRequest::query()->firstOrFail()->owner_password);

        $this->assertDatabaseMissing('wash_locations', [
            'name' => 'Lava Rapido Central',
        ]);
    }

    public function test_location_request_requires_true_information_confirmation(): void
    {
        $this->post(route('public.location-requests.store'), [
            'responsible_name' => 'Adriano Freitas',
            'email' => 'dono@lavacentral.com.br',
            'password' => 'senha-segura-123',
            'password_confirmation' => 'senha-segura-123',
            'phone' => '(11) 98888-2200',
            'business_name' => 'Lava Rapido Central',
            'address' => 'Av. das Nacoes',
            'address_number' => '1580',
            'city' => 'Sao Paulo',
            'state' => 'SP',
        ])->assertSessionHasErrors('accept_terms');
    }

    public function test_pending_request_blocks_duplicate_email_or_phone(): void
    {
        WashLocationRequest::query()->create([
            'responsible_name' => 'Primeiro Dono',
            'email' => 'dono@lavacentral.com.br',
            'phone' => '(11) 98888-2200',
            'business_name' => 'Lava Rapido Central',
            'address' => 'Av. das Nacoes',
            'address_number' => '1580',
            'city' => 'Sao Paulo',
            'state' => 'SP',
            'status' => WashLocationRequest::STATUS_PENDING_REVIEW,
        ]);

        $this->from(route('public.location-requests.create'))
            ->post(route('public.location-requests.store'), [
                'responsible_name' => 'Outro Dono',
                'email' => 'outro@lavacentral.com.br',
                'password' => 'senha-segura-123',
                'password_confirmation' => 'senha-segura-123',
                'phone' => '11988882200',
                'business_name' => 'Outro Lava Rapido',
                'address' => 'Rua B',
                'address_number' => '20',
                'city' => 'Sao Paulo',
                'state' => 'SP',
                'accept_terms' => '1',
            ])
            ->assertRedirect(route('public.location-requests.create'))
            ->assertSessionHasErrors('email');
    }

    public function test_public_map_links_to_location_request_form(): void
    {
        WashLocation::query()->create([
            'name' => 'Lava Rapido Central',
            'address' => 'Av. das Nacoes',
            'address_number' => '1580',
            'district' => 'Centro',
            'city' => 'Sao Paulo',
            'status' => WashLocation::STATUS_OPEN,
            'latitude' => -23.54891,
            'longitude' => -46.63412,
        ]);

        $this->get(route('public.locations.index'))
            ->assertOk()
            ->assertSee('Cadastrar lava-rápido')
            ->assertSee(route('public.location-requests.create'), false);
    }
}
