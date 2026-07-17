<x-app.layout heading="Equipe" title="Equipe · AutoFlow">
    @include('app.components.errors')

    <div class="space-y-5">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" data-tour="employees-header">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Acessos e operacao</p>
                    <h2 class="mt-1 text-2xl font-black text-slate-950">Usuarios da equipe</h2>
                    <p class="mt-1 text-sm text-slate-500">Controle quem acessa o sistema e qual perfil cada pessoa utiliza na unidade.</p>
                </div>
                <a href="{{ route('employees.create') }}" class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-800" data-tour="employees-create">Novo usuario</a>
            </div>

            <form method="GET" class="mt-5 grid gap-3 md:grid-cols-[1fr_auto]" data-tour="employees-search">
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

        <section class="grid gap-3 md:grid-cols-3" data-tour="employees-indicators">
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

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" data-tour="employees-list">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-black text-slate-950">Lista da equipe</h2>
                <p class="mt-1 text-sm text-slate-500">Audite rapidamente os acessos efetivos de cada usuário antes de liberar novas responsabilidades.</p>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse ($employees as $employee)
                    @php
                        $audit = $employee->permission_audit ?? ['effective' => [], 'enabled_overrides' => [], 'blocked_overrides' => []];
                        $effectivePermissions = collect($audit['effective']);
                        $visiblePermissions = $effectivePermissions->take(7);
                        $hiddenPermissionsCount = max(0, $effectivePermissions->count() - $visiblePermissions->count());
                        $enabledOverrides = collect($audit['enabled_overrides']);
                        $blockedOverrides = collect($audit['blocked_overrides']);
                    @endphp
                    <article class="grid gap-4 px-5 py-4 lg:grid-cols-[1fr_150px_200px_180px_auto] lg:items-start" @if ($loop->first) data-tour="employees-row" @endif>
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
                        <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4 lg:col-span-5" @if ($loop->first) data-tour="employees-permission-audit" @endif>
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Auditoria de permissões</p>
                                    <p class="mt-1 text-sm text-slate-600">{{ $effectivePermissions->count() }} acesso{{ $effectivePermissions->count() === 1 ? '' : 's' }} efetivo{{ $effectivePermissions->count() === 1 ? '' : 's' }} para este usuário.</p>
                                </div>
                                @if (! $employee->is_active)
                                    <span class="rounded-full bg-slate-200 px-3 py-1 text-xs font-black text-slate-600">Acesso bloqueado por inatividade</span>
                                @elseif ($enabledOverrides->isNotEmpty())
                                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">Possui exceção configurada</span>
                                @else
                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-black text-slate-600 ring-1 ring-slate-200">Perfil padrão</span>
                                @endif
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2">
                                @forelse ($visiblePermissions as $permission)
                                    <span title="{{ $permissionDescriptions[$permission] ?? '' }}" class="rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-700 ring-1 ring-slate-200">
                                        {{ $permissionLabels[$permission] ?? $permission }}
                                    </span>
                                @empty
                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-500 ring-1 ring-slate-200">Nenhuma permissão efetiva</span>
                                @endforelse

                                @if ($hiddenPermissionsCount > 0)
                                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-black text-blue-700 ring-1 ring-blue-100">+ {{ $hiddenPermissionsCount }} acesso{{ $hiddenPermissionsCount === 1 ? '' : 's' }}</span>
                                @endif
                            </div>

                            @if ($enabledOverrides->isNotEmpty() || $blockedOverrides->isNotEmpty())
                                <div class="mt-3 grid gap-3 md:grid-cols-2">
                                    <div>
                                        <p class="text-xs font-black uppercase tracking-[0.14em] text-emerald-700">Exceções liberadas</p>
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @forelse ($enabledOverrides as $permission)
                                                <span title="{{ $permissionDescriptions[$permission] ?? '' }}" class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700 ring-1 ring-emerald-100">+ {{ $permissionLabels[$permission] ?? $permission }}</span>
                                            @empty
                                                <span class="text-sm font-semibold text-slate-400">Nenhuma</span>
                                            @endforelse
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-xs font-black uppercase tracking-[0.14em] text-rose-700">Bloqueadas pela configuração</p>
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @forelse ($blockedOverrides as $permission)
                                                <span title="{{ $permissionDescriptions[$permission] ?? '' }}" class="rounded-full bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700 ring-1 ring-rose-100">{{ $permissionLabels[$permission] ?? $permission }}</span>
                                            @empty
                                                <span class="text-sm font-semibold text-slate-400">Nenhuma</span>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
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

    @php
        $employeesTour = [
            'key' => 'employees.index.v1',
            'title' => 'Equipe e permissões',
            'steps' => [
                [
                    'target' => '[data-tour="employees-header"]',
                    'title' => 'Equipe da unidade',
                    'body' => 'Aqui você controla quem acessa o sistema e qual função cada pessoa terá no lava-rápido.',
                ],
                [
                    'target' => '[data-tour="employees-create"]',
                    'title' => 'Novo usuário',
                    'body' => 'Cadastre operadores, atendentes ou administradores para participar das lavagens e acessar o painel.',
                ],
                [
                    'target' => '[data-tour="employees-search"]',
                    'title' => 'Filtro da equipe',
                    'body' => 'Busque por nome, e-mail, telefone ou perfil quando a equipe crescer.',
                ],
                [
                    'target' => '[data-tour="employees-indicators"]',
                    'title' => 'Resumo da lista',
                    'body' => 'Veja rapidamente quantos usuários existem, quantos estão ativos e quantos perfis aparecem na página.',
                ],
                [
                    'target' => '[data-tour="employees-list"]',
                    'title' => 'Lista da equipe',
                    'body' => 'Cada linha mostra dados de contato, perfil, status de acesso e último login.',
                ],
                [
                    'target' => '[data-tour="employees-permission-audit"]',
                    'title' => 'Auditoria de permissões',
                    'body' => 'Esta área mostra os acessos efetivos do usuário e destaca exceções liberadas ou bloqueadas pela configuração.',
                ],
                [
                    'target' => '[data-tour="employees-row"]',
                    'title' => 'Editar ou desativar',
                    'body' => 'Use as ações da linha para atualizar perfil, contato, senha provisória ou bloquear um acesso.',
                ],
            ],
        ];
    @endphp
    <script type="application/json" data-onboarding-tour>
        {!! json_encode($employeesTour, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
</x-app.layout>
