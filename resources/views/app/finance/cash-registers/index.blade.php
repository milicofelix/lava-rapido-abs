<x-app.layout heading="Caixa" title="Caixa · AutoFlow">
    <div class="space-y-5">
        @include('app.components.errors')

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-700">Controle de turno</p>
                    <h2 class="mt-1 text-2xl font-black text-slate-950">Caixa</h2>
                    <p class="mt-1 text-sm text-slate-500">Controle abertura, sangria, suprimento e fechamento do dinheiro fisico.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('finance.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Financeiro</a>
                    @if (\App\Models\AppSetting::isModuleEnabled('module_credit_receivables'))
                        <a href="{{ route('finance.credit-receivables.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Fiado</a>
                    @endif
                </div>
            </div>
        </section>

        @if (! $openRegister)
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="border-b border-slate-200 pb-4">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Início do turno</p>
                    <h2 class="mt-1 text-xl font-black text-slate-950">Abrir caixa</h2>
                    <p class="mt-1 text-sm text-slate-500">Informe o saldo inicial em dinheiro para iniciar o controle do turno.</p>
                </div>

                <form method="POST" action="{{ route('finance.cash-registers.store') }}" class="mt-5 grid gap-4 lg:grid-cols-[220px_1fr_auto] lg:items-end">
                    @csrf
                    <label class="block">
                        <span class="text-sm font-bold text-slate-700">Saldo inicial</span>
                        <input name="opening_balance" type="number" step="0.01" min="0" value="{{ old('opening_balance', '0.00') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </label>
                    <label class="block">
                        <span class="text-sm font-bold text-slate-700">Observacao</span>
                        <input name="opening_notes" value="{{ old('opening_notes') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100" placeholder="Ex: Troco inicial do turno da manha">
                    </label>
                    <button class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-800">Abrir caixa</button>
                </form>
            </section>
        @else
            <section class="grid gap-3 md:grid-cols-4">
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
                    <p class="text-sm font-bold text-emerald-700">Status</p>
                    <p class="mt-2 text-3xl font-black text-emerald-950">{{ $openRegister->statusLabel() }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-sm font-bold text-slate-500">Saldo inicial</p>
                    <p class="mt-2 text-3xl font-black text-slate-950">R$ {{ number_format((float) $openRegister->opening_balance, 2, ',', '.') }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-sm font-bold text-slate-500">Dinheiro recebido</p>
                    <p class="mt-2 text-3xl font-black text-blue-700">R$ {{ number_format((float) $cashPaymentTotal, 2, ',', '.') }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-sm font-bold text-slate-500">Esperado em caixa</p>
                    <p class="mt-2 text-3xl font-black text-emerald-700">R$ {{ number_format((float) $expectedCash, 2, ',', '.') }}</p>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 pb-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Conferência do turno</p>
                        <h2 class="mt-1 font-black text-slate-950">Resumo para fechamento</h2>
                        <p class="mt-1 text-sm text-slate-500">Confira vendas por método, entradas manuais e sangrias antes de encerrar o caixa.</p>
                    </div>
                    <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-right">
                        <p class="text-xs font-black uppercase tracking-[0.14em] text-emerald-700">Previsto em dinheiro</p>
                        <p class="mt-1 text-2xl font-black text-emerald-950">R$ {{ number_format((float) $cashRegisterSummary['expected_cash'], 2, ',', '.') }}</p>
                    </div>
                </div>

                <div class="mt-4 grid gap-3 md:grid-cols-4">
                    <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-sm font-bold text-slate-500">Vendas totais</p>
                        <p class="mt-2 text-2xl font-black text-slate-950">R$ {{ number_format((float) $cashRegisterSummary['payment_total'], 2, ',', '.') }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-sm font-bold text-slate-500">Suprimentos</p>
                        <p class="mt-2 text-2xl font-black text-blue-700">R$ {{ number_format((float) $cashRegisterSummary['supplies'], 2, ',', '.') }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-sm font-bold text-slate-500">Sangrias</p>
                        <p class="mt-2 text-2xl font-black text-amber-700">R$ {{ number_format((float) $cashRegisterSummary['withdrawals'], 2, ',', '.') }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-sm font-bold text-slate-500">Movimento manual líquido</p>
                        <p class="mt-2 text-2xl font-black text-slate-950">R$ {{ number_format((float) $cashRegisterSummary['net_manual_movements'], 2, ',', '.') }}</p>
                    </div>
                </div>

                <div class="mt-4 grid gap-3 md:grid-cols-3 xl:grid-cols-6">
                    @foreach ($cashRegisterSummary['payments_by_method'] as $methodSummary)
                        <div class="rounded-2xl border border-slate-100 p-3">
                            <p class="text-xs font-black uppercase tracking-[0.12em] text-slate-500">{{ $methodSummary['label'] }}</p>
                            <p class="mt-2 text-lg font-black text-slate-950">R$ {{ number_format((float) $methodSummary['total'], 2, ',', '.') }}</p>
                            <p class="mt-1 text-xs font-bold text-slate-500">{{ $methodSummary['count'] }} pagamento(s)</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="grid gap-5 xl:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="border-b border-slate-200 pb-4">
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Movimento manual</p>
                        <h2 class="mt-1 text-xl font-black text-slate-950">Registrar sangria ou suprimento</h2>
                    </div>
                    <form method="POST" action="{{ route('finance.cash-registers.movements.store', $openRegister) }}" class="mt-4 space-y-4">
                        @csrf
                        <label class="block">
                            <span class="text-sm font-bold text-slate-700">Tipo</span>
                            <select name="type" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                @foreach ($movementTypes as $value => $label)
                                    <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="text-sm font-bold text-slate-700">Valor</span>
                            <input name="amount" type="number" step="0.01" min="0.01" value="{{ old('amount') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        </label>
                        <label class="block">
                            <span class="text-sm font-bold text-slate-700">Descrição</span>
                            <input name="description" value="{{ old('description') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100" placeholder="Ex: Sangria para cofre">
                        </label>
                        <button class="rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-slate-800">Registrar movimentacao</button>
                    </form>
                </div>

                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                    <div class="border-b border-emerald-200 pb-4">
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-700">Encerramento</p>
                        <h2 class="mt-1 text-xl font-black text-emerald-950">Fechar caixa</h2>
                        <p class="mt-1 text-sm font-semibold text-emerald-800">Conte apenas o dinheiro fisico no caixa. Pix e cartao ficam no financeiro.</p>
                    </div>
                    <form method="POST" action="{{ route('finance.cash-registers.close', $openRegister) }}" class="mt-4 space-y-4">
                        @csrf
                        @method('PATCH')
                        <label class="block">
                            <span class="text-sm font-bold text-emerald-950">Dinheiro contado</span>
                            <input name="counted_cash" type="number" step="0.01" min="0" value="{{ old('counted_cash') }}" class="mt-1 w-full rounded-xl border border-emerald-200 bg-white px-3 py-2.5 text-sm shadow-sm">
                        </label>
                        <label class="block">
                            <span class="text-sm font-bold text-emerald-950">Observacao de fechamento</span>
                            <textarea name="closing_notes" rows="3" class="mt-1 w-full rounded-xl border border-emerald-200 bg-white px-3 py-2.5 text-sm shadow-sm">{{ old('closing_notes') }}</textarea>
                        </label>
                        <button class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-emerald-800">Fechar caixa</button>
                    </form>
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Caixa atual</p>
                    <h2 class="mt-1 font-black text-slate-950">Movimentacoes deste caixa</h2>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($openRegister->movements->sortByDesc('occurred_at') as $movement)
                        <article class="grid gap-4 px-5 py-4 md:grid-cols-[150px_150px_1fr_140px_160px] md:items-center">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Data</p>
                                <p class="mt-1 text-sm font-bold text-slate-900">{{ $movement->occurred_at?->format('d/m/Y H:i') }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Tipo</p>
                                <p class="mt-1 text-sm font-bold text-slate-900">{{ $movement->typeLabel() }}</p>
                            </div>
                            <p class="text-sm text-slate-600">{{ $movement->description }}</p>
                            <p class="font-black text-slate-950">R$ {{ number_format((float) $movement->amount, 2, ',', '.') }}</p>
                            <p class="truncate text-sm font-bold text-slate-600">{{ $movement->user?->name }}</p>
                        </article>
                    @empty
                        <p class="px-5 py-10 text-center text-sm text-slate-500">Nenhuma movimentacao registrada.</p>
                    @endforelse
                </div>
            </section>
        @endif

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Histórico</p>
                <h2 class="mt-1 font-black text-slate-950">Histórico de caixas</h2>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($recentRegisters as $register)
                    @php($summary = $register->cash_summary)
                    @php($difference = $summary['cash_difference'])
                    @php($differenceTone = $difference === null || abs($difference) < 0.01 ? 'text-emerald-700' : ($difference > 0 ? 'text-blue-700' : 'text-red-700'))
                    <article class="grid gap-4 px-5 py-4 xl:grid-cols-[160px_160px_130px_1fr_160px] xl:items-center">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Abertura</p>
                            <p class="mt-1 text-sm font-bold text-slate-900">{{ $register->opened_at?->format('d/m/Y H:i') }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Fechamento</p>
                            <p class="mt-1 text-sm font-bold text-slate-900">{{ $register->closed_at?->format('d/m/Y H:i') ?? '-' }}</p>
                        </div>
                        <div>
                            <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-700">{{ $register->statusLabel() }}</span>
                        </div>
                        <div class="grid gap-2 sm:grid-cols-4">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Inicial</p>
                                <p class="mt-1 text-sm font-bold text-slate-900">R$ {{ number_format((float) $register->opening_balance, 2, ',', '.') }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Esperado</p>
                                <p class="mt-1 text-sm font-bold text-slate-900">R$ {{ number_format((float) $summary['expected_cash'], 2, ',', '.') }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Contado</p>
                                <p class="mt-1 text-sm font-bold text-slate-900">{{ $summary['counted_cash'] !== null ? 'R$ '.number_format((float) $summary['counted_cash'], 2, ',', '.') : '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Diferenca</p>
                                <p class="mt-1 text-sm font-black {{ $differenceTone }}">{{ $difference !== null ? 'R$ '.number_format((float) $difference, 2, ',', '.') : '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Vendas</p>
                                <p class="mt-1 text-sm font-bold text-slate-900">R$ {{ number_format((float) $summary['payment_total'], 2, ',', '.') }}</p>
                            </div>
                        </div>
                        <p class="truncate text-sm font-bold text-slate-600">{{ $register->openedBy?->name }}</p>
                    </article>
                @empty
                    <p class="px-5 py-10 text-center text-sm text-slate-500">Nenhum caixa registrado.</p>
                @endforelse
            </div>
            <div class="border-t border-slate-200 px-5 py-4">
                {{ $recentRegisters->links() }}
            </div>
        </section>
    </div>
</x-app.layout>
