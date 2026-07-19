<?php

namespace Tests\Feature\App;

use App\Models\LoyaltyCoupon;
use App\Models\LoyaltyProgram;
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
        $washOrder->washLocation->update(['logo_path' => 'wash-location-logos/tracking-unit.png']);
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
                ->where('feedUrl', route('tracking.feed', 'ABS-TRACK-1'))
                ->where('logoUrl', $washOrder->washLocation->fresh()->logoUrl())
                ->where('onboardingTour.key', 'tracking.show.v1')
                ->where('onboardingTour.steps.0.target', '[data-tour="tracking-header"]')
                ->where('onboardingTour.steps.1.target', '[data-tour="tracking-summary"]')
                ->where('onboardingTour.steps.2.target', '[data-tour="tracking-loyalty"]')
                ->where('onboardingTour.steps.3.target', '[data-tour="tracking-progress"]')
                ->where('onboardingTour.steps.4.target', '[data-tour="tracking-review"]')
                ->where('onboardingTour.steps.5.target', '[data-tour="tracking-services"]')
                ->where('onboardingTour.steps.6.target', '[data-tour="tracking-history"]')
            );
    }

    public function test_tracking_feed_returns_latest_status_payload(): void
    {
        $washOrder = WashOrder::factory()->create([
            'code' => 'ABS-FEED-1',
            'status' => WashOrder::STATUS_WASHING,
        ]);

        $washOrder->update(['status' => WashOrder::STATUS_READY]);

        $this->getJson(route('tracking.feed', 'ABS-FEED-1'))
            ->assertOk()
            ->assertJsonPath('washOrder.code', 'ABS-FEED-1')
            ->assertJsonPath('washOrder.status', WashOrder::STATUS_READY)
            ->assertJsonPath('washOrder.status_label', 'Pronto para retirada')
            ->assertJsonPath('feedUrl', route('tracking.feed', 'ABS-FEED-1'));
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

    public function test_tracking_page_shows_customer_loyalty_progress(): void
    {
        $washOrder = WashOrder::factory()->create([
            'code' => 'ABS-LOYALTY-1',
            'status' => WashOrder::STATUS_WASHING,
        ]);
        $service = Service::factory()->create([
            'wash_location_id' => $washOrder->wash_location_id,
            'name' => 'Ducha simples',
            'base_price' => 35,
            'estimated_minutes' => 30,
        ]);
        $program = LoyaltyProgram::query()->create([
            'wash_location_id' => $washOrder->wash_location_id,
            'is_active' => true,
            'threshold' => 3,
            'count_scope' => LoyaltyProgram::COUNT_ANY,
            'reward_type' => LoyaltyProgram::REWARD_FIXED_SERVICE,
            'reward_service_id' => $service->id,
            'coupon_valid_days' => 30,
        ]);

        for ($i = 0; $i < 2; $i++) {
            $delivered = WashOrder::factory()->for($washOrder->customer)->for($washOrder->vehicle)->create([
                'wash_location_id' => $washOrder->wash_location_id,
                'status' => WashOrder::STATUS_DELIVERED,
                'payment_status' => WashOrder::PAYMENT_PAID,
                'completed_at' => now()->subDays(2 - $i),
            ]);
            $delivered->services()->attach($service, [
                'service_name' => $service->name,
                'price' => $service->base_price,
                'estimated_minutes' => $service->estimated_minutes,
            ]);
        }

        LoyaltyCoupon::query()->create([
            'wash_location_id' => $washOrder->wash_location_id,
            'loyalty_program_id' => $program->id,
            'customer_id' => $washOrder->customer_id,
            'source_wash_order_id' => $washOrder->id,
            'reward_service_id' => $service->id,
            'code' => 'FID-PUBLIC-1',
            'status' => LoyaltyCoupon::STATUS_ACTIVE,
            'earned_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $this->get(route('tracking.show', 'ABS-LOYALTY-1'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Tracking')
                ->where('loyalty.enabled', true)
                ->where('loyalty.threshold', 3)
                ->where('loyalty.active_coupons', 1)
                ->where('loyalty.has_active_coupon', true)
                ->where('loyalty.coupons.0.code', 'FID-PUBLIC-1')
                ->where('loyalty.coupons.0.benefit', 'Ducha simples')
            );
    }

    public function test_tracking_page_does_not_show_overdue_active_coupon_as_available(): void
    {
        $washOrder = WashOrder::factory()->create([
            'code' => 'ABS-LOYALTY-EXPIRED',
            'status' => WashOrder::STATUS_WASHING,
        ]);
        $service = Service::factory()->create([
            'wash_location_id' => $washOrder->wash_location_id,
            'name' => 'Ducha simples',
        ]);
        $program = LoyaltyProgram::query()->create([
            'wash_location_id' => $washOrder->wash_location_id,
            'is_active' => true,
            'threshold' => 3,
            'count_scope' => LoyaltyProgram::COUNT_ANY,
            'reward_type' => LoyaltyProgram::REWARD_FIXED_SERVICE,
            'reward_service_id' => $service->id,
            'coupon_valid_days' => 30,
        ]);

        LoyaltyCoupon::query()->create([
            'wash_location_id' => $washOrder->wash_location_id,
            'loyalty_program_id' => $program->id,
            'customer_id' => $washOrder->customer_id,
            'source_wash_order_id' => $washOrder->id,
            'reward_service_id' => $service->id,
            'code' => 'FID-PUBLIC-VENCIDO',
            'status' => LoyaltyCoupon::STATUS_ACTIVE,
            'earned_at' => now()->subDays(5),
            'expires_at' => now()->subDay(),
        ]);

        $this->get(route('tracking.show', 'ABS-LOYALTY-EXPIRED'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Tracking')
                ->where('loyalty.active_coupons', 0)
                ->where('loyalty.has_active_coupon', false)
                ->where('loyalty.coupons', [])
            );
    }

    public function test_customer_can_submit_public_review_after_delivery(): void
    {
        $washOrder = WashOrder::factory()->create([
            'code' => 'ABS-REVIEW-1',
            'status' => WashOrder::STATUS_DELIVERED,
        ]);

        $this->get(route('tracking.show', 'ABS-REVIEW-1'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Tracking')
                ->where('washOrder.review.can_review', true)
                ->where('washOrder.review.submitted', false)
                ->where('reviewUrl', route('tracking.review', 'ABS-REVIEW-1'))
            );

        $this->post(route('tracking.review', 'ABS-REVIEW-1'), [
            'rating' => 5,
            'comment' => 'Atendimento excelente e carro entregue muito bem limpo.',
            'publish_consent' => '1',
        ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('wash_orders', [
            'id' => $washOrder->id,
            'customer_review_rating' => 5,
            'customer_review_comment' => 'Atendimento excelente e carro entregue muito bem limpo.',
            'customer_review_public' => true,
        ]);

        $this->get(route('tracking.show', 'ABS-REVIEW-1'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('washOrder.review.can_review', false)
                ->where('washOrder.review.submitted', true)
                ->where('washOrder.review.rating', 5)
            );
    }

    public function test_customer_review_is_blocked_before_delivery(): void
    {
        $washOrder = WashOrder::factory()->create([
            'code' => 'ABS-REVIEW-BLOCKED',
            'status' => WashOrder::STATUS_WASHING,
        ]);

        $this->post(route('tracking.review', 'ABS-REVIEW-BLOCKED'), [
            'rating' => 5,
            'comment' => 'Ainda não deveria aceitar depoimento.',
            'publish_consent' => '1',
        ])
            ->assertRedirect()
            ->assertSessionHasErrors('review');

        $this->assertDatabaseMissing('wash_orders', [
            'id' => $washOrder->id,
            'customer_review_rating' => 5,
        ]);
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
