<x-app.layout heading="Equipe" title="Equipe · AutoFlow">
    @include('app.components.errors')

    <div class="space-y-5">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Acessos e operacao</p>
                    <h2 class="mt-1 text-2xl font-black text-slate-950">Usuarios da equipe</h2>
                    <p class="mt-1 text-sm text-slate-500">Controle quem acessa o sistema e qual perfil cada pessoa utiliza na unidade.</p>
                </div>
                <a href="{{ route('employees.create') }}" class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-800">Novo usuario</a>
            </div>

            <form method="GET" class="mt-5 grid gap-3 md:grid-cols-[1fr_auto]">
                <label class="block">
                    <span class="sr-only">Buscar usuario</span>
                    <input name="search" value="{{ $search }}" placeholder="Buscar por nome, e-mail, telefone ou perfil" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </label>
                <div class="flex gap-2">
                    <button class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Filtrar</button>
                    @if ($search !== '')
                        <a href="{{ route('employees.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-500 hover:bg-slate-50">Limpar</a>
                    @endif
                </div>
            </form>
        </section>

        <section class="grid gap-3 md:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Usuarios na lista</p>
                <p class="mt-2 text-3xl font-black text-slate-950">{{ $employees->total() }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Ativos nesta página</p>
                <p class="mt-2 text-3xl font-black text-emerald-700">{{ $employees->getCollection()->where('is_active', true)->count() }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Perfis nesta página</p>
                <p class="mt-2 text-3xl font-black text-blue-700">{{ $employees->getCollection()->pluck('role')->unique()->count() }}</p>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-black text-slate-950">Lista da equipe</h2>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse ($employees as $employee)
                    <article class="grid gap-4 px-5 py-4 lg:grid-cols-[1fr_160px_220px_150px_auto] lg:items-center">
                        <div class="min-w-0">
                            <p class="truncate font-black text-slate-950">{{ $employee->name }}</p>
                            <p class="mt-1 truncate text-sm text-slate-500">{{ $employee->email }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Perfil</p>
                            <p class="mt-1 text-sm font-bold text-slate-900">{{ $roles[$employee->role] ?? $employee->role }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Contato</p>
                            <p class="mt-1 text-sm font-bold text-slate-900">{{ $employee->phone ?: '-' }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full px-3 py-1 text-xs font-black {{ $employee->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                {{ $employee->is_active ? 'Ativo' : 'Inativo' }}
                            </span>
                            <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-black text-blue-700">{{ $employee->last_login_at?->format('d/m/Y H:i') ?? 'Nunca' }}</span>
                        </div>
                        <div class="flex flex-wrap justify-start gap-2 lg:justify-end">
                            <a href="{{ route('employees.edit', $employee) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Editar</a>
                            @if ($employee->is_active && ! $employee->is(auth()->user()))
                                <form method="POST" action="{{ route('employees.destroy', $employee) }}" onsubmit="return confirm('Desativar este usuario da equipe?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-xl border border-red-200 px-4 py-2 text-sm font-bold text-red-700 hover:bg-red-50">Desativar</button>
                                </form>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="px-5 py-12 text-center">
                        <p class="font-black text-slate-950">Nenhum usuario da equipe encontrado</p>
                        <p class="mt-1 text-sm text-slate-500">Cadastre o primeiro usuario ou ajuste o filtro atual.</p>
                        <a href="{{ route('employees.create') }}" class="mt-4 inline-flex rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white">Novo usuario</a>
                    </div>
                @endforelse
            </div>
        </section>

        <div>{{ $employees->links() }}</div>
    </div>
</x-app.layout>
