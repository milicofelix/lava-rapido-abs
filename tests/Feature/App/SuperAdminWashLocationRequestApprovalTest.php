<?php

namespace Tests\Feature\App;

use App\Models\User;
use App\Models\WashLocation;
use App\Models\WashLocationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->assertSame(WashLocation::ACCOUNT_STATUS_TRIAL, $location->account_status);
        $this->assertTrue($location->public_visible);
        $this->assertNotNull($location->trial_started_at);
        $this->assertNotNull($location->trial_ends_at);
        $this->assertSame($request->id, $location->approved_location_request_id);
    }

    public function test_approved_trial_location_appears_on_public_map(): void
    {
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $request = WashLocationRequest::factory()->create([
            'business_name' => 'Lava Trial Publico',
            'status' => WashLocationRequest::STATUS_PENDING_REVIEW,
        ]);

        $this->actingAs($superAdmin)
            ->patch(route('super-admin.location-requests.approve', $request));

        $this->get(route('public.locations.index'))
            ->assertOk()
            ->assertSee('Lava Trial Publico');
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
