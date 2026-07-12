<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));

        $employees = TenantContext::scopeUsers(User::query())
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
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
            'roles' => self::rolesFor(auth()->user()),
        ]);
    }

    public function create(): View
    {
        return view('app.employees.create', [
            'employee' => new User,
            'roles' => self::rolesFor(auth()->user()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['wash_location_id'] = auth()->user()->wash_location_id;
        $data['is_active'] = true;

        User::query()->create($data);

        return redirect()->route('employees.index')->with('status', 'Usuario da equipe cadastrado com sucesso.');
    }

    public function edit(User $employee): View
    {
        $this->abortUnlessManageable($employee);

        return view('app.employees.edit', [
            'employee' => $employee,
            'roles' => self::rolesFor(auth()->user()),
        ]);
    }

    public function update(Request $request, User $employee): RedirectResponse
    {
        $this->abortUnlessManageable($employee);

        $employee->update($this->validated($request, $employee));

        return redirect()->route('employees.index')->with('status', 'Usuario da equipe atualizado com sucesso.');
    }

    public function destroy(User $employee): RedirectResponse
    {
        $this->abortUnlessManageable($employee);

        if ($employee->is(auth()->user())) {
            return back()->withErrors(['employee' => 'Você não pode desativar o próprio usuário.']);
        }

        if ($employee->isOwner() && $this->activeOwnersCount() <= 1) {
            return back()->withErrors(['employee' => 'Não é possível desativar o último dono da unidade.']);
        }

        $employee->update(['is_active' => false]);

        return redirect()->route('employees.index')->with('status', 'Usuario da equipe desativado com sucesso.');
    }

    public static function rolesFor(?User $user): array
    {
        $roles = User::roleLabels();
        unset($roles[User::ROLE_SUPER_ADMIN]);

        if (! $user?->isOwner()) {
            unset($roles[User::ROLE_OWNER]);
        }

        return $roles;
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?User $employee = null): array
    {
        $roles = array_keys(self::rolesFor($request->user()));

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($employee)],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in($roles)],
            'password' => [$employee ? 'nullable' : 'required', 'string', 'min:6', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];

        $data = $request->validate($rules);

        if ($employee && $employee->is($request->user())) {
            unset($data['is_active']);
        }

        if ($employee && $employee->isOwner() && ($data['role'] ?? $employee->role) !== User::ROLE_OWNER && $this->activeOwnersCount() <= 1) {
            abort(422, 'Não é possível remover o último dono da unidade.');
        }

        if (($data['password'] ?? '') === '') {
            unset($data['password']);
        }

        if (! array_key_exists('is_active', $data) && $employee) {
            unset($data['is_active']);
        }

        return $data;
    }

    private function abortUnlessManageable(User $employee): void
    {
        TenantContext::abortUnlessModelBelongsToTenant($employee);

        if ($employee->isSuperAdmin()) {
            abort(404);
        }

        if ($employee->isOwner() && ! auth()->user()->isOwner()) {
            abort(403);
        }
    }

    private function activeOwnersCount(): int
    {
        return TenantContext::scopeUsers(User::query())
            ->where('role', User::ROLE_OWNER)
            ->where('is_active', true)
            ->count();
    }
}
