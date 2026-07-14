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
        $summary = $this->summary($cashRegister);

        return $summary['expected_cash'];
    }

    /**
     * @return array{
     *     opening_balance: float,
     *     payment_total: float,
     *     cash_payment_total: float,
     *     supplies: float,
     *     withdrawals: float,
     *     net_manual_movements: float,
     *     expected_cash: float,
     *     counted_cash: float|null,
     *     cash_difference: float|null,
     *     payments_by_method: array<string, array{label: string, total: float, count: int}>
     * }
     */
    public function summary(CashRegister $cashRegister): array
    {
        $openedAt = $cashRegister->opened_at;
        $closedAt = $cashRegister->closed_at ?? now();

        $paymentsByMethod = TenantContext::scopePayments(Payment::query())
            ->effective()
            ->whereBetween('paid_at', [$openedAt, $closedAt])
            ->selectRaw('method, COUNT(*) as total_count, SUM(amount) as total_amount')
            ->groupBy('method')
            ->get()
            ->keyBy('method');

        $paymentSummary = collect(Payment::methods())
            ->mapWithKeys(fn (string $label, string $method) => [
                $method => [
                    'label' => $label,
                    'total' => round((float) ($paymentsByMethod[$method]?->total_amount ?? 0), 2),
                    'count' => (int) ($paymentsByMethod[$method]?->total_count ?? 0),
                ],
            ])
            ->all();

        $supplies = (float) $cashRegister->movements()->where('type', CashMovement::TYPE_SUPPLY)->sum('amount');
        $withdrawals = (float) $cashRegister->movements()->where('type', CashMovement::TYPE_WITHDRAWAL)->sum('amount');
        $cashPaymentTotal = $paymentSummary[Payment::METHOD_CASH]['total'];
        $expectedCash = round((float) $cashRegister->opening_balance + $cashPaymentTotal + $supplies - $withdrawals, 2);

        return [
            'opening_balance' => (float) $cashRegister->opening_balance,
            'payment_total' => round(collect($paymentSummary)->sum('total'), 2),
            'cash_payment_total' => $cashPaymentTotal,
            'supplies' => round($supplies, 2),
            'withdrawals' => round($withdrawals, 2),
            'net_manual_movements' => round($supplies - $withdrawals, 2),
            'expected_cash' => $expectedCash,
            'counted_cash' => $cashRegister->counted_cash !== null ? (float) $cashRegister->counted_cash : null,
            'cash_difference' => $cashRegister->cash_difference !== null ? (float) $cashRegister->cash_difference : null,
            'payments_by_method' => $paymentSummary,
        ];
    }
}
