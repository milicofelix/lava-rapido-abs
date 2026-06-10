<x-app.layout heading="Financeiro" title="Financeiro · AutoFlow">
    <div class="space-y-5">
        @php($appSettings = \App\Models\AppSetting::allSettings())
        @if (! empty($appSettings['module_cash_register']) || ! empty($appSettings['module_credit_receivables']))
            <div class="flex flex-wrap justify-end gap-2">
                @if (! empty($appSettings['module_cash_register']))
                    <a href="{{ route('finance.cash-registers.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold">Caixa</a>
                @endif
                @if (! empty($appSettings['module_credit_receivables']))
                    <a href="{{ route('finance.credit-receivables.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold">Fiado / contas a receber</a>
                @endif
            </div>
        @endif

        <section class="rounded-lg border border-zinc-200 bg-white p-5">
            <form method="GET" action="{{ route('finance.index') }}" class="grid gap-4 md:grid-cols-[1fr_1fr_auto_auto] md:items-end">
                <label class="block">
                    <span class="text-sm font-medium">Inicio</span>
                    <input name="start" type="date" value="{{ $start }}" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2">
                </label>
                <label class="block">
                    <span class="text-sm font-medium">Fim</span>
                    <input name="end" type="date" value="{{ $end }}" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2">
                </label>
                <button class="rounded-md bg-cyan-700 px-4 py-2.5 text-sm font-semibold text-white">Filtrar</button>
                <a href="{{ route('finance.export', ['start' => $start, 'end' => $end]) }}" class="rounded-md border border-zinc-300 px-4 py-2.5 text-center text-sm font-semibold">Exportar CSV</a>
            </form>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-lg border border-zinc-200 bg-white p-5">
                <p class="text-sm text-zinc-500">Total do periodo</p>
                <p class="mt-2 text-2xl font-semibold">R$ {{ number_format((float) $total, 2, ',', '.') }}</p>
            </div>
            <div class="rounded-lg border border-zinc-200 bg-white p-5">
                <p class="text-sm text-zinc-500">Pagamentos</p>
                <p class="mt-2 text-2xl font-semibold">{{ $count }}</p>
            </div>
            <div class="rounded-lg border border-zinc-200 bg-white p-5">
                <p class="text-sm text-zinc-500">Ticket medio</p>
                <p class="mt-2 text-2xl font-semibold">R$ {{ number_format((float) $ticketAverage, 2, ',', '.') }}</p>
            </div>
            <div class="rounded-lg border border-zinc-200 bg-white p-5">
                <p class="text-sm text-zinc-500">Ordens pendentes</p>
                <p class="mt-2 text-2xl font-semibold">{{ $pendingCount }}</p>
            </div>
            @if (! empty($appSettings['module_credit_receivables']))
                <div class="rounded-lg border border-zinc-200 bg-white p-5">
                    <p class="text-sm text-zinc-500">Fiado / pendente</p>
                    <p class="mt-2 text-2xl font-semibold">{{ $creditPendingCount }}</p>
                </div>
            @endif
        </section>

        <section class="grid gap-5 xl:grid-cols-[360px_1fr]">
            <div class="rounded-lg border border-zinc-200 bg-white">
                <div class="border-b border-zinc-200 px-5 py-4">
                    <h2 class="font-semibold">Por metodo</h2>
                </div>
                <div class="divide-y divide-zinc-100">
                    @forelse ($totalsByMethod as $methodTotal)
                        <div class="flex items-center justify-between gap-4 px-5 py-4">
                            <div>
                                <p class="font-medium">{{ $methods[$methodTotal->method] ?? $methodTotal->method }}</p>
                                <p class="text-sm text-zinc-500">{{ $methodTotal->count }} registro(s)</p>
                            </div>
                            <p class="font-semibold">R$ {{ number_format((float) $methodTotal->total, 2, ',', '.') }}</p>
                        </div>
                    @empty
                        <p class="px-5 py-4 text-sm text-zinc-500">Nenhum pagamento no periodo.</p>
                    @endforelse
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white">
                <div class="border-b border-zinc-200 px-5 py-4">
                    <h2 class="font-semibold">Pagamentos recebidos</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[820px] text-left text-sm">
                        <thead class="bg-zinc-50 text-xs uppercase text-zinc-500">
                            <tr>
                                <th class="px-5 py-3">Data</th>
                                <th class="px-5 py-3">Ordem</th>
                                <th class="px-5 py-3">Cliente</th>
                                <th class="px-5 py-3">Placa</th>
                                <th class="px-5 py-3">Metodo</th>
                                <th class="px-5 py-3 text-right">Valor</th>
                                <th class="px-5 py-3">Registrado por</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            @forelse ($payments as $payment)
                                <tr>
                                    <td class="px-5 py-4 text-zinc-600">{{ $payment->paid_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td class="px-5 py-4 font-medium">
                                        <a href="{{ route('wash-orders.show', $payment->washOrder) }}" class="text-cyan-700 hover:text-cyan-900">{{ $payment->washOrder->code }}</a>
                                    </td>
                                    <td class="px-5 py-4">{{ $payment->washOrder->customer->name }}</td>
                                    <td class="px-5 py-4">{{ $payment->washOrder->vehicle->plate }}</td>
                                    <td class="px-5 py-4">{{ $payment->methodLabel() }}</td>
                                    <td class="px-5 py-4 text-right font-semibold">R$ {{ number_format((float) $payment->amount, 2, ',', '.') }}</td>
                                    <td class="px-5 py-4 text-zinc-600">{{ $payment->user?->name ?? 'Sistema' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-8 text-center text-zinc-500">Nenhum pagamento encontrado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-zinc-200 px-5 py-4">
                    {{ $payments->links() }}
                </div>
            </div>
        </section>
    </div>
</x-app.layout>
