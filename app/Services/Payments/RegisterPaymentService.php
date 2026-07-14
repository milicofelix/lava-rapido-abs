<?php

namespace App\Services\Payments;

use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\User;
use App\Models\WashOrder;
use App\Services\Loyalty\EvaluateLoyaltyProgramService;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;

class RegisterPaymentService
{
    public function __construct(
        private readonly EvaluateLoyaltyProgramService $loyalty,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(WashOrder $washOrder, array $data, ?User $user = null): Payment
    {
        return DB::transaction(function () use ($washOrder, $data, $user) {
            $method = $data['method'];
            $amount = in_array($method, [Payment::METHOD_COURTESY, Payment::METHOD_CREDIT_PENDING], true)
                ? 0
                : $data['amount'];

            $payment = $washOrder->payments()->create([
                'user_id' => $user?->id,
                'method' => $method,
                'amount' => $amount,
                'paid_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            $washOrder->forceFill([
                'payment_status' => match ($method) {
                    Payment::METHOD_COURTESY => WashOrder::PAYMENT_COURTESY,
                    Payment::METHOD_CREDIT_PENDING => WashOrder::PAYMENT_CREDIT_PENDING,
                    default => WashOrder::PAYMENT_PAID,
                },
            ])->save();

            AuditLogger::record(
                AuditLog::ACTION_PAYMENT_REGISTERED,
                ($user?->name ?? 'Sistema').' registrou pagamento em '.$payment->methodLabel().' na lavagem '.$washOrder->code.'.',
                $washOrder,
                [
                    'payment_id' => $payment->id,
                    'method' => $method,
                    'amount' => (float) $amount,
                ],
                $user,
            );

            $this->loyalty->handle($washOrder->refresh());

            return $payment;
        });
    }
}
