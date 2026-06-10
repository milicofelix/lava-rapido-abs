<?php

namespace App\Services\CreditPayments;

use App\Models\Payment;
use App\Models\User;
use App\Models\WashOrder;
use App\Services\Payments\RegisterPaymentService;
use DomainException;

class ReceiveCreditPaymentService
{
    public function __construct(private readonly RegisterPaymentService $registerPayment)
    {
    }

    /**
     * @param array{method: string, amount: numeric-string|float|int, notes?: string|null} $data
     */
    public function handle(WashOrder $washOrder, array $data, User $user): Payment
    {
        if ($washOrder->payment_status !== WashOrder::PAYMENT_CREDIT_PENDING) {
            throw new DomainException('Esta lavagem nao esta marcada como fiado / pendente.');
        }

        return $this->registerPayment->handle($washOrder, [
            'method' => $data['method'],
            'amount' => $data['amount'],
            'notes' => $data['notes'] ?? 'Recebimento de fiado.',
        ], $user);
    }
}
