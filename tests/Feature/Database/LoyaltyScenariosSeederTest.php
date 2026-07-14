<?php

namespace Tests\Feature\Database;

use App\Models\LoyaltyCoupon;
use App\Models\LoyaltyProgram;
use App\Models\Payment;
use App\Models\User;
use App\Models\WashLocation;
use App\Models\WashOrder;
use Database\Seeders\LoyaltyScenariosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoyaltyScenariosSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_loyalty_scenarios_seeder_creates_complete_scenarios_for_existing_locations(): void
    {
        $locations = WashLocation::factory()->count(2)->create();

        $locations->each(fn (WashLocation $location) => User::factory()->create([
            'wash_location_id' => $location->id,
            'role' => User::ROLE_OWNER,
        ]));

        $this->seed(LoyaltyScenariosSeeder::class);

        $locations->each(function (WashLocation $location): void {
            $this->assertDatabaseHas('loyalty_programs', [
                'wash_location_id' => $location->id,
                'is_active' => true,
                'threshold' => 3,
            ]);

            $this->assertSame(5, LoyaltyCoupon::query()
                ->where('wash_location_id', $location->id)
                ->where('code', 'like', 'FIDELIDADE-DEMO-'.$location->id.'-%')
                ->count());

            $this->assertDatabaseHas('loyalty_coupons', [
                'wash_location_id' => $location->id,
                'code' => 'FIDELIDADE-DEMO-'.$location->id.'-ATIVO',
                'status' => LoyaltyCoupon::STATUS_ACTIVE,
            ]);
            $this->assertDatabaseHas('loyalty_coupons', [
                'wash_location_id' => $location->id,
                'code' => 'FIDELIDADE-DEMO-'.$location->id.'-USADO',
                'status' => LoyaltyCoupon::STATUS_USED,
            ]);
            $this->assertDatabaseHas('loyalty_coupons', [
                'wash_location_id' => $location->id,
                'code' => 'FIDELIDADE-DEMO-'.$location->id.'-VENCIDO',
                'status' => LoyaltyCoupon::STATUS_EXPIRED,
            ]);
            $this->assertDatabaseHas('loyalty_coupons', [
                'wash_location_id' => $location->id,
                'code' => 'FIDELIDADE-DEMO-'.$location->id.'-CANCELADO',
                'status' => LoyaltyCoupon::STATUS_CANCELED,
            ]);

            $usedCoupon = LoyaltyCoupon::query()
                ->where('wash_location_id', $location->id)
                ->where('code', 'FIDELIDADE-DEMO-'.$location->id.'-USADO')
                ->firstOrFail();

            $this->assertNotNull($usedCoupon->used_wash_order_id);
            $this->assertDatabaseHas('wash_orders', [
                'id' => $usedCoupon->used_wash_order_id,
                'wash_location_id' => $location->id,
                'payment_status' => WashOrder::PAYMENT_COURTESY,
                'loyalty_coupon_id' => $usedCoupon->id,
            ]);
            $this->assertDatabaseHas('payments', [
                'wash_order_id' => $usedCoupon->used_wash_order_id,
                'method' => Payment::METHOD_COURTESY,
                'amount' => 0,
            ]);
        });

        $couponCount = LoyaltyCoupon::query()->count();
        $washOrderCount = WashOrder::query()
            ->where('code', 'like', 'FIDELIDADE-DEMO-%')
            ->count();

        $this->seed(LoyaltyScenariosSeeder::class);

        $this->assertSame($couponCount, LoyaltyCoupon::query()->count());
        $this->assertSame($washOrderCount, WashOrder::query()
            ->where('code', 'like', 'FIDELIDADE-DEMO-%')
            ->count());
        $this->assertSame(2, LoyaltyProgram::query()->count());
    }
}
