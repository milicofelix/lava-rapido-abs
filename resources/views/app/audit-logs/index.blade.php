<x-app.layout heading="Auditoria" title="Auditoria · AutoFlow">
    <div class="space-y-5">
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <form method="GET" action="{{ route('audit-logs.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <label class="block">
                    <span class="text-sm font-semibold text-slate-700">Inicio</span>
                    <input name="start" type="date" value="{{ $filters['start'] }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </label>

                <label class="block">
                    <span class="text-sm font-semibold text-slate-700">Fim</span>
                    <input name="end" type="date" value="{{ $filters['end'] }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </label>

                <label class="block">
                    <span class="text-sm font-semibold text-slate-700">Acao</span>
                    <select name="action" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Todas</option>
                        @foreach ($actions as $value => $label)
                            <option value="{{ $value }}" @selected($filters['action'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-sm font-semibold text-slate-700">Usuario</span>
                    <select name="user_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Todos</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected((string) $filters['user_id'] === (string) $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-sm font-semibold text-slate-700">Busca</span>
                    <input name="search" value="{{ $filters['search'] }}" placeholder="Cliente, lavagem, detalhe" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </label>

                <div class="flex flex-wrap gap-3 md:col-span-2 xl:col-span-5">
                    <button class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">Filtrar</button>
                    <a href="{{ route('audit-logs.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Limpar</a>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-semibold">Registro de acoes</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[980px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Quando</th>
                            <th class="px-5 py-3">Usuario</th>
                            <th class="px-5 py-3">Acao</th>
                            <th class="px-5 py-3">Registro</th>
                            <th class="px-5 py-3">Descricao</th>
                            <th class="px-5 py-3">Origem</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($logs as $log)
                            <tr>
                                <td class="px-5 py-4 text-slate-600">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-slate-950">{{ $log->user?->name ?? 'Sistema' }}</p>
                                    <p class="text-xs text-slate-500">{{ $log->user?->roleLabel() ?? '-' }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">{{ $log->actionLabel() }}</span>
                                </td>
                                <td class="px-5 py-4 font-semibold text-slate-800">{{ $log->subject_label ?? '-' }}</td>
                                <td class="px-5 py-4 text-slate-700">{{ $log->description }}</td>
                                <td class="px-5 py-4 text-xs text-slate-500">{{ $log->ip_address ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">Nenhum registro de auditoria encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 px-5 py-4">
                {{ $logs->links() }}
            </div>
        </section>
    </div>
</x-app.layout>
