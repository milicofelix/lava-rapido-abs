<?php

namespace Tests\Feature\App;

use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WashLocation;
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

    public function test_vehicle_index_has_guided_tour_and_search(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['name' => 'Cliente Veiculo']);
        Vehicle::factory()->for($customer)->create([
            'plate' => 'GUI1A23',
            'brand' => 'Toyota',
            'model' => 'Corolla',
        ]);

        $this->actingAs($user)
            ->get(route('vehicles.index', ['search' => 'GUI1A23']))
            ->assertOk()
            ->assertSee('GUI1A23')
            ->assertSee('Cliente Veiculo')
            ->assertSee('data-onboarding-tour', false)
            ->assertSee('vehicles.index.v1')
            ->assertSee('data-tour="vehicles-search"', false)
            ->assertSee('data-tour="vehicles-list"', false)
            ->assertSee('Gerenciando veículos');
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
            ->assertSee('data-onboarding-tour', false)
            ->assertSee('vehicles.create.v1')
            ->assertSee('data-tour="vehicle-form-brand"', false)
            ->assertSee('data-tour="vehicle-form-model"', false)
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

    public function test_vehicle_plate_can_repeat_in_different_locations(): void
    {
        $locationA = WashLocation::factory()->create(['name' => 'Lava Rapido A']);
        $locationB = WashLocation::factory()->create(['name' => 'Lava Rapido B']);
        $user = User::factory()->create(['wash_location_id' => $locationA->id]);
        $customerA = Customer::factory()->create(['wash_location_id' => $locationA->id]);
        $customerB = Customer::factory()->create(['wash_location_id' => $locationB->id]);

        Vehicle::factory()->create([
            'wash_location_id' => $locationB->id,
            'customer_id' => $customerB->id,
            'plate' => 'ABC1D23',
        ]);

        $this->actingAs($user)->post(route('vehicles.store'), [
            'customer_id' => $customerA->id,
            'plate' => 'abc1d23',
            'model' => 'Corolla',
            'brand' => 'Toyota',
            'color' => 'Prata',
            'type' => 'carro',
        ])->assertRedirect(route('vehicles.index'));

        $this->assertDatabaseHas('vehicles', [
            'wash_location_id' => $locationA->id,
            'customer_id' => $customerA->id,
            'plate' => 'ABC1D23',
        ]);

        $this->assertDatabaseHas('vehicles', [
            'wash_location_id' => $locationB->id,
            'customer_id' => $customerB->id,
            'plate' => 'ABC1D23',
        ]);
    }

    public function test_vehicle_plate_cannot_repeat_in_same_location(): void
    {
        $location = WashLocation::factory()->create();
        $user = User::factory()->create(['wash_location_id' => $location->id]);
        $customerA = Customer::factory()->create(['wash_location_id' => $location->id]);
        $customerB = Customer::factory()->create(['wash_location_id' => $location->id]);

        Vehicle::factory()->create([
            'wash_location_id' => $location->id,
            'customer_id' => $customerA->id,
            'plate' => 'ABC1D23',
        ]);

        $this->actingAs($user)
            ->from(route('vehicles.create'))
            ->post(route('vehicles.store'), [
                'customer_id' => $customerB->id,
                'plate' => 'abc1d23',
                'model' => 'Corolla',
                'brand' => 'Toyota',
                'color' => 'Prata',
                'type' => 'carro',
            ])
            ->assertRedirect(route('vehicles.create'))
            ->assertSessionHasErrors('plate');
    }
}
