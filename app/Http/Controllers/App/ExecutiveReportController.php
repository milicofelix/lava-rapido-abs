<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\WashOrder;
use App\Support\Pdf\SimplePdfDocument;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExecutiveReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        return $this->index($request);
    }

    public function index(Request $request): View
    {
        return view('app.reports.executive', $this->reportData($request));
    }

    public function exportPdf(Request $request): Response
    {
        $data = $this->reportData($request);
        $pdf = $this->buildPdf($data);
        $filename = 'relatorio-executivo-'.$data['start'].'-'.$data['end'].'.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(Request $request): array
    {
        [$start, $end] = $this->period($request);
        [$previousStart, $previousEnd] = $this->previousPeriod($start, $end);

        $summary = $this->summary($start, $end);
        $previousSummary = $this->summary($previousStart, $previousEnd);

        return [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'previousStart' => $previousStart->toDateString(),
            'previousEnd' => $previousEnd->toDateString(),
            'summary' => $summary,
            'previousSummary' => $previousSummary,
            'variations' => [
                'revenue' => $this->variation($summary['revenue'], $previousSummary['revenue']),
                'orders' => $this->variation($summary['orders_count'], $previousSummary['orders_count']),
                'ticket_average' => $this->variation($summary['ticket_average'], $previousSummary['ticket_average']),
                'recurring_customers' => $this->variation($summary['recurring_customers_count'], $previousSummary['recurring_customers_count']),
            ],
            'topServices' => $this->topServices($start, $end),
            'topCustomers' => $this->topCustomers($start, $end),
            'paymentMethods' => $this->paymentMethods($start, $end),
            'statusDistribution' => $this->statusDistribution($start, $end),
            'dailyVolume' => $this->dailyVolume($start, $end),
            'statuses' => WashOrder::statuses(),
            'methods' => Payment::methods(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function buildPdf(array $data): string
    {
        $pdf = new SimplePdfDocument;
        $pdf->addPage();

        $y = 805;
        $this->pdfLine($pdf, 48, $y, 'AutoFlow - Relatório executivo', 18);
        $y -= 24;
        $this->pdfLine(
            $pdf,
            48,
            $y,
            'Período: '.$this->dateLabel($data['start']).' até '.$this->dateLabel($data['end'])
            .' | Comparativo: '.$this->dateLabel($data['previousStart']).' até '.$this->dateLabel($data['previousEnd']),
            10
        );
        $y -= 34;

        $summary = $data['summary'];
        $cards = [
            'Receita do período' => $this->money($summary['revenue']),
            'Lavagens no período' => $this->number($summary['orders_count']),
            'Ticket médio' => $this->money($summary['ticket_average']),
            'Clientes recorrentes' => $this->number($summary['recurring_customers_count']),
            'Entregues' => $this->number($summary['delivered_count']),
            'Canceladas' => $this->number($summary['canceled_count']),
            'Clientes ativos' => $this->number($summary['active_customers_count']),
            'Novos clientes' => $this->number($summary['new_customers_count']),
        ];

        foreach ($cards as $label => $value) {
            $this->pdfLine($pdf, 48, $y, $label.': '.$value, 11);
            $y -= 18;
        }

        $y -= 14;
        $this->pdfSection($pdf, $y, 'Top serviços');
        $y -= 20;

        foreach (array_slice($data['topServices'], 0, 8) as $service) {
            $this->pdfLine($pdf, 58, $y, $service['service_name'].' - '.$service['total'].' lavagens - '.$this->money($service['revenue']), 10);
            $y -= 16;
        }

        if ($data['topServices'] === []) {
            $this->pdfLine($pdf, 58, $y, 'Nenhum serviço no período.', 10);
            $y -= 16;
        }

        $y -= 10;
        $this->pdfSection($pdf, $y, 'Top clientes');
        $y -= 20;

        foreach (array_slice($data['topCustomers'], 0, 8) as $customer) {
            $this->pdfLine($pdf, 58, $y, $customer['name'].' - '.$customer['orders_count'].' lavagens - '.$this->money($customer['revenue']), 10);
            $y -= 16;
        }

        if ($data['topCustomers'] === []) {
            $this->pdfLine($pdf, 58, $y, 'Nenhum cliente com lavagem no período.', 10);
            $y -= 16;
        }

        $y -= 10;
        $this->pdfSection($pdf, $y, 'Pagamentos por método');
        $y -= 20;

        foreach ($data['paymentMethods'] as $method) {
            $this->pdfLine($pdf, 58, $y, $method['label'].' - '.$this->money($method['total']).' - '.$method['count'].' pagamento(s)', 10);
            $y -= 16;
        }

        if ($data['paymentMethods'] === []) {
            $this->pdfLine($pdf, 58, $y, 'Nenhum pagamento no período.', 10);
        }

        return $pdf->render();
    }

    private function pdfSection(SimplePdfDocument $pdf, int $y, string $title): void
    {
        $this->pdfLine($pdf, 48, $y, $title, 13);
    }

    private function pdfLine(SimplePdfDocument $pdf, int $x, int $y, string $text, int $size): void
    {
        $pdf->text($x, $y, mb_strimwidth($text, 0, 110, '...'), $size);
    }

    private function money(float|int $value): string
    {
        return 'R$ '.number_format((float) $value, 2, ',', '.');
    }

    private function number(float|int $value): string
    {
        return number_format((float) $value, 0, ',', '.');
    }

    private function dateLabel(string $date): string
    {
        return Carbon::parse($date)->format('d/m/Y');
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function period(Request $request): array
    {
        $today = today()->toDateString();

        $validated = $request->validate([
            'start' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:'.$today],
            'end' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:'.$today, 'after_or_equal:start'],
        ]);

        $start = Carbon::parse($validated['start'] ?? today()->startOfMonth()->toDateString())->startOfDay();
        $end = Carbon::parse($validated['end'] ?? $today)->endOfDay();

        if ($start->isAfter($end)) {
            throw ValidationException::withMessages([
                'end' => 'A data final deve ser igual ou posterior a data inicial.',
            ]);
        }

        return [$start, $end];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function previousPeriod(Carbon $start, Carbon $end): array
    {
        $days = $start->diffInDays($end) + 1;
        $previousEnd = $start->copy()->subDay()->endOfDay();
        $previousStart = $previousEnd->copy()->subDays($days - 1)->startOfDay();

        return [$previousStart, $previousEnd];
    }

    /**
     * @return array{
     *     orders_count:int,
     *     delivered_count:int,
     *     canceled_count:int,
     *     revenue:float,
     *     payments_count:int,
     *     ticket_average:float,
     *     active_customers_count:int,
     *     recurring_customers_count:int,
     *     new_customers_count:int
     * }
     */
    private function summary(Carbon $start, Carbon $end): array
    {
        $orders = $this->ordersForPeriod($start, $end);
        $payments = $this->paymentsForPeriod($start, $end);

        $ordersCount = (clone $orders)->count();
        $deliveredCount = (clone $orders)->where('status', WashOrder::STATUS_DELIVERED)->count();
        $canceledCount = (clone $orders)->where('status', WashOrder::STATUS_CANCELED)->count();
        $paymentsCount = (clone $payments)->count();
        $revenue = (float) (clone $payments)->sum('amount');

        $recurringCustomersCount = (int) (clone $orders)
            ->select('customer_id')
            ->groupBy('customer_id')
            ->havingRaw('COUNT(*) >= 2')
            ->get()
            ->count();

        return [
            'orders_count' => $ordersCount,
            'delivered_count' => $deliveredCount,
            'canceled_count' => $canceledCount,
            'revenue' => $revenue,
            'payments_count' => $paymentsCount,
            'ticket_average' => $paymentsCount > 0 ? $revenue / $paymentsCount : 0.0,
            'active_customers_count' => (int) (clone $orders)->distinct('customer_id')->count('customer_id'),
            'recurring_customers_count' => $recurringCustomersCount,
            'new_customers_count' => TenantContext::scopeCustomers(Customer::query())->whereBetween('created_at', [$start, $end])->count(),
        ];
    }

    private function variation(float|int $current, float|int $previous): ?float
    {
        if ((float) $previous === 0.0) {
            return (float) $current === 0.0 ? 0.0 : null;
        }

        return (((float) $current - (float) $previous) / (float) $previous) * 100;
    }

    /**
     * @return array<int, array{service_name:string, total:int, revenue:float, share:float}>
     */
    private function topServices(Carbon $start, Carbon $end): array
    {
        $rows = DB::table('service_wash_order')
            ->join('wash_orders', 'wash_orders.id', '=', 'service_wash_order.wash_order_id')
            ->when(TenantContext::currentLocationId(), fn ($query, $locationId) => $query->where('wash_orders.wash_location_id', $locationId))
            ->whereBetween('wash_orders.entered_at', [$start, $end])
            ->select('service_wash_order.service_name')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(service_wash_order.price) as revenue')
            ->groupBy('service_wash_order.service_name')
            ->orderByDesc('total')
            ->orderByDesc('revenue')
            ->limit(8)
            ->get();

        $max = max(1, (int) $rows->max('total'));

        return $rows
            ->map(fn ($row) => [
                'service_name' => $row->service_name,
                'total' => (int) $row->total,
                'revenue' => (float) $row->revenue,
                'share' => ((int) $row->total / $max) * 100,
            ])
            ->all();
    }

    /**
     * @return array<int, array{name:string, phone:?string, orders_count:int, revenue:float, share:float}>
     */
    private function topCustomers(Carbon $start, Carbon $end): array
    {
        $rows = $this->ordersForPeriod($start, $end)
            ->join('customers', 'customers.id', '=', 'wash_orders.customer_id')
            ->leftJoin('payments', function ($join): void {
                $join->on('payments.wash_order_id', '=', 'wash_orders.id')
                    ->whereNull('payments.reversed_at');
            })
            ->select('customers.name', 'customers.phone')
            ->selectRaw('COUNT(DISTINCT wash_orders.id) as orders_count')
            ->selectRaw('COALESCE(SUM(payments.amount), 0) as revenue')
            ->groupBy('customers.id', 'customers.name', 'customers.phone')
            ->orderByDesc('orders_count')
            ->orderByDesc('revenue')
            ->limit(8)
            ->get();

        $max = max(1, (int) $rows->max('orders_count'));

        return $rows
            ->map(fn ($row) => [
                'name' => $row->name,
                'phone' => $row->phone,
                'orders_count' => (int) $row->orders_count,
                'revenue' => (float) $row->revenue,
                'share' => ((int) $row->orders_count / $max) * 100,
            ])
            ->all();
    }

    /**
     * @return array<int, array{method:string, label:string, total:float, count:int, share:float}>
     */
    private function paymentMethods(Carbon $start, Carbon $end): array
    {
        $rows = $this->paymentsForPeriod($start, $end)
            ->select('method')
            ->selectRaw('SUM(amount) as total')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('method')
            ->orderByDesc('total')
            ->get();

        $max = max(1, (float) $rows->max('total'));

        return $rows
            ->map(fn ($row) => [
                'method' => $row->method,
                'label' => Payment::methods()[$row->method] ?? $row->method,
                'total' => (float) $row->total,
                'count' => (int) $row->count,
                'share' => ((float) $row->total / $max) * 100,
            ])
            ->all();
    }

    /**
     * @return array<int, array{status:string, label:string, total:int, share:float}>
     */
    private function statusDistribution(Carbon $start, Carbon $end): array
    {
        $rows = $this->ordersForPeriod($start, $end)
            ->select('status')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        $max = max(1, (int) $rows->max('total'));

        return $rows
            ->map(fn ($row) => [
                'status' => $row->status,
                'label' => WashOrder::statuses()[$row->status] ?? $row->status,
                'total' => (int) $row->total,
                'share' => ((int) $row->total / $max) * 100,
            ])
            ->all();
    }

    /**
     * @return array<int, array{day:string, label:string, total:int, share:float}>
     */
    private function dailyVolume(Carbon $start, Carbon $end): array
    {
        $rows = $this->ordersForPeriod($start, $end)
            ->selectRaw('DATE(entered_at) as day')
            ->selectRaw('COUNT(*) as total')
            ->groupByRaw('DATE(entered_at)')
            ->orderBy('day')
            ->get();

        $max = max(1, (int) $rows->max('total'));

        return $rows
            ->map(fn ($row) => [
                'day' => $row->day,
                'label' => Carbon::parse($row->day)->format('d/m'),
                'total' => (int) $row->total,
                'share' => ((int) $row->total / $max) * 100,
            ])
            ->all();
    }

    private function ordersForPeriod(Carbon $start, Carbon $end): Builder
    {
        $query = WashOrder::query();

        if ($locationId = TenantContext::currentLocationId()) {
            $query->where('wash_orders.wash_location_id', $locationId);
        }

        return $query->whereBetween('wash_orders.entered_at', [$start, $end]);
    }

    private function paymentsForPeriod(Carbon $start, Carbon $end): Builder
    {
        return TenantContext::scopePayments(Payment::query())
            ->effective()
            ->whereBetween('paid_at', [$start, $end]);
    }
}
