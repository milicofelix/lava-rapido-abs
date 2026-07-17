<x-app.layout heading="Lavagens" title="Lavagens · AutoFlow">
    <div class="space-y-5">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" data-tour="wash-orders-header">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Operacao diaria</p>
                    <h2 class="mt-1 text-2xl font-black text-slate-950">Lavagens registradas</h2>
                    <p class="mt-1 text-sm text-slate-500">Consulte ordens por codigo, cliente, placa ou etapa atual do atendimento.</p>
                </div>
                <div class="flex flex-wrap gap-2" data-tour="wash-orders-actions">
                    <a href="{{ route('kanban') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Kanban</a>
                    @if ($canOpenWashOrderNow)
                        <a href="{{ route('wash-orders.create') }}" class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-800">Nova lavagem</a>
                    @else
                        <span title="Unidade fechada pelo horário de funcionamento" class="cursor-not-allowed rounded-xl bg-slate-200 px-4 py-2.5 text-sm font-bold text-slate-500">Nova lavagem</span>
                    @endif
                </div>
            </div>

            <form method="GET" class="mt-5 grid gap-3 lg:grid-cols-[1fr_220px_auto]" data-tour="wash-orders-filters">
                <label class="block">
                    <span class="sr-only">Buscar lavagem</span>
                    <input name="search" value="{{ $search }}" placeholder="Buscar por codigo, cliente ou placa" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </label>
                <label class="block">
                    <span class="sr-only">Status</span>
                    <select name="status" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <option value="">Todos os status</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="flex gap-2">
                    <button class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Filtrar</button>
                    @if ($search !== '' || $status !== '')
                        <a href="{{ route('wash-orders.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-500 hover:bg-slate-50">Limpar</a>
                    @endif
                </div>
            </form>
        </section>

        <section class="grid gap-3 md:grid-cols-4" data-tour="wash-orders-indicators">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Lavagens na lista</p>
                <p class="mt-2 text-3xl font-black text-slate-950">{{ $washOrders->total() }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Nesta página</p>
                <p class="mt-2 text-3xl font-black text-blue-700">{{ $washOrders->count() }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Pendentes nesta página</p>
                <p class="mt-2 text-3xl font-black text-amber-700">{{ $washOrders->getCollection()->whereNotIn('status', [\App\Models\WashOrder::STATUS_DELIVERED])->count() }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Total nesta página</p>
                <p class="mt-2 text-3xl font-black text-emerald-700">R$ {{ number_format((float) $washOrders->getCollection()->sum('total_amount'), 2, ',', '.') }}</p>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" data-tour="wash-orders-list">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-black text-slate-950">Lista de lavagens</h2>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse ($washOrders as $washOrder)
                    <article class="grid gap-4 px-5 py-4 xl:grid-cols-[160px_1fr_1fr_180px_140px_auto] xl:items-center" @if ($loop->first) data-tour="wash-orders-row" @endif>
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Código</p>
                            <p class="mt-1 font-black text-slate-950">{{ $washOrder->code }}</p>
                        </div>
                        <div class="min-w-0">
                            <p class="truncate font-black text-slate-950">{{ $washOrder->customer->name }}</p>
                            <p class="mt-1 truncate text-sm text-slate-500">{{ $washOrder->teamMembers->isNotEmpty() ? $washOrder->teamMembers->pluck('name')->join(', ') : 'Sem equipe definida' }}</p>
                        </div>
                        <div class="min-w-0">
                            <p class="font-black tracking-wide text-slate-950">{{ $washOrder->vehicle->plate }}</p>
                            <p class="mt-1 truncate text-sm text-slate-500">{{ $washOrder->vehicle->brand }} {{ $washOrder->vehicle->model }}</p>
                        </div>
                        <div>
                            @include('app.wash-orders._status-badge', ['status' => $washOrder->status, 'label' => $washOrder->statusLabel()])
                            <p class="mt-2 text-xs font-bold text-slate-500">Previsão: {{ $washOrder->estimated_completion_at?->format('H:i') ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Total</p>
                            <p class="mt-1 font-black text-slate-950">R$ {{ number_format((float) $washOrder->total_amount, 2, ',', '.') }}</p>
                        </div>
                        <div class="flex justify-start xl:justify-end">
                            <a href="{{ route('wash-orders.show', $washOrder) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Abrir</a>
                        </div>
                    </article>
                @empty
                    <div class="px-5 py-12 text-center">
                        <p class="font-black text-slate-950">Nenhuma lavagem encontrada</p>
                        <p class="mt-1 text-sm text-slate-500">Abra uma nova lavagem ou ajuste os filtros atuais.</p>
                        @if ($canOpenWashOrderNow)
                            <a href="{{ route('wash-orders.create') }}" class="mt-4 inline-flex rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white">Nova lavagem</a>
                        @else
                            <p class="mt-4 inline-flex rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-bold text-slate-500">Unidade fechada para novas lavagens</p>
                        @endif
                    </div>
                @endforelse
            </div>
        </section>

        <div>{{ $washOrders->links() }}</div>
    </div>

    @php
        $washOrdersTour = [
            'key' => 'wash-orders.index.v1',
            'title' => 'Lista de lavagens',
            'steps' => [
                [
                    'target' => '[data-tour="wash-orders-header"]',
                    'title' => 'Operação diária',
                    'body' => 'Esta tela reúne as lavagens registradas e ajuda a consultar rapidamente cliente, veículo, status e valor.',
                ],
                [
                    'target' => '[data-tour="wash-orders-actions"]',
                    'title' => 'Ações principais',
                    'body' => 'Abra o Kanban para operar por etapas ou crie uma nova lavagem quando a unidade estiver dentro do horário permitido.',
                ],
                [
                    'target' => '[data-tour="wash-orders-filters"]',
                    'title' => 'Filtros',
                    'body' => 'Busque por código, cliente ou placa e filtre por status para encontrar atendimentos específicos.',
                ],
                [
                    'target' => '[data-tour="wash-orders-indicators"]',
                    'title' => 'Resumo da lista',
                    'body' => 'Os cards mostram volume, pendências e total financeiro apenas dos itens exibidos na listagem atual.',
                ],
                [
                    'target' => '[data-tour="wash-orders-list"]',
                    'title' => 'Lavagens registradas',
                    'body' => 'Cada registro mostra código, cliente, equipe, veículo, status, previsão e valor da lavagem.',
                ],
                [
                    'target' => '[data-tour="wash-orders-row"]',
                    'title' => 'Abrir detalhes',
                    'body' => 'Use Abrir para acessar status, pagamentos, link do cliente, cupons e histórico operacional da lavagem.',
                ],
            ],
        ];
    @endphp
    <script type="application/json" data-onboarding-tour>
        {!! json_encode($washOrdersTour, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
</x-app.layout>
