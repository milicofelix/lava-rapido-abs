<?php

namespace App\Services\CashRegisters;

use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\User;
use DomainException;

class RegisterCashMovementService
{
    /**
     * @param array{type: string, amount: numeric-string|float|int, description: string} $data
     */
    public function handle(CashRegister $cashRegister, array $data, User $user): CashMovement
    {
        if ($cashRegister->status !== CashRegister::STATUS_OPEN) {
            throw new DomainException('Não é possível movimentar um caixa fechado.');
        }

        return $cashRegister->movements()->create([
            'user_id' => $user->id,
            'type' => $data['type'],
            'amount' => $data['amount'],
            'description' => $data['description'],
            'occurred_at' => now(),
        ]);
    }
}
