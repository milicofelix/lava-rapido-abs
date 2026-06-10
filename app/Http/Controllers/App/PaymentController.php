<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Payment;
use App\Models\WashOrder;
use App\Services\Payments\RegisterPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function store(Request $request, WashOrder $washOrder, RegisterPaymentService $registerPayment): RedirectResponse
    {
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
                'amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            ]);
            $data['amount'] = $request->input('amount');
        }

        $registerPayment->handle($washOrder, $data, $request->user());

        return back()->with('status', 'Pagamento registrado com sucesso.');
    }
}
