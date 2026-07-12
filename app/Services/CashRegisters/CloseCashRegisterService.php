<?php

namespace App\Services\CashRegisters;

use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\Payment;
use App\Support\TenantContext;
use App\Models\User;
use DomainException;

class CloseCashRegisterService
{
    /**
     * @param array{counted_cash: numeric-string|float|int, closing_notes?: string|null} $data
     */
    public function handle(CashRegister $cashRegister, array $data, User $user): CashRegister
    {
        if ($cashRegister->status !== CashRegister::STATUS_OPEN) {
            throw new DomainException('Este caixa já está fechado.');
        }

        $expectedCash = $this->expectedCash($cashRegister);
        $countedCash = (float) $data['counted_cash'];

        $cashRegister->forceFill([
            'closed_by_user_id' => $user->id,
            'status' => CashRegister::STATUS_CLOSED,
            'counted_cash' => $countedCash,
            'expected_cash' => $expectedCash,
            'cash_difference' => round($countedCash - $expectedCash, 2),
            'closed_at' => now(),
            'closing_notes' => $data['closing_notes'] ?? null,
        ])->save();

        return $cashRegister->refresh();
    }

    public function expectedCash(CashRegister $cashRegister): float
    {
        $openedAt = $cashRegister->opened_at;
        $closedAt = $cashRegister->closed_at ?? now();

        $cashPayments = TenantContext::scopePayments(Payment::query())
            ->where('method', Payment::METHOD_CASH)
            ->whereBetween('paid_at', [$openedAt, $closedAt])
            ->sum('amount');

        $supplies = $cashRegister->movements()->where('type', CashMovement::TYPE_SUPPLY)->sum('amount');
        $withdrawals = $cashRegister->movements()->where('type', CashMovement::TYPE_WITHDRAWAL)->sum('amount');

        return round((float) $cashRegister->opening_balance + (float) $cashPayments + (float) $supplies - (float) $withdrawals, 2);
    }
}
