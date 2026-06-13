<?php

namespace App\Http\Controllers\App\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        return view('app.super-admin.plans.index', [
            'plans' => Plan::query()->orderByDesc('is_active')->orderBy('price')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Plan::query()->create($this->validatedData($request));

        return back()->with('success', 'Plano criado com sucesso.');
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $plan->update($this->validatedData($request, $plan));

        return back()->with('success', 'Plano atualizado com sucesso.');
    }

    public function deactivate(Plan $plan): RedirectResponse
    {
        $plan->update(['is_active' => false]);

        return back()->with('success', 'Plano desativado. Ele nao aparecera para novas escolhas.');
    }

    /**
     * @return array{name: string, price: float, trial_days: int, is_active: bool}
     */
    private function validatedData(Request $request, ?Plan $plan = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('plans', 'name')->ignore($plan)],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'trial_days' => ['required', 'integer', 'min:0', 'max:365'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return [
            'name' => $data['name'],
            'price' => (float) $data['price'],
            'trial_days' => (int) $data['trial_days'],
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
