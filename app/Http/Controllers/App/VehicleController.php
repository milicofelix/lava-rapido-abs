<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));

        $vehicles = TenantContext::scopeVehicles(Vehicle::query())
            ->with('customer')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('plate', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('app.vehicles.index', compact('vehicles', 'search'));
    }

    public function create(): View
    {
        return view('app.vehicles.create', [
            'vehicle' => new Vehicle,
            'customers' => TenantContext::scopeCustomers(Customer::query())->orderBy('name')->get(),
            'types' => $this->types(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $customer = TenantContext::scopeCustomers(Customer::query())->findOrFail($data['customer_id']);
        $data['wash_location_id'] = $customer->wash_location_id;

        Vehicle::create($data);

        return redirect()->route('vehicles.index')->with('status', 'Veiculo cadastrado com sucesso.');
    }

    public function edit(Vehicle $vehicle): View
    {
        TenantContext::abortUnlessModelBelongsToTenant($vehicle);

        return view('app.vehicles.edit', [
            'vehicle' => $vehicle,
            'customers' => TenantContext::scopeCustomers(Customer::query())->orderBy('name')->get(),
            'types' => $this->types(),
        ]);
    }

    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        TenantContext::abortUnlessModelBelongsToTenant($vehicle);

        $data = $this->validated($request, $vehicle);
        $customer = TenantContext::scopeCustomers(Customer::query())->findOrFail($data['customer_id']);
        $data['wash_location_id'] = $customer->wash_location_id;

        $vehicle->update($data);

        return redirect()->route('vehicles.index')->with('status', 'Veiculo atualizado com sucesso.');
    }

    private function validated(Request $request, ?Vehicle $vehicle = null): array
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'plate' => ['required', 'string', 'max:12', Rule::unique('vehicles', 'plate')->ignore($vehicle)],
            'model' => ['required', 'string', 'max:100'],
            'brand' => ['required', 'string', 'max:100'],
            'color' => ['required', 'string', 'max:60'],
            'type' => ['required', Rule::in(array_keys($this->types()))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $data['plate'] = mb_strtoupper($data['plate']);

        return $data;
    }

    private function types(): array
    {
        return [
            'carro' => 'Carro',
            'moto' => 'Moto',
            'suv' => 'SUV',
            'caminhonete' => 'Caminhonete',
        ];
    }
}
