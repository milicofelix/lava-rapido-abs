<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));

        $employees = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('role', 'like', "%{$search}%");
                });
            })
            ->withCount(['assignedWashOrders', 'washOrderTeams'])
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('app.employees.index', [
            'employees' => $employees,
            'search' => $search,
            'roles' => self::roles(),
        ]);
    }

    public function create(): View
    {
        return view('app.employees.create', [
            'employee' => new User,
            'roles' => self::roles(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        User::query()->create($this->validated($request));

        return redirect()->route('employees.index')->with('status', 'Funcionario cadastrado com sucesso.');
    }

    public function edit(User $employee): View
    {
        return view('app.employees.edit', [
            'employee' => $employee,
            'roles' => self::roles(),
        ]);
    }

    public function update(Request $request, User $employee): RedirectResponse
    {
        $employee->update($this->validated($request, $employee));

        return redirect()->route('employees.index')->with('status', 'Funcionario atualizado com sucesso.');
    }

    public static function roles(): array
    {
        return [
            'admin' => 'Admin / Dono',
            'attendant' => 'Atendente',
            'operator' => 'Lavador / Operacional',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?User $employee = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($employee)],
            'role' => ['required', Rule::in(array_keys(self::roles()))],
            'password' => [$employee ? 'nullable' : 'required', 'string', 'min:6', 'max:255'],
        ];

        $data = $request->validate($rules);

        if (($data['password'] ?? '') === '') {
            unset($data['password']);
        }

        return $data;
    }
}
