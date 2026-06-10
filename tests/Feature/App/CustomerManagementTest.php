<?php

namespace Tests\Feature\App;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendant_can_create_customer(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('customers.store'), [
            'name' => 'Maria Silva',
            'phone' => '(11) 99999-0000',
            'email' => 'maria@example.com',
            'cpf' => '123.456.789-00',
            'notes' => 'Prefere contato por WhatsApp.',
        ])->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'name' => 'Maria Silva',
            'phone' => '(11) 99999-0000',
        ]);
    }

    public function test_customer_search_finds_vehicle_plate(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->hasVehicles(1, ['plate' => 'ABC1D23'])->create();

        $this->actingAs($user)->get(route('customers.index', ['search' => 'ABC1D23']))
            ->assertOk()
            ->assertSee($customer->name);
    }
}
