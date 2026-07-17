<?php

namespace Tests\Feature\App;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_services_index_exposes_guided_tour(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        Service::factory()->create([
            'wash_location_id' => $user->wash_location_id,
            'name' => 'Lavagem completa',
            'category' => 'Lavagem',
        ]);

        $this->actingAs($user)
            ->get(route('services.index'))
            ->assertOk()
            ->assertSee('data-onboarding-tour', false)
            ->assertSee('services.index.v1')
            ->assertSee('data-tour="services-search"', false)
            ->assertSee('data-tour="services-indicators"', false)
            ->assertSee('data-tour="services-list"', false)
            ->assertSee('data-tour="services-row"', false);
    }

    public function test_service_form_exposes_guided_tour(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($user)
            ->get(route('services.create'))
            ->assertOk()
            ->assertSee('data-onboarding-tour', false)
            ->assertSee('services.create.v1')
            ->assertSee('data-tour="service-form-name"', false)
            ->assertSee('data-tour="service-form-category"', false)
            ->assertSee('data-tour="service-form-price"', false)
            ->assertSee('data-tour="service-form-active"', false);
    }

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
