<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\LoyaltyCoupon;
use App\Support\AuditLogger;
use Illuminate\Console\Command;

class ExpireLoyaltyCouponsCommand extends Command
{
    protected $signature = 'loyalty:expire-coupons';

    protected $description = 'Marca cupons de fidelidade vencidos como expirados.';

    public function handle(): int
    {
        $expired = 0;

        LoyaltyCoupon::query()
            ->where('status', LoyaltyCoupon::STATUS_ACTIVE)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->with('customer')
            ->chunkById(100, function ($coupons) use (&$expired) {
                foreach ($coupons as $coupon) {
                    $coupon->forceFill(['status' => LoyaltyCoupon::STATUS_EXPIRED])->save();
                    $expired++;

                    AuditLogger::record(
                        AuditLog::ACTION_LOYALTY_COUPON_EXPIRED,
                        'Sistema expirou o cupom '.$coupon->code.' de '.$coupon->customer?->name.'.',
                        $coupon,
                        ['coupon_code' => $coupon->code],
                    );
                }
            });

        $this->info($expired.' cupom(ns) expirado(s).');

        return self::SUCCESS;
    }
}
