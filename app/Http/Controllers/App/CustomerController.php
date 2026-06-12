<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Customer;
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

        return view('app.customers.index', compact('customers', 'search'));
    }

    public function create(): View
    {
        return view('app.customers.create', ['customer' => new Customer]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['wash_location_id'] = TenantContext::currentLocationId();

        Customer::create($data);

        return redirect()->route('customers.index')->with('status', 'Cliente cadastrado com sucesso.');
    }

    public function edit(Customer $customer): View
    {
        TenantContext::abortUnlessModelBelongsToTenant($customer);

        return view('app.customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        TenantContext::abortUnlessModelBelongsToTenant($customer);

        $customer->update($this->validated($request));

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
