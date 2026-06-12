<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Payment;
use App\Models\WashOrder;
use App\Services\CreditPayments\ReceiveCreditPaymentService;
use App\Support\TenantContext;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CreditReceivableController extends Controller
{
    public function index(): View|\Illuminate\Http\RedirectResponse
    {
        if (! AppSetting::isModuleEnabled('module_credit_receivables')) {
            return redirect()->route('settings.edit')->with('status', 'Modulo Fiado esta desabilitado. Habilite em Configuracoes para usar.');
        }

        $creditQuery = TenantContext::scopeWashOrders(WashOrder::query())
            ->where('payment_status', WashOrder::PAYMENT_CREDIT_PENDING);

        $orders = (clone $creditQuery)
            ->with(['customer', 'vehicle', 'payments.user'])
            ->latest('entered_at')
            ->paginate(15);

        return view('app.finance.credit-receivables.index', [
            'orders' => $orders,
            'totalPending' => (clone $creditQuery)->sum('total_amount'),
            'methods' => collect(Payment::methods())
                ->except([Payment::METHOD_COURTESY, Payment::METHOD_CREDIT_PENDING])
                ->all(),
        ]);
    }

    public function receive(Request $request, WashOrder $washOrder, ReceiveCreditPaymentService $receiveCreditPayment): RedirectResponse
    {
        TenantContext::abortUnlessModelBelongsToTenant($washOrder);

        $allowedMethods = array_diff(array_keys(Payment::methods()), [
            Payment::METHOD_COURTESY,
            Payment::METHOD_CREDIT_PENDING,
        ]);

        $data = $request->validate([
            'method' => ['required', Rule::in($allowedMethods)],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $receiveCreditPayment->handle($washOrder, $data, $request->user());
        } catch (DomainException $exception) {
            return back()->withErrors(['credit_payment' => $exception->getMessage()])->withInput();
        }

        return back()->with('status', 'Fiado recebido com sucesso.');
    }
}
