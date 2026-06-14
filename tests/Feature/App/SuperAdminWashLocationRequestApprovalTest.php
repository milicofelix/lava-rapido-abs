<?php

namespace Tests\Feature\App;

use App\Models\User;
use App\Models\WashLocation;
use App\Models\WashLocationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SuperAdminWashLocationRequestApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_approve_request_and_start_trial(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'wash_location_id' => null,
        ]);

        $request = WashLocationRequest::factory()->create([
            'business_name' => 'Lava Rapido Central',
            'address' => 'Av. das Nacoes, 1580',
            'district' => 'Centro',
            'city' => 'Sao Paulo',
            'state' => 'SP',
            'phone' => '(11) 98888-2200',
            'status' => WashLocationRequest::STATUS_PENDING_REVIEW,
        ]);

        $this->actingAs($superAdmin)
            ->patch(route('super-admin.location-requests.approve', $request), [
                'latitude' => -23.54891,
                'longitude' => -46.63412,
                'decision_notes' => 'Dados conferidos por contato manual.',
            ])
            ->assertRedirect(route('super-admin.location-requests.show', $request));

        $request->refresh();

        $this->assertSame(WashLocationRequest::STATUS_APPROVED, $request->status);
        $this->assertNotNull($request->wash_location_id);
        $this->assertSame($superAdmin->id, $request->decided_by_user_id);
        $this->assertSame('Dados conferidos por contato manual.', $request->decision_notes);

        $location = WashLocation::query()
            ->where('approved_location_request_id', $request->id)
            ->firstOrFail();

        $this->assertSame('Lava Rapido Central', $location->name);
        $this->assertSame('Sao Paulo', $location->city);
        $this->assertSame('SP', $location->state);
        $this->assertSame('-23.5489100', (string) $location->latitude);
        $this->assertSame('-46.6341200', (string) $location->longitude);
        $this->assertSame(WashLocation::ACCOUNT_STATUS_TRIAL, $location->account_status);
        $this->assertTrue($location->public_visible);
        $this->assertNotNull($location->trial_started_at);
        $this->assertNotNull($location->trial_ends_at);
        $this->assertSame($request->id, $location->approved_location_request_id);

        foreach ([
            'Lavagem completa',
            'Ducha simples',
            'Ducha + aspiração',
            'Cera',
            'Higienização interna',
            'Lavagem de motor',
            'Polimento',
            'Cristalização',
            'Lavagem de moto',
        ] as $serviceName) {
            $this->assertDatabaseHas('services', [
                'wash_location_id' => $location->id,
                'name' => $serviceName,
                'active' => true,
            ]);
        }
    }

    public function test_approval_uses_requested_owner_password_for_first_access(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'wash_location_id' => null,
        ]);

        $request = WashLocationRequest::factory()->create([
            'responsible_name' => 'Dono com Senha',
            'email' => 'dono-com-senha@lavacentral.com.br',
            'owner_password' => 'senha-segura-123',
            'status' => WashLocationRequest::STATUS_PENDING_REVIEW,
        ]);

        $this->actingAs($superAdmin)
            ->patch(route('super-admin.location-requests.approve', $request), [
                'latitude' => -23.54891,
                'longitude' => -46.63412,
            ])
            ->assertRedirect(route('super-admin.location-requests.show', $request));

        $owner = User::query()
            ->where('email', 'dono-com-senha@lavacentral.com.br')
            ->firstOrFail();

        $this->assertSame(User::ROLE_OWNER, $owner->role);
        $this->assertTrue(Hash::check('senha-segura-123', $owner->password));
    }

    public function test_super_admin_can_define_owner_password_when_request_has_no_password(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'wash_location_id' => null,
        ]);

        $request = WashLocationRequest::factory()->create([
            'responsible_name' => 'Dono Antigo',
            'email' => 'dono-antigo@lavacentral.com.br',
            'owner_password' => null,
            'status' => WashLocationRequest::STATUS_PENDING_REVIEW,
        ]);

        $this->actingAs($superAdmin)
            ->patch(route('super-admin.location-requests.approve', $request), [
                'latitude' => -23.54891,
                'longitude' => -46.63412,
                'owner_password' => 'senha-antiga-123',
                'owner_password_confirmation' => 'senha-antiga-123',
            ])
            ->assertRedirect(route('super-admin.location-requests.show', $request));

        $owner = User::query()
            ->where('email', 'dono-antigo@lavacentral.com.br')
            ->firstOrFail();

        $this->assertTrue(Hash::check('senha-antiga-123', $owner->password));
    }

    public function test_approved_trial_location_appears_on_public_map(): void
    {
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $request = WashLocationRequest::factory()->create([
            'business_name' => 'Lava Trial Publico',
            'status' => WashLocationRequest::STATUS_PENDING_REVIEW,
        ]);

        $this->actingAs($superAdmin)
            ->patch(route('super-admin.location-requests.approve', $request), [
                'latitude' => -23.54891,
                'longitude' => -46.63412,
            ]);

        $this->get(route('public.locations.index'))
            ->assertOk()
            ->assertSee('Lava Trial Publico')
            ->assertSee('-23.54891')
            ->assertSee('-46.63412');
    }

    public function test_super_admin_can_approve_request_with_google_maps_url(): void
    {
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $request = WashLocationRequest::factory()->create([
            'business_name' => 'Lava Rapido do Adilson',
            'address' => 'Av. Nordestina, 4660',
            'district' => 'Vila Nova Curuca',
            'city' => 'Sao Paulo',
            'state' => 'SP',
            'status' => WashLocationRequest::STATUS_PENDING_REVIEW,
        ]);
        $mapsUrl = 'https://www.google.com/maps/place/Av.+Nordestina,+4660+-+Vila+Nova+Curuca,+S%C3%A3o+Paulo+-+SP,+08032-000/@-23.5191405,-46.4207678,17z/data=!3m1!4b1!4m6!3m5!1s0x94ce640cd8043355:0x58a019a956587895!8m2!3d-23.5191405!4d-46.4207678!16s%2Fg%2F11c4dryd_5?entry=ttu';

        $this->actingAs($superAdmin)
            ->patch(route('super-admin.location-requests.approve', $request), [
                'google_maps_url' => $mapsUrl,
            ])
            ->assertRedirect(route('super-admin.location-requests.show', $request));

        $location = WashLocation::query()
            ->where('approved_location_request_id', $request->id)
            ->firstOrFail();

        $this->assertSame('-23.5191405', (string) $location->latitude);
        $this->assertSame('-46.4207678', (string) $location->longitude);
    }

    public function test_super_admin_can_load_coordinates_by_ajax(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                [
                    'lat' => '-23.5191405',
                    'lon' => '-46.4207678',
                ],
            ]),
        ]);

        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $request = WashLocationRequest::factory()->create([
            'address' => 'Av. Nordestina, 4660',
            'district' => 'Vila Nova Curuca',
            'city' => 'Sao Paulo',
            'state' => 'SP',
            'zip_code' => '08032-000',
            'status' => WashLocationRequest::STATUS_PENDING_REVIEW,
        ]);

        $this->actingAs($superAdmin)
            ->postJson(route('super-admin.location-requests.geocode', $request))
            ->assertOk()
            ->assertJson([
                'fallback_required' => false,
                'latitude' => -23.5191405,
                'longitude' => -46.4207678,
            ]);
    }

    public function test_geocode_endpoint_returns_fallback_instructions_when_address_is_not_found(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([]),
        ]);

        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $request = WashLocationRequest::factory()->create([
            'address' => 'Rua Sem Resultado, 999',
            'city' => 'Sao Paulo',
            'state' => 'SP',
            'status' => WashLocationRequest::STATUS_PENDING_REVIEW,
        ]);

        $this->actingAs($superAdmin)
            ->postJson(route('super-admin.location-requests.geocode', $request))
            ->assertOk()
            ->assertJson([
                'fallback_required' => true,
            ])
            ->assertJsonStructure(['message', 'maps_url']);
    }

    public function test_super_admin_can_approve_request_with_address_geocoding(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                [
                    'lat' => '-23.5191405',
                    'lon' => '-46.4207678',
                ],
            ]),
        ]);

        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $request = WashLocationRequest::factory()->create([
            'business_name' => 'Lava Rapido Geocodificado',
            'address' => 'Av. Nordestina, 4660',
            'district' => 'Vila Nova Curuca',
            'city' => 'Sao Paulo',
            'state' => 'SP',
            'zip_code' => '08032-000',
            'status' => WashLocationRequest::STATUS_PENDING_REVIEW,
        ]);

        $this->actingAs($superAdmin)
            ->patch(route('super-admin.location-requests.approve', $request))
            ->assertRedirect(route('super-admin.location-requests.show', $request));

        $location = WashLocation::query()
            ->where('approved_location_request_id', $request->id)
            ->firstOrFail();

        $this->assertSame('-23.5191405', (string) $location->latitude);
        $this->assertSame('-46.4207678', (string) $location->longitude);
    }

    public function test_approval_requires_real_map_coordinates(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([]),
        ]);

        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $request = WashLocationRequest::factory()->create([
            'status' => WashLocationRequest::STATUS_PENDING_REVIEW,
        ]);

        $this->actingAs($superAdmin)
            ->patch(route('super-admin.location-requests.approve', $request), [
                'decision_notes' => 'Sem coordenadas.',
            ])
            ->assertSessionHasErrors(['latitude', 'longitude']);

        $this->assertSame(WashLocationRequest::STATUS_PENDING_REVIEW, $request->fresh()->status);
        $this->assertDatabaseCount('wash_locations', 0);
    }

    public function test_super_admin_can_reject_request_without_creating_location(): void
    {
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $request = WashLocationRequest::factory()->create([
            'business_name' => 'Lava Rejeitado',
            'status' => WashLocationRequest::STATUS_PENDING_REVIEW,
        ]);

        $this->actingAs($superAdmin)
            ->patch(route('super-admin.location-requests.reject', $request), [
                'decision_notes' => 'Endereço não validado.',
            ])
            ->assertRedirect(route('super-admin.location-requests.show', $request));

        $request->refresh();

        $this->assertSame(WashLocationRequest::STATUS_REJECTED, $request->status);
        $this->assertNull($request->wash_location_id);
        $this->assertDatabaseMissing('wash_locations', [
            'name' => 'Lava Rejeitado',
        ]);
    }

    public function test_reject_requires_decision_notes(): void
    {
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $request = WashLocationRequest::factory()->create();

        $this->actingAs($superAdmin)
            ->patch(route('super-admin.location-requests.reject', $request), [
                'decision_notes' => '',
            ])
            ->assertSessionHasErrors('decision_notes');

        $this->assertSame(WashLocationRequest::STATUS_PENDING_REVIEW, $request->fresh()->status);
    }
}
