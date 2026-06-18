<?php

namespace Tests\Feature\App;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendant_can_create_vehicle_for_customer(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();

        $this->actingAs($user)->post(route('vehicles.store'), [
            'customer_id' => $customer->id,
            'plate' => 'abc1d23',
            'model' => 'Corolla',
            'brand' => 'Toyota',
            'color' => 'Prata',
            'type' => 'carro',
        ])->assertRedirect(route('vehicles.index'));

        $this->assertDatabaseHas('vehicles', [
            'customer_id' => $customer->id,
            'plate' => 'ABC1D23',
            'model' => 'Corolla',
        ]);
    }

    public function test_vehicle_form_uses_brand_and_dependent_model_selects(): void
    {
        $user = User::factory()->create();
        Customer::factory()->create();

        $this->actingAs($user)
            ->get(route('vehicles.create'))
            ->assertOk()
            ->assertSee('data-vehicle-brand', false)
            ->assertSee('data-vehicle-model', false)
            ->assertSee('data-vehicle-models', false)
            ->assertSee('Toyota')
            ->assertSee('Corolla')
            ->assertSee('HB20')
            ->assertSee('Preenchido automaticamente conforme o modelo.');
    }

    public function test_vehicle_model_must_belong_to_selected_brand(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();

        $this->actingAs($user)
            ->from(route('vehicles.create'))
            ->post(route('vehicles.store'), [
                'customer_id' => $customer->id,
                'plate' => 'abc1d23',
                'model' => 'HB20',
                'brand' => 'Fiat',
                'color' => 'Prata',
                'type' => 'carro',
            ])
            ->assertRedirect(route('vehicles.create'))
            ->assertSessionHasErrors('model');

        $this->assertDatabaseMissing('vehicles', [
            'plate' => 'ABC1D23',
        ]);
    }
}
