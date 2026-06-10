<x-app.layout heading="Funcionarios" title="Funcionarios · AutoFlow">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex w-full flex-wrap gap-2 sm:w-auto">
            <input name="search" value="{{ $search }}" placeholder="Buscar por nome, e-mail ou perfil" class="w-full rounded-md border border-zinc-300 px-3 py-2 sm:w-80">
            <button class="rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold">Filtrar</button>
        </form>
        <a href="{{ route('employees.create') }}" class="rounded-md bg-cyan-700 px-4 py-2 text-sm font-semibold text-white">Novo funcionario</a>
    </div>

    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white">
        <table class="min-w-full divide-y divide-zinc-200">
            <thead class="bg-zinc-100 text-left text-sm text-zinc-600">
                <tr>
                    <th class="px-4 py-3">Nome</th>
                    <th class="px-4 py-3">E-mail</th>
                    <th class="px-4 py-3">Perfil</th>
                    <th class="px-4 py-3">Equipe</th>
                    <th class="px-4 py-3">Principal</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 text-sm">
                @forelse ($employees as $employee)
                    <tr>
                        <td class="px-4 py-3 font-semibold">{{ $employee->name }}</td>
                        <td class="px-4 py-3">{{ $employee->email }}</td>
                        <td class="px-4 py-3">{{ $roles[$employee->role] ?? $employee->role }}</td>
                        <td class="px-4 py-3">{{ $employee->wash_order_teams_count }}</td>
                        <td class="px-4 py-3">{{ $employee->assigned_wash_orders_count }}</td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('employees.edit', $employee) }}" class="font-semibold text-cyan-700">Editar</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-zinc-500">Nenhum funcionario encontrado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $employees->links() }}</div>
</x-app.layout>
