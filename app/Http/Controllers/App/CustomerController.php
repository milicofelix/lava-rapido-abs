<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\AuditLog;
use App\Models\LoyaltyCoupon;
use App\Models\LoyaltyProgram;
use App\Models\Payment;
use App\Models\WashOrder;
use App\Services\Customers\ImportCustomersAndVehiclesService;
use App\Support\AuditLogger;
use App\Support\Loyalty\LoyaltyProgress;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));

        $customers = TenantContext::scopeCustomers(Customer::query())
            ->withCount('vehicles')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhereHas('vehicles', fn ($query) => $query->where('plate', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $loyaltyProgram = LoyaltyProgram::query()
            ->where('wash_location_id', TenantContext::currentLocationId())
            ->where('is_active', true)
            ->first();

        $customers->getCollection()->each(function (Customer $customer) use ($loyaltyProgram): void {
            $customer->setAttribute('loyalty_progress', LoyaltyProgress::forCustomer($customer, $loyaltyProgram));
        });

        return view('app.customers.index', compact('customers', 'search', 'loyaltyProgram'));
    }

    public function create(): View
    {
        return view('app.customers.create', ['customer' => new Customer]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['wash_location_id'] = TenantContext::currentLocationId();

        $customer = Customer::create($data);

        AuditLogger::record(
            AuditLog::ACTION_CUSTOMER_CREATED,
            auth()->user()->name.' cadastrou o cliente '.$customer->name.'.',
            $customer,
            ['fields' => array_keys($data)],
        );

        return redirect()->route('customers.index')->with('status', 'Cliente cadastrado com sucesso.');
    }

    public function import(Request $request, ImportCustomersAndVehiclesService $importer): RedirectResponse
    {
        $data = $request->validate([
            'customers_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ], [
            'customers_file.required' => 'Selecione um arquivo CSV para importar.',
            'customers_file.mimes' => 'Envie um arquivo CSV válido.',
            'customers_file.max' => 'O arquivo deve ter no máximo 2 MB.',
        ]);

        $summary = $importer->handle($data['customers_file'], TenantContext::currentLocationId());

        AuditLogger::record(
            AuditLog::ACTION_CUSTOMERS_IMPORTED,
            auth()->user()->name.' importou clientes e veículos por CSV.',
            null,
            $summary,
        );

        return redirect()
            ->route('customers.index')
            ->with('status', $this->importStatusMessage($summary))
            ->with('import_summary', $summary);
    }

    public function importTemplate(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'w');

            if (! $output) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'nome',
                'telefone',
                'email',
                'cpf',
                'observacao',
                'placa',
                'marca',
                'modelo',
                'cor',
                'observacao_veiculo',
            ]);
            fputcsv($output, [
                'Maria Silva',
                '(11) 99999-0000',
                'maria@email.com',
                '123.456.789-00',
                'Prefere contato por WhatsApp',
                'ABC1D23',
                'Hyundai',
                'HB20',
                'Prata',
                'Sem adesivos',
            ]);
            fputcsv($output, [
                'João Santos',
                '(11) 98888-0000',
                'joao@email.com',
                '',
                'Cliente sem veículo no primeiro cadastro',
                '',
                '',
                '',
                '',
                '',
            ]);

            fclose($output);
        }, 'modelo-clientes-veiculos.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function edit(Customer $customer): View
    {
        TenantContext::abortUnlessModelBelongsToTenant($customer);

        $loyaltyProgram = LoyaltyProgram::query()
            ->where('wash_location_id', $customer->wash_location_id)
            ->where('is_active', true)
            ->first();
        $loyaltyProgress = LoyaltyProgress::forCustomer($customer, $loyaltyProgram);
        $loyaltyCoupons = LoyaltyCoupon::query()
            ->with(['loyaltyProgram', 'rewardService', 'sourceWashOrder.services'])
            ->where('wash_location_id', $customer->wash_location_id)
            ->where('customer_id', $customer->id)
            ->latest('earned_at')
            ->limit(10)
            ->get();
        $customerInsights = $this->customerInsights($customer);

        return view('app.customers.edit', compact('customer', 'loyaltyProgram', 'loyaltyProgress', 'loyaltyCoupons', 'customerInsights'));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        TenantContext::abortUnlessModelBelongsToTenant($customer);

        $before = $customer->only(['name', 'phone', 'email', 'cpf', 'notes']);
        $customer->update($this->validated($request));
        $after = $customer->only(['name', 'phone', 'email', 'cpf', 'notes']);
        $changedFields = array_keys(array_diff_assoc($after, $before));

        AuditLogger::record(
            AuditLog::ACTION_CUSTOMER_UPDATED,
            auth()->user()->name.' editou o cliente '.$customer->name.'.',
            $customer,
            ['changed_fields' => $changedFields],
        );

        return redirect()->route('customers.index')->with('status', 'Cliente atualizado com sucesso.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'cpf' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $summary
     */
    private function importStatusMessage(array $summary): string
    {
        if (($summary['imported_rows'] ?? 0) === 0) {
            return 'Nenhuma linha foi importada. Revise o arquivo e tente novamente.';
        }

        return sprintf(
            'Importação concluída: %d linha(s), %d cliente(s) criado(s), %d veículo(s) criado(s).',
            $summary['imported_rows'],
            $summary['created_customers'],
            $summary['created_vehicles'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function customerInsights(Customer $customer): array
    {
        $ordersQuery = WashOrder::query()
            ->where('wash_location_id', $customer->wash_location_id)
            ->where('customer_id', $customer->id);

        $totalWashOrders = (clone $ordersQuery)->count();
        $deliveredWashOrders = (clone $ordersQuery)->where('status', WashOrder::STATUS_DELIVERED)->count();
        $lastWashOrder = (clone $ordersQuery)->with(['vehicle', 'services'])->latest('entered_at')->first();
        $firstWashDate = (clone $ordersQuery)->oldest('entered_at')->value('entered_at');
        $totalRevenue = (float) Payment::query()
            ->whereHas('washOrder', fn ($query) => $query
                ->where('wash_location_id', $customer->wash_location_id)
                ->where('customer_id', $customer->id))
            ->sum('amount');
        $paidWashOrders = (clone $ordersQuery)
            ->whereIn('payment_status', [WashOrder::PAYMENT_PAID, WashOrder::PAYMENT_COURTESY, WashOrder::PAYMENT_CREDIT_PENDING])
            ->count();

        $topServices = DB::table('service_wash_order')
            ->join('wash_orders', 'wash_orders.id', '=', 'service_wash_order.wash_order_id')
            ->where('wash_orders.wash_location_id', $customer->wash_location_id)
            ->where('wash_orders.customer_id', $customer->id)
            ->select(
                'service_wash_order.service_name',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(service_wash_order.price) as revenue')
            )
            ->groupBy('service_wash_order.service_name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return [
            'total_wash_orders' => $totalWashOrders,
            'delivered_wash_orders' => $deliveredWashOrders,
            'total_revenue' => $totalRevenue,
            'average_ticket' => $paidWashOrders > 0 ? $totalRevenue / $paidWashOrders : 0,
            'first_wash_date' => $firstWashDate,
            'last_wash_order' => $lastWashOrder,
            'top_services' => $topServices,
            'favorite_service' => $topServices->first()?->service_name,
            'vehicles' => $customer->vehicles()
                ->withCount('washOrders')
                ->orderByDesc('wash_orders_count')
                ->orderBy('brand')
                ->get(),
            'recent_wash_orders' => (clone $ordersQuery)
                ->with(['vehicle', 'services', 'payments'])
                ->latest('entered_at')
                ->limit(8)
                ->get(),
            'active_coupons_count' => LoyaltyCoupon::query()
                ->where('wash_location_id', $customer->wash_location_id)
                ->where('customer_id', $customer->id)
                ->where('status', LoyaltyCoupon::STATUS_ACTIVE)
                ->where(function ($query): void {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>=', now());
                })
                ->count(),
        ];
    }
}
