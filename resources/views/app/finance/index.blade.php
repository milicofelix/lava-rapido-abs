<x-app.layout heading="Financeiro" title="Financeiro · AutoFlow">
    @php
        $appSettings = \App\Models\AppSetting::allSettings();
    @endphp

    <div class="space-y-5">
        @include('app.components.errors')

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" data-tour="finance-header">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-700">Controle financeiro</p>
                    <h2 class="mt-1 text-2xl font-black text-slate-950">Recebimentos e indicadores</h2>
                    <p class="mt-1 text-sm text-slate-500">Acompanhe os pagamentos registrados no periodo e exporte o relatorio quando precisar.</p>
                </div>

                @if (! empty($appSettings['module_cash_register']) || ! empty($appSettings['module_credit_receivables']))
                    <div class="flex flex-wrap gap-2" data-tour="finance-modules">
                        @if (! empty($appSettings['module_cash_register']))
                            <a href="{{ route('finance.cash-registers.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Caixa</a>
                        @endif
                        @if (! empty($appSettings['module_credit_receivables']))
                            <a href="{{ route('finance.credit-receivables.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Fiado / contas a receber</a>
                        @endif
                    </div>
                @endif
            </div>

            <form method="GET" action="{{ route('finance.index') }}" class="mt-5 grid gap-3 lg:grid-cols-[1fr_1fr_auto_auto] lg:items-end" data-tour="finance-period">
                <label class="block">
                    <span class="text-sm font-bold text-slate-700">Início</span>
                    <input data-period-start name="start" type="date" value="{{ $start }}" max="{{ today()->toDateString() }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    @error('start') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="text-sm font-bold text-slate-700">Fim</span>
                    <input data-period-end name="end" type="date" value="{{ $end }}" min="{{ $start }}" max="{{ today()->toDateString() }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    @error('end') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                </label>
                <button class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-800">Filtrar</button>
                <a href="{{ route('finance.export', ['start' => $start, 'end' => $end]) }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-center text-sm font-bold text-slate-700 hover:bg-slate-50">Exportar CSV</a>
            </form>
        </section>

        <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-5" data-tour="finance-indicators">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Total do periodo</p>
                <p class="mt-2 text-3xl font-black text-slate-950">R$ {{ number_format((float) $total, 2, ',', '.') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Pagamentos</p>
                <p class="mt-2 text-3xl font-black text-blue-700">{{ $count }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Ticket medio</p>
                <p class="mt-2 text-3xl font-black text-emerald-700">R$ {{ number_format((float) $ticketAverage, 2, ',', '.') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Ordens pendentes</p>
                <p class="mt-2 text-3xl font-black text-amber-700">{{ $pendingCount }}</p>
            </div>
            @if (! empty($appSettings['module_credit_receivables']))
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-sm font-bold text-slate-500">Fiado / pendente</p>
                    <p class="mt-2 text-3xl font-black text-red-700">{{ $creditPendingCount }}</p>
                </div>
            @endif
        </section>

        <section class="grid gap-5 xl:grid-cols-[360px_1fr]">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" data-tour="finance-methods">
                <div class="border-b border-slate-200 px-5 py-4">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-700">Resumo</p>
                    <h2 class="mt-1 font-black text-slate-950">Por metodo</h2>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($totalsByMethod as $methodTotal)
                        <div class="flex items-center justify-between gap-4 px-5 py-4">
                            <div>
                                <p class="font-black text-slate-950">{{ $methods[$methodTotal->method] ?? $methodTotal->method }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $methodTotal->count }} registro(s)</p>
                            </div>
                            <p class="font-black text-emerald-700">R$ {{ number_format((float) $methodTotal->total, 2, ',', '.') }}</p>
                        </div>
                    @empty
                        <p class="px-5 py-4 text-sm text-slate-500">Nenhum pagamento no periodo.</p>
                    @endforelse
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" data-tour="finance-statement">
                <div class="border-b border-slate-200 px-5 py-4">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-700">Extrato</p>
                    <h2 class="mt-1 font-black text-slate-950">Pagamentos recebidos</h2>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($payments as $payment)
                        <article class="grid gap-4 px-5 py-4 xl:grid-cols-[145px_minmax(0,1.35fr)_minmax(0,150px)_120px_minmax(0,170px)] xl:items-center">
                            <div class="min-w-0">
                                <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Data</p>
                                <p class="mt-1 text-sm font-bold text-slate-900">{{ $payment->paid_at?->format('d/m/Y H:i') ?? '-' }}</p>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Lavagem</p>
                                <a href="{{ route('wash-orders.show', $payment->washOrder) }}" class="mt-1 block max-w-full truncate font-black text-blue-700 hover:text-blue-900" title="{{ $payment->washOrder->code }}">{{ $payment->washOrder->code }}</a>
                                <p class="mt-1 truncate text-sm text-slate-500" title="{{ $payment->washOrder->customer->name }} · {{ $payment->washOrder->vehicle->plate }}">{{ $payment->washOrder->customer->name }} · {{ $payment->washOrder->vehicle->plate }}</p>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Método</p>
                                <p class="mt-1 text-sm font-bold text-slate-900">{{ $payment->methodLabel() }}</p>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Valor</p>
                                <p class="mt-1 font-black text-emerald-700">R$ {{ number_format((float) $payment->amount, 2, ',', '.') }}</p>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Registrado por</p>
                                <p class="mt-1 truncate text-sm font-bold text-slate-900">{{ $payment->user?->name ?? 'Sistema' }}</p>
                            </div>
                        </article>
                    @empty
                        <div class="px-5 py-12 text-center">
                            <p class="font-black text-slate-950">Nenhum pagamento encontrado</p>
                            <p class="mt-1 text-sm text-slate-500">Ajuste o periodo ou registre pagamentos nas lavagens.</p>
                        </div>
                    @endforelse
                </div>
                <div class="border-t border-slate-200 px-5 py-4">
                    {{ $payments->links() }}
                </div>
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
        $financeTour = [
            'key' => 'finance.index.v1',
            'title' => 'Entendendo o Financeiro',
            'steps' => [
                [
                    'target' => '[data-tour="finance-header"]',
                    'title' => 'Controle financeiro',
                    'body' => 'Esta tela mostra os pagamentos efetivamente recebidos no período selecionado e ajuda a conferir o caixa do lava-rápido.',
                ],
                [
                    'target' => '[data-tour="finance-modules"]',
                    'title' => 'Módulos relacionados',
                    'body' => 'Quando Caixa ou Fiado estiverem habilitados, use estes atalhos para acessar controles específicos sem misturar com o extrato principal.',
                ],
                [
                    'target' => '[data-tour="finance-period"]',
                    'title' => 'Período e exportação',
                    'body' => 'Filtre por data inicial e final. O sistema não permite período invertido nem datas futuras. Use Exportar CSV para conferências externas.',
                ],
                [
                    'target' => '[data-tour="finance-indicators"]',
                    'title' => 'Indicadores do período',
                    'body' => 'Aqui ficam total recebido, quantidade de pagamentos, ticket médio e pendências financeiras que ainda precisam de atenção.',
                ],
                [
                    'target' => '[data-tour="finance-methods"]',
                    'title' => 'Resumo por método',
                    'body' => 'Confira quanto entrou por Pix, dinheiro, cartão, cortesia ou outros métodos registrados nas lavagens.',
                ],
                [
                    'target' => '[data-tour="finance-statement"]',
                    'title' => 'Extrato de recebimentos',
                    'body' => 'Cada linha mostra data, lavagem, cliente, placa, método, valor e quem registrou o pagamento. Clique no código para abrir os detalhes da lavagem.',
                ],
            ],
        ];
    @endphp
    <script type="application/json" data-onboarding-tour>
        {!! json_encode($financeTour, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
</x-app.layout>
