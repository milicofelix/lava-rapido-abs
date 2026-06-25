<x-app.layout heading="Auditoria" title="Auditoria · AutoFlow">
    <div class="space-y-5">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-5 border-b border-slate-200 pb-4">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Rastreabilidade</p>
                <h2 class="mt-1 text-2xl font-black text-slate-950">Auditoria</h2>
                <p class="mt-1 text-sm text-slate-500">Consulte ações realizadas por usuários da unidade e encontre mudanças sensíveis rapidamente.</p>
            </div>

            <form method="GET" action="{{ route('audit-logs.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <label class="block">
                    <span class="text-sm font-bold text-slate-700">Inicio</span>
                    <input name="start" type="date" value="{{ $filters['start'] }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </label>

                <label class="block">
                    <span class="text-sm font-bold text-slate-700">Fim</span>
                    <input name="end" type="date" value="{{ $filters['end'] }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </label>

                <label class="block">
                    <span class="text-sm font-bold text-slate-700">Acao</span>
                    <select name="action" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <option value="">Todas</option>
                        @foreach ($actions as $value => $label)
                            <option value="{{ $value }}" @selected($filters['action'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-sm font-bold text-slate-700">Usuario</span>
                    <select name="user_id" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <option value="">Todos</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected((string) $filters['user_id'] === (string) $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-sm font-bold text-slate-700">Busca</span>
                    <input name="search" value="{{ $filters['search'] }}" placeholder="Cliente, lavagem, detalhe" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </label>

                <div class="flex flex-wrap gap-3 md:col-span-2 xl:col-span-5">
                    <button class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-800">Filtrar</button>
                    <a href="{{ route('audit-logs.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Limpar</a>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Eventos</p>
                <h2 class="mt-1 font-black text-slate-950">Registro de acoes</h2>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse ($logs as $log)
                    <article class="grid gap-4 px-5 py-5 xl:grid-cols-[170px_220px_170px_1fr_140px] xl:items-start">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Quando</p>
                            <p class="mt-1 text-sm font-bold text-slate-900">{{ $log->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <div class="min-w-0">
                            <p class="truncate font-black text-slate-950">{{ $log->user?->name ?? 'Sistema' }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $log->user?->roleLabel() ?? '-' }}</p>
                        </div>
                        <div>
                            <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-black text-blue-700">{{ $log->actionLabel() }}</span>
                        </div>
                        <div class="min-w-0">
                            <p class="font-black text-slate-950">{{ $log->subject_label ?? '-' }}</p>
                            <p class="mt-2 text-sm text-slate-700">{{ $log->description }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Origem</p>
                            <p class="mt-1 break-all text-xs text-slate-500">{{ $log->ip_address ?? '-' }}</p>
                        </div>
                    </article>
                @empty
                    <div class="px-5 py-12 text-center">
                        <p class="font-black text-slate-950">Nenhum registro de auditoria encontrado</p>
                        <p class="mt-1 text-sm text-slate-500">Ajuste os filtros ou aguarde novas ações no sistema.</p>
                    </div>
                @endforelse
            </div>

            <div class="border-t border-slate-200 px-5 py-4">
                {{ $logs->links() }}
            </div>
        </section>
    </div>
</x-app.layout>
