<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\WashOrder;
use Illuminate\View\View;

class WashOrderReceiptController extends Controller
{
    public function __invoke(WashOrder $washOrder): View
    {
        return view('app.wash-orders.receipt', [
            'washOrder' => $washOrder->load(['customer', 'vehicle', 'services', 'payments.user']),
        ]);
    }
}
