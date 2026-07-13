<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\AppSetting;
use App\Models\CustomerNotification;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WashOrder;
use App\Services\Loyalty\LoyaltyCouponApplicabilityService;
use App\Services\Loyalty\RemoveLoyaltyCouponService;
use App\Services\WashOrders\ChangeWashOrderStatusService;
use App\Services\WashOrders\CreateWashOrderService;
use App\Support\TenantContext;
use App\Support\Access\AccessControl;
use App\Support\WashOrders\WashOrderStatusFlow;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Illuminate\View\View;

class WashOrderController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $status = trim((string) $request->query('status'));

        $washOrders = TenantContext::scopeWashOrders(WashOrder::query())
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
            'canOpenWashOrderNow' => TenantContext::currentLocation()?->canOpenWashOrderAt() ?? true,
        ]);
    }

    public function create(): View
    {
        $currentLocation = TenantContext::currentLocation();

        $customers = TenantContext::scopeCustomers(Customer::query())
            ->with(['vehicles' => fn ($query) => TenantContext::scopeVehicles($query)->orderBy('plate')])
            ->orderBy('name')
            ->get();

        return view('app.wash-orders.create', [
            'currentLocation' => $currentLocation,
            'canOpenWashOrderNow' => $currentLocation?->canOpenWashOrderAt() ?? true,
            'customers' => $customers,
            'customerVehicles' => $customers->mapWithKeys(fn (Customer $customer) => [
                $customer->id => $customer->vehicles->map(fn (Vehicle $vehicle) => [
                    'id' => $vehicle->id,
                    'label' => "{$vehicle->plate} · {$vehicle->brand} {$vehicle->model}",
                ])->values(),
            ]),
            'services' => TenantContext::scopeServices(Service::query())->where('active', true)->orderBy('category')->orderBy('name')->get(),
            'users' => TenantContext::scopeUsers(User::query())->orderBy('name')->get(),
            'scheduleEnabled' => AppSetting::isModuleEnabled('module_schedule'),
            'suggestedScheduledAt' => $this->suggestedScheduledAt(request()),
        ]);
    }

    public function store(Request $request, CreateWashOrderService $creator): RedirectResponse
    {
        $data = $this->validated($request);
        $currentLocation = TenantContext::currentLocation();

        $vehicle = TenantContext::scopeVehicles(Vehicle::query())->findOrFail($data['vehicle_id']);

        if ((int) $vehicle->customer_id !== (int) $data['customer_id']) {
            return back()
                ->withErrors(['vehicle_id' => 'O veículo selecionado não pertence ao cliente informado.'])
                ->withInput();
        }

        $scheduledAt = filled($data['scheduled_at'] ?? null)
            && AppSetting::isModuleEnabled('module_schedule')
            ? Carbon::parse($data['scheduled_at'])
            : now();

        if ($currentLocation && ! $currentLocation->canOpenWashOrderAt($scheduledAt)) {
            $field = $scheduledAt->isFuture() ? 'scheduled_at' : 'wash_order';
            $message = $scheduledAt->isFuture()
                ? 'A unidade estará fechada no horário escolhido. Escolha um horário dentro do funcionamento.'
                : 'A unidade está fechada agora. Abra lavagens somente dentro do horário de funcionamento.';

            return back()
                ->withErrors([$field => $message])
                ->withInput();
        }

        $washOrder = $creator->handle([
            'wash_location_id' => TenantContext::currentLocationId(),
            'customer_id' => $data['customer_id'],
            'vehicle_id' => $data['vehicle_id'],
            'entered_at' => $scheduledAt,
            'notes' => $data['notes'] ?? null,
        ], $data['service_ids'], array_values(array_unique($data['assigned_user_ids'] ?? [])));

        return redirect()
            ->route('wash-orders.show', $washOrder)
            ->with('status', 'Ordem de lavagem criada com sucesso.');
    }

    public function show(
        WashOrder $washOrder,
        LoyaltyCouponApplicabilityService $couponApplicability,
        RemoveLoyaltyCouponService $removeLoyaltyCoupon,
    ): View {
        TenantContext::abortUnlessModelBelongsToTenant($washOrder);

        $paymentMethods = Payment::methods();

        if (! AppSetting::isModuleEnabled('module_credit_receivables')) {
            unset($paymentMethods[Payment::METHOD_CREDIT_PENDING]);
        }

        $washOrder = $washOrder->load([
            'customer.loyaltyCoupons' => fn ($query) => $query
                ->where('wash_location_id', $washOrder->wash_location_id)
                ->activeAndValid()
                ->with(['loyaltyProgram', 'rewardService', 'sourceWashOrder.services'])
                ->latest('earned_at'),
            'vehicle',
            'assignedUser',
            'teamMembers',
            'services',
            'washLocation',
            'loyaltyCoupon.loyaltyProgram',
            'loyaltyCoupon.rewardService',
            'loyaltyCoupon.usedByUser',
            'statusHistories.user',
            'payments.user',
            'payments.reversedBy',
            'customerNotifications.user',
        ]);
        $user = request()->user();
        $canOperateByBusinessHours = $washOrder->washLocation?->canOpenWashOrderAt() ?? true;
        $canUpdateStatus = AccessControl::allows($user, AccessControl::UPDATE_WASH_ORDER_STATUS)
            && $canOperateByBusinessHours
            && (! $user?->isOperator() || $washOrder->teamMembers->contains('id', $user->id));
        $statusBlockedReason = match (true) {
            ! $canOperateByBusinessHours => 'A unidade está fechada agora. Avance etapas somente dentro do horário de funcionamento.',
            ! AccessControl::allows($user, AccessControl::UPDATE_WASH_ORDER_STATUS) => 'Status restrito ao perfil de usuário.',
            $user?->isOperator() && ! $washOrder->teamMembers->contains('id', $user->id) => 'Status restrito a responsáveis da equipe desta lavagem.',
            default => null,
        };
        $loyaltyCouponEvaluations = $washOrder->customer->loyaltyCoupons
            ->mapWithKeys(fn ($coupon) => [$coupon->id => $couponApplicability->evaluate($washOrder, $coupon)]);
        $applicableLoyaltyCoupons = $washOrder->customer->loyaltyCoupons
            ->filter(fn ($coupon) => $loyaltyCouponEvaluations[$coupon->id]['applicable'] ?? false)
            ->values();

        return view('app.wash-orders.show', [
            'washOrder' => $washOrder,
            'statuses' => WashOrder::statuses(),
            'statusOptions' => [
                $washOrder->status => $washOrder->statusLabel(),
                ...WashOrderStatusFlow::allowedStatusLabelsForWashOrder($washOrder),
            ],
            'canUpdateStatus' => $canUpdateStatus,
            'paymentMethods' => $paymentMethods,
            'notificationTemplates' => CustomerNotification::templates(),
            'loyaltyCouponEvaluations' => $loyaltyCouponEvaluations,
            'applicableLoyaltyCoupons' => $applicableLoyaltyCoupons,
            'loyaltyCouponRemovalState' => $removeLoyaltyCoupon->removableState($washOrder),
            'statusBlockedReason' => $statusBlockedReason,
        ]);
    }

    public function updateStatus(Request $request, WashOrder $washOrder, ChangeWashOrderStatusService $changer): JsonResponse|RedirectResponse
    {
        TenantContext::abortUnlessModelBelongsToTenant($washOrder);

        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(WashOrder::statuses()))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $washOrder = $changer->handle($washOrder, $data['status'], $request->user(), $data['notes'] ?? null);
        } catch (InvalidArgumentException $exception) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => $exception->getMessage(),
                    'errors' => ['status' => [$exception->getMessage()]],
                ], 422);
            }

            return back()
                ->withErrors(['status' => $exception->getMessage()])
                ->withInput();
        }

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
        $locationId = TenantContext::currentLocationId();

        return $request->validate([
            'customer_id' => [
                'required',
                Rule::exists('customers', 'id')->where('wash_location_id', $locationId),
            ],
            'vehicle_id' => [
                'required',
                Rule::exists('vehicles', 'id')->where('wash_location_id', $locationId),
            ],
            'assigned_user_ids' => ['nullable', 'array'],
            'assigned_user_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('users', 'id')->where('wash_location_id', $locationId),
            ],
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('services', 'id')
                    ->where('wash_location_id', $locationId)
                    ->where('active', true),
            ],
            'notes' => ['nullable', 'string', 'max:2000'],
            'scheduled_at' => ['nullable', 'date'],
        ]);
    }

    private function suggestedScheduledAt(Request $request): ?string
    {
        if (! AppSetting::isModuleEnabled('module_schedule')) {
            return null;
        }

        try {
            $scheduledAt = Carbon::parse((string) $request->query('scheduled_at'));
        } catch (\Throwable) {
            return null;
        }

        return $scheduledAt->format('Y-m-d\TH:i');
    }
}
