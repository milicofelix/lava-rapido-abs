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
}
