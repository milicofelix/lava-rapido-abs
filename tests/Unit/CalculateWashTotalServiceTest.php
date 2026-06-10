<?php

namespace Tests\Unit;

use App\Models\Service;
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
}
