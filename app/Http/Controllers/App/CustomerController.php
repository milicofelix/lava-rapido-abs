<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\AuditLog;
use App\Models\LoyaltyCoupon;
use App\Models\LoyaltyProgram;
use App\Support\AuditLogger;
use App\Support\Loyalty\LoyaltyProgress;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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

    public function edit(Customer $customer): View
    {
        TenantContext::abortUnlessModelBelongsToTenant($customer);

        $loyaltyProgram = LoyaltyProgram::query()
            ->where('wash_location_id', $customer->wash_location_id)
            ->where('is_active', true)
            ->first();
        $loyaltyProgress = LoyaltyProgress::forCustomer($customer, $loyaltyProgram);
        $loyaltyCoupons = LoyaltyCoupon::query()
            ->with('rewardService')
            ->where('wash_location_id', $customer->wash_location_id)
            ->where('customer_id', $customer->id)
            ->latest('earned_at')
            ->limit(10)
            ->get();

        return view('app.customers.edit', compact('customer', 'loyaltyProgram', 'loyaltyProgress', 'loyaltyCoupons'));
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
}
