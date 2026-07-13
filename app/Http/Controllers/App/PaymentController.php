<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Payment;
use App\Models\WashOrder;
use App\Services\Payments\RegisterPaymentService;
use App\Services\Payments\ReversePaymentService;
use App\Support\TenantContext;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function store(Request $request, WashOrder $washOrder, RegisterPaymentService $registerPayment): RedirectResponse
    {
        TenantContext::abortUnlessModelBelongsToTenant($washOrder);

        $allowedMethods = array_keys(Payment::methods());

        if (! AppSetting::isModuleEnabled('module_credit_receivables')) {
            $allowedMethods = array_values(array_diff($allowedMethods, [Payment::METHOD_CREDIT_PENDING]));
        }

        $data = $request->validate([
            'method' => ['required', Rule::in($allowedMethods)],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! in_array($data['method'], [Payment::METHOD_COURTESY, Payment::METHOD_CREDIT_PENDING], true)) {
            $request->validate([
                'amount' => ['required', 'numeric', 'min:0.01', 'max:'.$washOrder->payableAmount()],
            ]);
            $data['amount'] = $request->input('amount');
        }

        $registerPayment->handle($washOrder, $data, $request->user());

        return back()->with('status', 'Pagamento registrado com sucesso.');
    }

    public function reverse(
        Request $request,
        WashOrder $washOrder,
        Payment $payment,
        ReversePaymentService $reversePayment,
    ): RedirectResponse {
        TenantContext::abortUnlessModelBelongsToTenant($washOrder);

        $data = $request->validate([
            'reversal_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'reversal_reason.required' => 'Informe o motivo do estorno.',
            'reversal_reason.min' => 'Informe um motivo de estorno com pelo menos 5 caracteres.',
        ]);

        try {
            $reversePayment->handle($washOrder, $payment, $data, $request->user());
        } catch (DomainException $exception) {
            return back()->withErrors(['payment_reversal' => $exception->getMessage()])->withInput();
        }

        return back()->with('status', 'Pagamento estornado com sucesso.');
    }
}
