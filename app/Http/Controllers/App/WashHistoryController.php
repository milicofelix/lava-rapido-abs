<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Models\WashOrder;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WashHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        $washOrdersQuery = $this->washOrdersQuery($filters);

        $summary = [
            'count' => (clone $washOrdersQuery)->count(),
            'total' => (clone $washOrdersQuery)->sum('total_amount'),
            'delivered' => (clone $washOrdersQuery)->where('status', WashOrder::STATUS_DELIVERED)->count(),
            'paid' => (clone $washOrdersQuery)->where('payment_status', WashOrder::PAYMENT_PAID)->count(),
        ];

        $washOrders = $washOrdersQuery
            ->with(['customer', 'vehicle', 'services', 'assignedUser', 'teamMembers', 'payments'])
            ->latest('entered_at')
            ->paginate(20)
            ->withQueryString();

        return view('app.history.index', [
            'filters' => $filters,
            'washOrders' => $washOrders,
            'summary' => $summary,
            'customers' => TenantContext::scopeCustomers(Customer::query())->orderBy('name')->get(['id', 'name']),
            'services' => TenantContext::scopeServices(Service::query())->orderBy('name')->get(['id', 'name']),
            'employees' => TenantContext::scopeUsers(User::query())->orderBy('name')->get(['id', 'name']),
            'statuses' => WashOrder::statuses(),
            'paymentMethods' => Payment::methods(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $washOrders = $this->washOrdersQuery($filters)
            ->with(['customer', 'vehicle', 'services', 'assignedUser', 'teamMembers', 'payments'])
            ->oldest('entered_at')
            ->get();

        return response()->streamDownload(function () use ($washOrders) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Entrada',
                'Codigo',
                'Cliente',
                'Placa',
                'Veiculo',
                'Servicos',
                'Status',
                'Equipe',
                'Pagamento',
                'Total',
            ]);

            foreach ($washOrders as $washOrder) {
                fputcsv($handle, [
                    $washOrder->entered_at?->format('d/m/Y H:i'),
                    $washOrder->code,
                    $washOrder->customer->name,
                    $washOrder->vehicle->plate,
                    trim($washOrder->vehicle->brand.' '.$washOrder->vehicle->model),
                    $washOrder->services->pluck('pivot.service_name')->filter()->join(', '),
                    $washOrder->statusLabel(),
                    $this->teamLabel($washOrder),
                    $this->paymentMethodsLabel($washOrder),
                    number_format((float) $washOrder->total_amount, 2, ',', '.'),
                ]);
            }

            fclose($handle);
        }, 'historico-operacional-'.$filters['start'].'-'.$filters['end'].'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array{start: string, end: string, customer_id: ?string, plate: ?string, service_id: ?string, status: ?string, employee_id: ?string, payment_method: ?string}
     */
    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'plate' => ['nullable', 'string', 'max:20'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'status' => ['nullable', Rule::in(array_keys(WashOrder::statuses()))],
            'employee_id' => ['nullable', 'integer', 'exists:users,id'],
            'payment_method' => ['nullable', Rule::in(array_keys(Payment::methods()))],
        ]);

        return [
            'start' => Carbon::parse($validated['start'] ?? today()->subDays(30)->toDateString())->toDateString(),
            'end' => Carbon::parse($validated['end'] ?? today()->toDateString())->toDateString(),
            'customer_id' => $validated['customer_id'] ?? null,
            'plate' => $validated['plate'] ?? null,
            'service_id' => $validated['service_id'] ?? null,
            'status' => $validated['status'] ?? null,
            'employee_id' => $validated['employee_id'] ?? null,
            'payment_method' => $validated['payment_method'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function washOrdersQuery(array $filters): Builder
    {
        $start = Carbon::parse($filters['start'])->startOfDay();
        $end = Carbon::parse($filters['end'])->endOfDay();

        return TenantContext::scopeWashOrders(WashOrder::query())
            ->whereBetween('entered_at', [$start, $end])
            ->when($filters['customer_id'], fn (Builder $query, string $customerId) => $query->where('customer_id', $customerId))
            ->when($filters['plate'], function (Builder $query, string $plate) {
                $query->whereHas('vehicle', fn (Builder $vehicleQuery) => $vehicleQuery->where('plate', 'like', '%'.strtoupper($plate).'%'));
            })
            ->when($filters['service_id'], function (Builder $query, string $serviceId) {
                $query->whereHas('services', fn (Builder $serviceQuery) => $serviceQuery->where('services.id', $serviceId));
            })
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['employee_id'], function (Builder $query, string $employeeId) {
                $query->where(function (Builder $employeeQuery) use ($employeeId) {
                    $employeeQuery
                        ->where('assigned_user_id', $employeeId)
                        ->orWhereHas('teamMembers', fn (Builder $teamQuery) => $teamQuery->where('users.id', $employeeId));
                });
            })
            ->when($filters['payment_method'], function (Builder $query, string $method) {
                $query->whereHas('payments', fn (Builder $paymentQuery) => $paymentQuery->where('method', $method));
            });
    }

    private function teamLabel(WashOrder $washOrder): string
    {
        if ($washOrder->teamMembers->isNotEmpty()) {
            return $washOrder->teamMembers->pluck('name')->join(', ');
        }

        return $washOrder->assignedUser?->name ?? '-';
    }

    private function paymentMethodsLabel(WashOrder $washOrder): string
    {
        if ($washOrder->payments->isEmpty()) {
            return $washOrder->paymentStatusLabel();
        }

        return $washOrder->payments
            ->map(fn (Payment $payment) => $payment->methodLabel())
            ->unique()
            ->join(', ');
    }
}
