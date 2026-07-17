<x-app.layout heading="Fiado / Contas a receber" title="Fiado · AutoFlow">
    <div class="space-y-5">
        @include('app.components.errors')

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" data-tour="credit-receivables-intro">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-amber-700">Recebimento posterior</p>
                    <h2 class="mt-1 text-2xl font-black text-slate-950">Fiado / Contas a receber</h2>
                    <p class="mt-1 text-sm text-slate-500">Lavagens marcadas como fiado / pendente para baixa quando o cliente pagar.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('finance.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Financeiro</a>
                    @if (\App\Models\AppSetting::isModuleEnabled('module_cash_register'))
                        <a href="{{ route('finance.cash-registers.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Caixa</a>
                    @endif
                </div>
            </div>
        </section>

        <section class="grid gap-3 md:grid-cols-3" data-tour="credit-receivables-summary">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Total pendente</p>
                <p class="mt-2 text-3xl font-black text-slate-950">R$ {{ number_format((float) $totalPending, 2, ',', '.') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Ordens pendentes</p>
                <p class="mt-2 text-3xl font-black text-amber-700">{{ $orders->total() }}</p>
            </div>
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
                <p class="text-sm font-bold text-amber-700">Proximo passo</p>
                <p class="mt-2 text-xl font-black text-amber-950">Receber e baixar no financeiro</p>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" data-tour="credit-receivables-list">
            <div class="border-b border-slate-200 px-5 py-4">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-amber-700">Pendencias</p>
                <h2 class="mt-1 font-black text-slate-950">Contas em aberto</h2>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($orders as $order)
                    <article class="grid gap-4 px-5 py-5 xl:grid-cols-[1fr_420px]" @if ($loop->first) data-tour="credit-receivables-order" @endif>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('wash-orders.show', $order) }}" class="text-lg font-black text-blue-700 hover:text-blue-900">{{ $order->code }}</a>
                                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-800">{{ $order->paymentStatusLabel() }}</span>
                            </div>
                            <p class="mt-2 text-sm font-bold text-slate-700">{{ $order->customer->name }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $order->vehicle->plate }} · {{ $order->vehicle->brand }} {{ $order->vehicle->model }}</p>
                            <p class="mt-1 text-sm text-slate-500">Entrada: {{ $order->entered_at?->format('d/m/Y H:i') }}</p>
                            <p class="mt-4 text-3xl font-black text-slate-950">R$ {{ number_format((float) $order->total_amount, 2, ',', '.') }}</p>
                        </div>

                        <form method="POST" action="{{ route('finance.credit-receivables.receive', $order) }}" class="rounded-2xl border border-slate-200 bg-slate-50 p-4" @if ($loop->first) data-tour="credit-receivables-receive-form" @endif>
                            @csrf
                            @method('PATCH')
                            <div class="grid gap-3 md:grid-cols-2">
                                <label class="block">
                                    <span class="text-sm font-bold text-slate-700">Metodo</span>
                                    <select name="method" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                        @foreach ($methods as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="block">
                                    <span class="text-sm font-bold text-slate-700">Valor recebido</span>
                                    <input name="amount" type="number" step="0.01" min="0.01" value="{{ old('amount', $order->total_amount) }}" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                </label>
                            </div>
                            <label class="mt-3 block">
                                <span class="text-sm font-bold text-slate-700">Observacao</span>
                                <input name="notes" value="{{ old('notes') }}" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100" placeholder="Ex: Recebido via Pix depois da retirada">
                            </label>
                            <button class="mt-4 rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-emerald-800">Marcar como recebido</button>
                        </form>
                    </article>
                @empty
                    <div class="px-5 py-12 text-center">
                        <p class="font-black text-slate-950">Nenhum fiado em aberto</p>
                        <p class="mt-1 text-sm text-slate-500">As contas recebidas deixam de aparecer nesta fila.</p>
                    </div>
                @endforelse
            </div>
            <div class="border-t border-slate-200 px-5 py-4">
                {{ $orders->links() }}
            </div>
        </section>
    </div>

    @php
        $creditReceivablesTour = [
            'key' => 'finance.credit-receivables.index.v1',
            'title' => 'Fiado e contas a receber',
            'steps' => [
                [
                    'target' => '[data-tour="credit-receivables-intro"]',
                    'title' => 'Recebimento posterior',
                    'body' => 'Esta tela reúne lavagens marcadas como fiado para baixa quando o cliente efetivamente pagar.',
                ],
                [
                    'target' => '[data-tour="credit-receivables-summary"]',
                    'title' => 'Resumo do fiado',
                    'body' => 'Acompanhe o total pendente, quantidade de ordens abertas e o próximo passo de cobrança.',
                ],
                [
                    'target' => '[data-tour="credit-receivables-list"]',
                    'title' => 'Contas em aberto',
                    'body' => 'A lista mostra apenas contas ainda não recebidas. Depois da baixa, a lavagem sai desta fila.',
                ],
                [
                    'target' => '[data-tour="credit-receivables-order"]',
                    'title' => 'Dados da lavagem',
                    'body' => 'Confira código, cliente, veículo, data de entrada e valor antes de registrar o recebimento.',
                ],
                [
                    'target' => '[data-tour="credit-receivables-receive-form"]',
                    'title' => 'Baixar recebimento',
                    'body' => 'Informe método, valor e observação. Ao marcar como recebido, o pagamento entra no financeiro.',
                ],
            ],
        ];
    @endphp

    <script type="application/json" data-onboarding-tour>
        {!! json_encode($creditReceivablesTour, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
</x-app.layout>
