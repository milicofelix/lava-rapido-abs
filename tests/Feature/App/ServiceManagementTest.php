<?php

namespace Tests\Feature\App;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_service(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)->post(route('services.store'), [
            'name' => 'Lavagem completa',
            'description' => 'Lavagem externa, interna e acabamento.',
            'base_price' => 80,
            'estimated_minutes' => 70,
            'active' => '1',
            'category' => 'Lavagem',
        ])->assertRedirect(route('services.index'));

        $this->assertDatabaseHas('services', [
            'name' => 'Lavagem completa',
            'base_price' => 80,
            'estimated_minutes' => 70,
            'active' => true,
        ]);
    }
}
