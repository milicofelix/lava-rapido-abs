<x-app.layout heading="Agenda" title="Agenda · AutoFlow">
    <div class="space-y-5">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Agenda diaria</p>
                    <h2 class="mt-1 text-2xl font-black text-slate-950">{{ $selectedDate->format('d/m/Y') }}</h2>
                    <p class="mt-1 text-sm text-slate-500">Lavagens abertas na data selecionada, ordenadas por previsao/entrada.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('schedule.index', ['date' => $previousDate]) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Dia anterior</a>
                    <a href="{{ route('schedule.index') }}" class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-bold text-blue-700 hover:bg-blue-100">Hoje</a>
                    <a href="{{ route('schedule.index', ['date' => $nextDate]) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Proximo dia</a>
                    <a href="{{ route('wash-orders.create') }}" class="rounded-xl bg-blue-700 px-4 py-2 text-sm font-bold text-white hover:bg-blue-800">Nova lavagem</a>
                </div>
            </div>

            <form method="GET" action="{{ route('schedule.index') }}" class="mt-5 grid gap-3 sm:grid-cols-[220px_auto_1fr]">
                <label class="block">
                    <span class="text-sm font-semibold text-slate-700">Data</span>
                    <input type="date" name="date" value="{{ $selectedDate->toDateString() }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </label>
                <div class="flex items-end">
                    <button class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Filtrar</button>
                </div>
            </form>
        </section>

        <section class="grid gap-3 md:grid-cols-3">
            @foreach ([
                ['label' => 'Lavagens no dia', 'value' => $summary['total'], 'color' => 'bg-blue-50 text-blue-700'],
                ['label' => 'Em aberto', 'value' => $summary['open'], 'color' => 'bg-amber-50 text-amber-700'],
                ['label' => 'Entregues', 'value' => $summary['delivered'], 'color' => 'bg-emerald-50 text-emerald-700'],
            ] as $item)
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-sm font-bold text-slate-600">{{ $item['label'] }}</p>
                    <p class="mt-2 text-3xl font-black {{ $item['color'] }} inline-flex rounded-xl px-3 py-1">{{ $item['value'] }}</p>
                </div>
            @endforeach
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-black text-slate-950">Linha do dia</h2>
                <a href="{{ route('kanban') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Abrir Kanban</a>
            </div>

            <div class="mt-5 space-y-3">
                @forelse ($washOrders as $washOrder)
                    <a href="{{ route('wash-orders.show', $washOrder) }}" class="grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:border-blue-200 hover:bg-blue-50/40 md:grid-cols-[120px_1fr_auto]">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Horario</p>
                            <p class="mt-1 text-lg font-black text-slate-950">{{ $washOrder->estimated_completion_at?->format('H:i') ?? $washOrder->entered_at->format('H:i') }}</p>
                            <p class="text-xs text-slate-500">Entrada {{ $washOrder->entered_at->format('H:i') }}</p>
                        </div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-black text-slate-950">{{ $washOrder->vehicle->plate }}</p>
                                @include('app.wash-orders._status-badge', ['status' => $washOrder->status, 'label' => $washOrder->statusLabel()])
                            </div>
                            <p class="mt-1 truncate text-sm font-semibold text-slate-700">{{ $washOrder->customer->name }} · {{ $washOrder->vehicle->brand }} {{ $washOrder->vehicle->model }}</p>
                            <p class="mt-2 text-sm text-slate-500">{{ $washOrder->services->pluck('pivot.service_name')->filter()->join(', ') ?: 'Servico nao informado' }}</p>
                        </div>
                        <div class="text-sm text-slate-600 md:text-right">
                            <p class="font-bold text-slate-900">{{ $washOrder->teamMembers->isNotEmpty() ? $washOrder->teamMembers->pluck('name')->join(', ') : 'Sem equipe' }}</p>
                            <p class="mt-1">R$ {{ number_format((float) $washOrder->total_amount, 2, ',', '.') }}</p>
                        </div>
                    </a>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
                        Nenhuma lavagem agendada para esta data.
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</x-app.layout>
