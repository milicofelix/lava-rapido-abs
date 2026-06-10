<x-app.layout heading="Historico operacional" title="Historico operacional · AutoFlow">
    <div class="space-y-5">
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <form method="GET" action="{{ route('history.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <label class="block">
                    <span class="text-sm font-semibold text-slate-700">Inicio</span>
                    <input name="start" type="date" value="{{ $filters['start'] }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                </label>

                <label class="block">
                    <span class="text-sm font-semibold text-slate-700">Fim</span>
                    <input name="end" type="date" value="{{ $filters['end'] }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                </label>

                <label class="block">
                    <span class="text-sm font-semibold text-slate-700">Cliente</span>
                    <select name="customer_id" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Todos</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected((string) $filters['customer_id'] === (string) $customer->id)>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-sm font-semibold text-slate-700">Placa</span>
                    <input name="plate" value="{{ $filters['plate'] }}" placeholder="ABC1D23" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm uppercase">
                </label>

                <label class="block">
                    <span class="text-sm font-semibold text-slate-700">Servico</span>
                    <select name="service_id" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Todos</option>
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}" @selected((string) $filters['service_id'] === (string) $service->id)>{{ $service->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-sm font-semibold text-slate-700">Status</span>
                    <select name="status" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Todos</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-sm font-semibold text-slate-700">Funcionario</span>
                    <select name="employee_id" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Todos</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" @selected((string) $filters['employee_id'] === (string) $employee->id)>{{ $employee->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-sm font-semibold text-slate-700">Pagamento</span>
                    <select name="payment_method" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Todos</option>
                        @foreach ($paymentMethods as $value => $label)
                            <option value="{{ $value }}" @selected($filters['payment_method'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <div class="flex flex-wrap gap-3 md:col-span-2 xl:col-span-4">
                    <button class="rounded-md bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">Filtrar</button>
                    <a href="{{ route('history.index') }}" class="rounded-md border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Limpar</a>
                    <a href="{{ route('history.export', request()->query()) }}" class="rounded-md border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm font-semibold text-blue-700 hover:bg-blue-100">Exportar CSV</a>
                </div>
            </form>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Lavagens no filtro</p>
                <p class="mt-2 text-2xl font-bold">{{ $summary['count'] }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Total operacional</p>
                <p class="mt-2 text-2xl font-bold">R$ {{ number_format((float) $summary['total'], 2, ',', '.') }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Entregues</p>
                <p class="mt-2 text-2xl font-bold">{{ $summary['delivered'] }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Pagas</p>
                <p class="mt-2 text-2xl font-bold">{{ $summary['paid'] }}</p>
            </div>
        </section>

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-semibold">Registros operacionais</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1180px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Entrada</th>
                            <th class="px-5 py-3">Ordem</th>
                            <th class="px-5 py-3">Cliente</th>
                            <th class="px-5 py-3">Veiculo</th>
                            <th class="px-5 py-3">Servicos</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Equipe</th>
                            <th class="px-5 py-3">Pagamento</th>
                            <th class="px-5 py-3 text-right">Total</th>
                            <th class="px-5 py-3 text-right">Acao</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($washOrders as $washOrder)
                            <tr>
                                <td class="px-5 py-4 text-slate-600">{{ $washOrder->entered_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                <td class="px-5 py-4 font-semibold text-slate-950">{{ $washOrder->code }}</td>
                                <td class="px-5 py-4">
                                    <p class="font-medium">{{ $washOrder->customer->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $washOrder->customer->phone }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-medium">{{ $washOrder->vehicle->plate }}</p>
                                    <p class="text-xs text-slate-500">{{ $washOrder->vehicle->brand }} {{ $washOrder->vehicle->model }}</p>
                                </td>
                                <td class="px-5 py-4 text-slate-700">
                                    {{ $washOrder->services->pluck('pivot.service_name')->filter()->join(', ') ?: '-' }}
                                </td>
                                <td class="px-5 py-4">
                                    @include('app.wash-orders._status-badge', ['status' => $washOrder->status, 'label' => $washOrder->statusLabel()])
                                </td>
                                <td class="px-5 py-4 text-slate-700">
                                    {{ $washOrder->teamMembers->isNotEmpty() ? $washOrder->teamMembers->pluck('name')->join(', ') : ($washOrder->assignedUser?->name ?? '-') }}
                                </td>
                                <td class="px-5 py-4 text-slate-700">
                                    @if ($washOrder->payments->isNotEmpty())
                                        {{ $washOrder->payments->map(fn ($payment) => $payment->methodLabel())->unique()->join(', ') }}
                                    @else
                                        {{ $washOrder->paymentStatusLabel() }}
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right font-semibold">R$ {{ number_format((float) $washOrder->total_amount, 2, ',', '.') }}</td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('wash-orders.show', $washOrder) }}" class="font-semibold text-blue-700 hover:text-blue-900">Abrir</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-5 py-10 text-center text-sm text-slate-500">Nenhuma lavagem encontrada para os filtros aplicados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 px-5 py-4">
                {{ $washOrders->links() }}
            </div>
        </section>
    </div>
</x-app.layout>
