<?php

namespace Tests\Unit;

use App\Models\Service;
use App\Models\User;
use App\Models\WashLocation;
use App\Services\WashOrders\CalculateWashTotalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalculateWashTotalServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_total_and_estimated_minutes_for_active_services(): void
    {
        $first = Service::factory()->create([
            'base_price' => 80,
            'estimated_minutes' => 70,
            'active' => true,
        ]);
        $second = Service::factory()->create([
            'base_price' => 45,
            'estimated_minutes' => 35,
            'active' => true,
        ]);

        $result = app(CalculateWashTotalService::class)->handle([$first->id, $second->id]);

        $this->assertSame('125.00', $result['total']);
        $this->assertSame(105, $result['estimated_minutes']);
        $this->assertCount(2, $result['services']);
    }

    public function test_it_ignores_services_from_another_tenant_when_user_has_location(): void
    {
        $ownLocation = WashLocation::factory()->create();
        $otherLocation = WashLocation::factory()->create();
        $user = User::factory()->create(['wash_location_id' => $ownLocation->id]);
        $ownService = Service::factory()->create([
            'wash_location_id' => $ownLocation->id,
            'base_price' => 80,
            'estimated_minutes' => 70,
            'active' => true,
        ]);
        $otherService = Service::factory()->create([
            'wash_location_id' => $otherLocation->id,
            'base_price' => 999,
            'estimated_minutes' => 999,
            'active' => true,
        ]);

        $this->actingAs($user);

        $result = app(CalculateWashTotalService::class)->handle([$ownService->id, $otherService->id]);

        $this->assertSame('80.00', $result['total']);
        $this->assertSame(70, $result['estimated_minutes']);
        $this->assertCount(1, $result['services']);
    }
}
