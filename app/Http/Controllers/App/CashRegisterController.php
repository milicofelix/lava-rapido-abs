<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Services\CashRegisters\CloseCashRegisterService;
use App\Services\CashRegisters\OpenCashRegisterService;
use App\Services\CashRegisters\RegisterCashMovementService;
use App\Support\TenantContext;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class CashRegisterController extends Controller
{
    public function index(CloseCashRegisterService $closeCashRegister): View|\Illuminate\Http\RedirectResponse
    {
        if (! AppSetting::isModuleEnabled('module_cash_register')) {
            return redirect()->route('settings.edit')->with('status', 'Módulo Caixa está desabilitado. Habilite em Configurações para usar.');
        }

        $openRegister = CashRegister::openRegister(TenantContext::currentLocationId())?->load(['openedBy', 'movements.user']);

        $openRegisterSummary = $openRegister ? $closeCashRegister->summary($openRegister) : null;
        $recentRegisters = TenantContext::scopeCashRegisters(CashRegister::query())
            ->with(['openedBy', 'closedBy'])
            ->latest('opened_at')
            ->paginate(10);

        $recentRegisters->getCollection()->each(function (CashRegister $register) use ($closeCashRegister): void {
            $register->setAttribute('cash_summary', $closeCashRegister->summary($register));
        });

        return view('app.finance.cash-registers.index', [
            'openRegister' => $openRegister,
            'cashRegisterSummary' => $openRegisterSummary,
            'expectedCash' => $openRegisterSummary['expected_cash'] ?? 0,
            'cashPaymentTotal' => $openRegisterSummary['cash_payment_total'] ?? 0,
            'recentRegisters' => $recentRegisters,
            'movementTypes' => CashMovement::types(),
        ]);
    }

    public function store(Request $request, OpenCashRegisterService $openCashRegister): RedirectResponse
    {
        $data = $request->validate([
            'opening_balance' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'opening_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $openCashRegister->handle($data, $request->user());
        } catch (DomainException $exception) {
            return back()->withErrors(['cash_register' => $exception->getMessage()])->withInput();
        }

        return back()->with('status', 'Caixa aberto com sucesso.');
    }

    public function movement(Request $request, CashRegister $cashRegister, RegisterCashMovementService $registerMovement): RedirectResponse
    {
        TenantContext::abortUnlessModelBelongsToTenant($cashRegister);

        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(CashMovement::types()))],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'description' => ['required', 'string', 'max:255'],
        ]);

        try {
            $registerMovement->handle($cashRegister, $data, $request->user());
        } catch (DomainException $exception) {
            return back()->withErrors(['cash_movement' => $exception->getMessage()])->withInput();
        }

        return back()->with('status', 'Movimentacao registrada com sucesso.');
    }

    public function close(Request $request, CashRegister $cashRegister, CloseCashRegisterService $closeCashRegister): RedirectResponse
    {
        TenantContext::abortUnlessModelBelongsToTenant($cashRegister);

        $data = $request->validate([
            'counted_cash' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'closing_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $closeCashRegister->handle($cashRegister, $data, $request->user());
        } catch (DomainException $exception) {
            return back()->withErrors(['cash_register' => $exception->getMessage()])->withInput();
        }

        return back()->with('status', 'Caixa fechado com sucesso.');
    }
}
