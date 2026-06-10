<?php

namespace Tests\Feature\App;

use App\Models\Service;
use App\Models\User;
use App\Models\WashOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicWashTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_track_wash_order_by_public_code_without_login(): void
    {
        $washOrder = WashOrder::factory()->create([
            'code' => 'ABS-TRACK-1',
            'status' => WashOrder::STATUS_WASHING,
        ]);
        $service = Service::factory()->create([
            'name' => 'Lavagem completa',
            'base_price' => 80,
            'estimated_minutes' => 70,
        ]);

        $washOrder->services()->attach($service, [
            'service_name' => $service->name,
            'price' => $service->base_price,
            'estimated_minutes' => $service->estimated_minutes,
        ]);
        $washOrder->statusHistories()->create([
            'to_status' => WashOrder::STATUS_WASHING,
            'notes' => 'Lavagem iniciada.',
        ]);

        $this->get(route('tracking.show', 'ABS-TRACK-1'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Tracking')
                ->where('washOrder.code', 'ABS-TRACK-1')
                ->where('washOrder.vehicle.plate', $washOrder->vehicle->plate)
                ->where('washOrder.status_label', 'Lavando')
                ->where('washOrder.services.0.name', 'Lavagem completa')
            );
    }

    public function test_unknown_tracking_code_returns_not_found(): void
    {
        $this->get(route('tracking.show', 'ABS-NAO-EXISTE'))
            ->assertNotFound();
    }

    public function test_customer_can_track_wash_order_by_numeric_id(): void
    {
        $washOrder = WashOrder::factory()->create();

        $this->get(route('tracking.show', (string) $washOrder->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Tracking')
                ->where('washOrder.id', $washOrder->id)
                ->where('washOrder.vehicle.plate', $washOrder->vehicle->plate)
                ->where('washOrder.status_label', $washOrder->statusLabel())
            );
    }

    public function test_internal_wash_order_detail_shows_customer_tracking_link(): void
    {
        $user = User::factory()->create();
        $washOrder = WashOrder::factory()->create(['code' => 'ABS-LINK-1']);
        $washOrder->customer->update(['phone' => '(11) 98888-7777']);

        $this->actingAs($user)->get(route('wash-orders.show', $washOrder))
            ->assertOk()
            ->assertSee('Link do cliente')
            ->assertSee(route('tracking.show', 'ABS-LINK-1'))
            ->assertSee('Compartilhar via WhatsApp')
            ->assertSee('https://wa.me/5511988887777', false)
            ->assertSee(rawurlencode(route('tracking.show', 'ABS-LINK-1')), false);
    }
}
