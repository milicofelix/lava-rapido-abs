<x-app.layout heading="Histórico operacional" title="Histórico operacional · AutoFlow">
    <div class="space-y-5">
        @include('app.components.errors')

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" data-tour="history-header">
            <div class="mb-5 flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 pb-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Consulta operacional</p>
                    <h2 class="mt-1 text-2xl font-black text-slate-950">Histórico operacional</h2>
                    <p class="mt-1 text-sm text-slate-500">Audite lavagens por período, cliente, placa, serviço, funcionário, status e pagamento.</p>
                </div>
                <a href="{{ route('history.export', request()->query()) }}" class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm font-bold text-blue-700 hover:bg-blue-100" data-tour="history-export">Exportar CSV</a>
            </div>

            <form method="GET" action="{{ route('history.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4" data-tour="history-filters">
                <label class="block">
                    <span class="text-sm font-bold text-slate-700">Início</span>
                    <input data-period-start name="start" type="date" value="{{ $filters['start'] }}" max="{{ today()->toDateString() }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    @error('start') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-bold text-slate-700">Fim</span>
                    <input data-period-end name="end" type="date" value="{{ $filters['end'] }}" min="{{ $filters['start'] }}" max="{{ today()->toDateString() }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    @error('end') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-bold text-slate-700">Cliente</span>
                    <select name="customer_id" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <option value="">Todos</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected((string) $filters['customer_id'] === (string) $customer->id)>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-sm font-bold text-slate-700">Placa</span>
                    <input name="plate" value="{{ $filters['plate'] }}" placeholder="ABC1D23" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm uppercase shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </label>

                <label class="block">
                    <span class="text-sm font-bold text-slate-700">Serviço</span>
                    <select name="service_id" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <option value="">Todos</option>
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}" @selected((string) $filters['service_id'] === (string) $service->id)>{{ $service->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-sm font-bold text-slate-700">Status</span>
                    <select name="status" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <option value="">Todos</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-sm font-bold text-slate-700">Funcionário</span>
                    <select name="employee_id" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <option value="">Todos</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" @selected((string) $filters['employee_id'] === (string) $employee->id)>{{ $employee->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-sm font-bold text-slate-700">Pagamento</span>
                    <select name="payment_method" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <option value="">Todos</option>
                        @foreach ($paymentMethods as $value => $label)
                            <option value="{{ $value }}" @selected($filters['payment_method'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <div class="flex flex-wrap gap-3 md:col-span-2 xl:col-span-4" data-tour="history-filter-actions">
                    <button class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-800">Filtrar</button>
                    <a href="{{ route('history.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Limpar</a>
                </div>
            </form>
        </section>

        <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-4" data-tour="history-summary">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Lavagens no filtro</p>
                <p class="mt-2 text-3xl font-black text-slate-950">{{ $summary['count'] }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Total operacional</p>
                <p class="mt-2 text-3xl font-black text-blue-700">R$ {{ number_format((float) $summary['total'], 2, ',', '.') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Entregues</p>
                <p class="mt-2 text-3xl font-black text-emerald-700">{{ $summary['delivered'] }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Pagas</p>
                <p class="mt-2 text-3xl font-black text-emerald-700">{{ $summary['paid'] }}</p>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" data-tour="history-results">
            <div class="border-b border-slate-200 px-5 py-4">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Resultado</p>
                <h2 class="mt-1 font-black text-slate-950">Registros operacionais</h2>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse ($washOrders as $washOrder)
                    <article class="grid gap-4 px-5 py-5 xl:grid-cols-[170px_1fr_1fr_180px_140px_auto] xl:items-center" @if ($loop->first) data-tour="history-row" @endif>
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Entrada</p>
                            <p class="mt-1 text-sm font-bold text-slate-900">{{ $washOrder->entered_at?->format('d/m/Y H:i') ?? '-' }}</p>
                            <p class="mt-1 font-black text-slate-950">{{ $washOrder->code }}</p>
                        </div>
                        <div class="min-w-0">
                            <p class="truncate font-black text-slate-950">{{ $washOrder->customer->name }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $washOrder->customer->phone }}</p>
                            <p class="mt-2 font-bold tracking-wide text-slate-900">{{ $washOrder->vehicle->plate }}</p>
                            <p class="text-xs text-slate-500">{{ $washOrder->vehicle->brand }} {{ $washOrder->vehicle->model }}</p>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Serviços</p>
                            <p class="mt-1 text-sm text-slate-700">{{ $washOrder->services->pluck('pivot.service_name')->filter()->join(', ') ?: '-' }}</p>
                            <p class="mt-2 text-xs font-black uppercase tracking-[0.16em] text-slate-500">Equipe</p>
                            <p class="mt-1 text-sm text-slate-700">{{ $washOrder->teamMembers->isNotEmpty() ? $washOrder->teamMembers->pluck('name')->join(', ') : ($washOrder->assignedUser?->name ?? '-') }}</p>
                        </div>
                        <div>
                            @include('app.wash-orders._status-badge', ['status' => $washOrder->status, 'label' => $washOrder->statusLabel()])
                            <p class="mt-2 text-sm text-slate-700">
                                @if ($washOrder->payments->isNotEmpty())
                                    {{ $washOrder->payments->map(fn ($payment) => $payment->methodLabel())->unique()->join(', ') }}
                                @else
                                    {{ $washOrder->paymentStatusLabel() }}
                                @endif
                            </p>
                        </div>
                        <p class="font-black text-slate-950">R$ {{ number_format((float) $washOrder->total_amount, 2, ',', '.') }}</p>
                        <div class="flex justify-start xl:justify-end">
                            <a href="{{ route('wash-orders.show', $washOrder) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Abrir</a>
                        </div>
                    </article>
                @empty
                    <div class="px-5 py-12 text-center">
                        <p class="font-black text-slate-950">Nenhuma lavagem encontrada</p>
                        <p class="mt-1 text-sm text-slate-500">Ajuste os filtros para ampliar a consulta.</p>
                    </div>
                @endforelse
            </div>

            <div class="border-t border-slate-200 px-5 py-4">
                {{ $washOrders->links() }}
            </div>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const startInput = document.querySelector('[data-period-start]');
            const endInput = document.querySelector('[data-period-end]');

            if (!startInput || !endInput) {
                return;
            }

            startInput.addEventListener('change', () => {
                endInput.min = startInput.value || '';

                if (endInput.value && startInput.value && endInput.value < startInput.value) {
                    endInput.value = startInput.value;
                }
            });
        });
    </script>
    @php
        $historyTour = [
            'key' => 'history.index.v1',
            'title' => 'Entendendo o Histórico',
            'steps' => [
                [
                    'target' => '[data-tour="history-header"]',
                    'title' => 'Consulta operacional',
                    'body' => 'O histórico reúne lavagens já registradas para conferência por período, cliente, veículo, serviço, equipe, status e pagamento.',
                ],
                [
                    'target' => '[data-tour="history-export"]',
                    'title' => 'Exportação',
                    'body' => 'Exporte CSV para conferir dados fora do sistema. O arquivo respeita os filtros aplicados na tela.',
                ],
                [
                    'target' => '[data-tour="history-filters"]',
                    'title' => 'Filtros detalhados',
                    'body' => 'Combine datas, cliente, placa, serviço, status, funcionário e pagamento para localizar lavagens específicas.',
                ],
                [
                    'target' => '[data-tour="history-filter-actions"]',
                    'title' => 'Aplicar filtros',
                    'body' => 'Clique em Filtrar para atualizar a consulta ou Limpar para voltar ao histórico padrão.',
                ],
                [
                    'target' => '[data-tour="history-summary"]',
                    'title' => 'Resumo do filtro',
                    'body' => 'Estes cards mostram volume, total operacional, entregues e pagas conforme a consulta atual.',
                ],
                [
                    'target' => '[data-tour="history-results"]',
                    'title' => 'Registros operacionais',
                    'body' => 'A lista mostra as lavagens encontradas com cliente, placa, serviços, equipe, status, pagamento e valor.',
                ],
                [
                    'target' => '[data-tour="history-row"]',
                    'title' => 'Lavagem encontrada',
                    'body' => 'Abra uma lavagem para ver detalhes, pagamentos, cupons, histórico e link de acompanhamento do cliente.',
                ],
            ],
        ];
    @endphp
    <script type="application/json" data-onboarding-tour>
        {!! json_encode($historyTour, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
</x-app.layout>
