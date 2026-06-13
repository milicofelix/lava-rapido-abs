<x-app.layout heading="Equipe" title="Equipe · AutoFlow">
    @include('app.components.errors')

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex w-full flex-wrap gap-2 sm:w-auto">
            <input name="search" value="{{ $search }}" placeholder="Buscar por nome, e-mail, telefone ou perfil" class="w-full rounded-md border border-zinc-300 px-3 py-2 sm:w-80">
            <button class="rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold">Filtrar</button>
        </form>
        <a href="{{ route('employees.create') }}" class="rounded-md bg-cyan-700 px-4 py-2 text-sm font-semibold text-white">Novo usuario</a>
    </div>

    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white">
        <table class="min-w-full divide-y divide-zinc-200">
            <thead class="bg-zinc-100 text-left text-sm text-zinc-600">
                <tr>
                    <th class="px-4 py-3">Nome</th>
                    <th class="px-4 py-3">Perfil</th>
                    <th class="px-4 py-3">E-mail</th>
                    <th class="px-4 py-3">Telefone</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Ultimo acesso</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 text-sm">
                @forelse ($employees as $employee)
                    <tr>
                        <td class="px-4 py-3 font-semibold">{{ $employee->name }}</td>
                        <td class="px-4 py-3">{{ $roles[$employee->role] ?? $employee->role }}</td>
                        <td class="px-4 py-3">{{ $employee->email }}</td>
                        <td class="px-4 py-3">{{ $employee->phone ?: '-' }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $employee->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-zinc-100 text-zinc-600' }}">
                                {{ $employee->is_active ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">{{ $employee->last_login_at?->format('d/m/Y H:i') ?? 'Nunca' }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-3">
                                <a href="{{ route('employees.edit', $employee) }}" class="font-semibold text-cyan-700">Editar</a>
                                @if ($employee->is_active && ! $employee->is(auth()->user()))
                                    <form method="POST" action="{{ route('employees.destroy', $employee) }}" onsubmit="return confirm('Desativar este usuario da equipe?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="font-semibold text-red-700">Desativar</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-zinc-500">Nenhum usuario da equipe encontrado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $employees->links() }}</div>
</x-app.layout>
