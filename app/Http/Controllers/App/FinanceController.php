<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\WashOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanceController extends Controller
{
    public function index(Request $request): View
    {
        [$start, $end] = $this->period($request);

        $payments = $this->paymentsForPeriod($start, $end);
        $paymentsList = (clone $payments)
            ->with(['washOrder.customer', 'washOrder.vehicle', 'user'])
            ->latest('paid_at')
            ->paginate(15)
            ->withQueryString();

        $total = (clone $payments)->sum('amount');
        $count = (clone $payments)->count();

        return view('app.finance.index', [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'total' => $total,
            'count' => $count,
            'ticketAverage' => $count > 0 ? $total / $count : 0,
            'pendingCount' => WashOrder::where('payment_status', WashOrder::PAYMENT_PENDING)->count(),
            'creditPendingCount' => WashOrder::where('payment_status', WashOrder::PAYMENT_CREDIT_PENDING)->count(),
            'totalsByMethod' => (clone $payments)
                ->selectRaw('method, SUM(amount) as total, COUNT(*) as count')
                ->groupBy('method')
                ->orderBy('method')
                ->get(),
            'payments' => $paymentsList,
            'methods' => Payment::methods(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$start, $end] = $this->period($request);

        $payments = $this->paymentsForPeriod($start, $end)
            ->with(['washOrder.customer', 'washOrder.vehicle', 'user'])
            ->oldest('paid_at')
            ->get();

        return response()->streamDownload(function () use ($payments) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Data', 'Codigo', 'Cliente', 'Placa', 'Metodo', 'Valor', 'Registrado por']);

            foreach ($payments as $payment) {
                fputcsv($handle, [
                    $payment->paid_at?->format('d/m/Y H:i'),
                    $payment->washOrder->code,
                    $payment->washOrder->customer->name,
                    $payment->washOrder->vehicle->plate,
                    $payment->methodLabel(),
                    number_format((float) $payment->amount, 2, ',', '.'),
                    $payment->user?->name ?? 'Sistema',
                ]);
            }

            fclose($handle);
        }, 'financeiro-'.$start->toDateString().'-'.$end->toDateString().'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function period(Request $request): array
    {
        $request->validate([
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
        ]);

        $start = Carbon::parse($request->query('start', today()->toDateString()))->startOfDay();
        $end = Carbon::parse($request->query('end', today()->toDateString()))->endOfDay();

        return [$start, $end];
    }

    private function paymentsForPeriod(Carbon $start, Carbon $end): Builder
    {
        return Payment::query()->whereBetween('paid_at', [$start, $end]);
    }
}
