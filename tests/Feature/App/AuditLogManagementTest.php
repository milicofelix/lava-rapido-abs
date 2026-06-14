<?php

namespace Tests\Feature\App;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\User;
use App\Models\WashLocation;
use App\Models\WashOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_audit_log_page(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        AuditLog::query()->create([
            'wash_location_id' => $admin->wash_location_id,
            'user_id' => $admin->id,
            'action' => AuditLog::ACTION_CUSTOMER_UPDATED,
            'subject_label' => 'Maria Cliente',
            'description' => 'Administrador editou o cliente Maria Cliente.',
        ]);

        $this->actingAs($admin)
            ->get(route('audit-logs.index'))
            ->assertOk()
            ->assertSee('Auditoria')
            ->assertSee('Cliente editado')
            ->assertSee('Maria Cliente');
    }

    public function test_attendant_cannot_view_audit_log_page(): void
    {
        $attendant = User::factory()->create(['role' => User::ROLE_ATTENDANT]);

        $this->actingAs($attendant)
            ->get(route('audit-logs.index'))
            ->assertForbidden();
    }

    public function test_status_change_creates_audit_log(): void
    {
        $admin = User::factory()->create([
            'name' => 'Carlos Operacional',
            'role' => User::ROLE_ADMIN,
        ]);
        $washOrder = WashOrder::factory()->create([
            'code' => 'ABS-AUDIT-001',
            'status' => WashOrder::STATUS_AWAITING,
        ]);

        $this->actingAs($admin)
            ->patch(route('wash-orders.update-status', $washOrder), [
                'status' => WashOrder::STATUS_WASHING,
                'notes' => 'Inicio da lavagem.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => AuditLog::ACTION_WASH_ORDER_STATUS_CHANGED,
            'subject_label' => 'ABS-AUDIT-001',
        ]);

        $this->actingAs($admin)
            ->get(route('audit-logs.index', ['search' => 'ABS-AUDIT-001']))
            ->assertOk()
            ->assertSee('Carlos Operacional alterou a lavagem ABS-AUDIT-001')
            ->assertSee('Status alterado');
    }

    public function test_owner_sees_only_own_location_audit_logs(): void
    {
        $locationA = WashLocation::factory()->create(['name' => 'Unidade A']);
        $locationB = WashLocation::factory()->create(['name' => 'Unidade B']);
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
            'wash_location_id' => $locationA->id,
        ]);
        $customerA = Customer::factory()->create([
            'wash_location_id' => $locationA->id,
            'name' => 'Cliente da Unidade A',
        ]);
        $customerB = Customer::factory()->create([
            'wash_location_id' => $locationB->id,
            'name' => 'Cliente da Unidade B',
        ]);

        AuditLog::query()->create([
            'wash_location_id' => $locationA->id,
            'user_id' => $owner->id,
            'action' => AuditLog::ACTION_CUSTOMER_UPDATED,
            'subject_type' => Customer::class,
            'subject_id' => $customerA->id,
            'subject_label' => $customerA->name,
            'description' => 'Editou cliente da unidade A.',
        ]);

        AuditLog::query()->create([
            'wash_location_id' => $locationB->id,
            'action' => AuditLog::ACTION_CUSTOMER_UPDATED,
            'subject_type' => Customer::class,
            'subject_id' => $customerB->id,
            'subject_label' => $customerB->name,
            'description' => 'Editou cliente da unidade B.',
        ]);

        $this->actingAs($owner)
            ->get(route('audit-logs.index'))
            ->assertOk()
            ->assertSee('Cliente da Unidade A')
            ->assertDontSee('Cliente da Unidade B');
    }
}
