<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));

        $services = Service::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            })
            ->orderByDesc('active')
            ->orderBy('category')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('app.services.index', compact('services', 'search'));
    }

    public function create(): View
    {
        return view('app.services.create', ['service' => new Service]);
    }

    public function store(Request $request): RedirectResponse
    {
        Service::create($this->validated($request));

        return redirect()->route('services.index')->with('status', 'Servico cadastrado com sucesso.');
    }

    public function edit(Service $service): View
    {
        return view('app.services.edit', compact('service'));
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $service->update($this->validated($request));

        return redirect()->route('services.index')->with('status', 'Servico atualizado com sucesso.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'base_price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'estimated_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'active' => ['nullable', 'boolean'],
            'category' => ['required', 'string', 'max:100'],
        ]);

        $data['active'] = $request->boolean('active');

        return $data;
    }
}
