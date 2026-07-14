<?php

namespace App\Services\CashRegisters;

use App\Models\CashRegister;
use App\Models\User;
use App\Support\TenantContext;
use DomainException;

class OpenCashRegisterService
{
    /**
     * @param array{opening_balance?: numeric-string|float|int|null, opening_notes?: string|null} $data
     */
    public function handle(array $data, User $user): CashRegister
    {
        if (CashRegister::openRegister(TenantContext::currentLocationId())) {
            throw new DomainException('Ja existe um caixa aberto. Feche o caixa atual antes de abrir outro.');
        }

        return CashRegister::query()->create([
            'wash_location_id' => TenantContext::currentLocationId(),
            'opened_by_user_id' => $user->id,
            'status' => CashRegister::STATUS_OPEN,
            'opening_balance' => $data['opening_balance'] ?? 0,
            'opened_at' => now(),
            'opening_notes' => $data['opening_notes'] ?? null,
        ]);
    }
}
