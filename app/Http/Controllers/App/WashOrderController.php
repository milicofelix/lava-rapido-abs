<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerNotification;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WashOrder;
use App\Services\WashOrders\ChangeWashOrderStatusService;
use App\Services\WashOrders\CreateWashOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WashOrderController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $status = trim((string) $request->query('status'));

        $washOrders = WashOrder::query()
            ->with(['customer', 'vehicle', 'assignedUser', 'teamMembers', 'services'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('vehicle', fn ($query) => $query->where('plate', 'like', "%{$search}%"));
                });
            })
            ->latest('entered_at')
            ->paginate(10)
            ->withQueryString();

        return view('app.wash-orders.index', [
            'washOrders' => $washOrders,
            'search' => $search,
            'status' => $status,
            'statuses' => WashOrder::statuses(),
        ]);
    }

    public function create(): View
    {
        $customers = Customer::with(['vehicles' => fn ($query) => $query->orderBy('plate')])->orderBy('name')->get();

        return view('app.wash-orders.create', [
            'customers' => $customers,
            'customerVehicles' => $customers->mapWithKeys(fn (Customer $customer) => [
                $customer->id => $customer->vehicles->map(fn (Vehicle $vehicle) => [
                    'id' => $vehicle->id,
                    'label' => "{$vehicle->plate} · {$vehicle->brand} {$vehicle->model}",
                ])->values(),
            ]),
            'services' => Service::where('active', true)->orderBy('category')->orderBy('name')->get(),
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, CreateWashOrderService $creator): RedirectResponse
    {
        $data = $this->validated($request);

        $vehicle = Vehicle::findOrFail($data['vehicle_id']);

        if ((int) $vehicle->customer_id !== (int) $data['customer_id']) {
            return back()
                ->withErrors(['vehicle_id' => 'O veiculo selecionado nao pertence ao cliente informado.'])
                ->withInput();
        }

        $washOrder = $creator->handle([
            'customer_id' => $data['customer_id'],
            'vehicle_id' => $data['vehicle_id'],
            'entered_at' => now(),
            'notes' => $data['notes'] ?? null,
        ], $data['service_ids'], array_values(array_unique($data['assigned_user_ids'] ?? [])));

        return redirect()
            ->route('wash-orders.show', $washOrder)
            ->with('status', 'Ordem de lavagem criada com sucesso.');
    }

    public function show(WashOrder $washOrder): View
    {
        return view('app.wash-orders.show', [
            'washOrder' => $washOrder->load(['customer', 'vehicle', 'assignedUser', 'teamMembers', 'services', 'statusHistories.user', 'payments.user', 'customerNotifications.user']),
            'statuses' => WashOrder::statuses(),
            'paymentMethods' => Payment::methods(),
            'notificationTemplates' => CustomerNotification::templates(),
        ]);
    }

    public function updateStatus(Request $request, WashOrder $washOrder, ChangeWashOrderStatusService $changer): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(WashOrder::statuses()))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $washOrder = $changer->handle($washOrder, $data['status'], $request->user(), $data['notes'] ?? null);

        if ($request->wantsJson()) {
            return response()->json([
                'id' => $washOrder->id,
                'status' => $washOrder->status,
                'status_label' => $washOrder->statusLabel(),
            ]);
        }

        return back()->with('status', 'Status atualizado com sucesso.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'assigned_user_ids' => ['nullable', 'array'],
            'assigned_user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('services', 'id')->where('active', true),
            ],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
