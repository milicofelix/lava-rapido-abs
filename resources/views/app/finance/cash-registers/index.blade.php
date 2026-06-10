<x-app.layout heading="Caixa" title="Caixa · AutoFlow">
    <div class="space-y-5">
        ('app.components.errors')

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm text-slate-500">Controle de abertura, sangria, suprimento e fechamento.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('finance.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold">Financeiro</a>
                <a href="{{ route('finance.credit-receivables.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold">Fiado</a>
            </div>
        </div>

        @if (! $openRegister)
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold">Abrir caixa</h2>
                <p class="mt-1 text-sm text-slate-500">Informe o saldo inicial em dinheiro para iniciar o controle do turno.</p>

                <form method="POST" action="{{ route('finance.cash-registers.store') }}" class="mt-5 grid gap-4 md:grid-cols-[220px_1fr_auto] md:items-end">
                    @csrf
                    <label class="block">
                        <span class="text-sm font-medium">Saldo inicial</span>
                        <input name="opening_balance" type="number" step="0.01" min="0" value="{{ old('opening_balance', '0.00') }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium">Observacao</span>
                        <input name="opening_notes" value="{{ old('opening_notes') }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" placeholder="Ex: Troco inicial do turno da manha">
                    </label>
                    <button class="rounded-md bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white">Abrir caixa</button>
                </form>
            </section>
        @else
            <section class="grid gap-4 md:grid-cols-4">
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
                    <p class="text-sm text-emerald-700">Status</p>
                    <p class="mt-2 text-2xl font-semibold text-emerald-950">{{ $openRegister->statusLabel() }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <p class="text-sm text-slate-500">Saldo inicial</p>
                    <p class="mt-2 text-2xl font-semibold">R$ {{ number_format((float) $openRegister->opening_balance, 2, ',', '.') }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <p class="text-sm text-slate-500">Dinheiro recebido</p>
                    <p class="mt-2 text-2xl font-semibold">R$ {{ number_format((float) $cashPaymentTotal, 2, ',', '.') }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <p class="text-sm text-slate-500">Esperado em caixa</p>
                    <p class="mt-2 text-2xl font-semibold">R$ {{ number_format((float) $expectedCash, 2, ',', '.') }}</p>
                </div>
            </section>

            <section class="grid gap-5 xl:grid-cols-[1fr_1fr]">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-semibold">Registrar sangria ou suprimento</h2>
                    <form method="POST" action="{{ route('finance.cash-registers.movements.store', $openRegister) }}" class="mt-4 space-y-4">
                        @csrf
                        <label class="block">
                            <span class="text-sm font-medium">Tipo</span>
                            <select name="type" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
                                @foreach ($movementTypes as $value => $label)
                                    <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="text-sm font-medium">Valor</span>
                            <input name="amount" type="number" step="0.01" min="0.01" value="{{ old('amount') }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
                        </label>
                        <label class="block">
                            <span class="text-sm font-medium">Descricao</span>
                            <input name="description" value="{{ old('description') }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" placeholder="Ex: Sangria para cofre">
                        </label>
                        <button class="rounded-md bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white">Registrar movimentacao</button>
                    </form>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-semibold">Fechar caixa</h2>
                    <p class="mt-1 text-sm text-slate-500">Conte apenas o dinheiro fisico no caixa. Pix e cartao ficam no financeiro.</p>
                    <form method="POST" action="{{ route('finance.cash-registers.close', $openRegister) }}" class="mt-4 space-y-4">
                        @csrf
                        @method('PATCH')
                        <label class="block">
                            <span class="text-sm font-medium">Dinheiro contado</span>
                            <input name="counted_cash" type="number" step="0.01" min="0" value="{{ old('counted_cash') }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
                        </label>
                        <label class="block">
                            <span class="text-sm font-medium">Observacao de fechamento</span>
                            <textarea name="closing_notes" rows="3" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">{{ old('closing_notes') }}</textarea>
                        </label>
                        <button class="rounded-md bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white">Fechar caixa</button>
                    </form>
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="font-semibold">Movimentacoes deste caixa</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[720px] text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Data</th>
                                <th class="px-5 py-3">Tipo</th>
                                <th class="px-5 py-3">Descricao</th>
                                <th class="px-5 py-3 text-right">Valor</th>
                                <th class="px-5 py-3">Usuario</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($openRegister->movements->sortByDesc('occurred_at') as $movement)
                                <tr>
                                    <td class="px-5 py-4 text-slate-600">{{ $movement->occurred_at?->format('d/m/Y H:i') }}</td>
                                    <td class="px-5 py-4 font-medium">{{ $movement->typeLabel() }}</td>
                                    <td class="px-5 py-4">{{ $movement->description }}</td>
                                    <td class="px-5 py-4 text-right font-semibold">R$ {{ number_format((float) $movement->amount, 2, ',', '.') }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ $movement->user?->name }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-8 text-center text-slate-500">Nenhuma movimentacao registrada.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-semibold">Historico de caixas</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Abertura</th>
                            <th class="px-5 py-3">Fechamento</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3 text-right">Inicial</th>
                            <th class="px-5 py-3 text-right">Esperado</th>
                            <th class="px-5 py-3 text-right">Contado</th>
                            <th class="px-5 py-3 text-right">Diferenca</th>
                            <th class="px-5 py-3">Aberto por</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($recentRegisters as $register)
                            <tr>
                                <td class="px-5 py-4">{{ $register->opened_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-5 py-4">{{ $register->closed_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                <td class="px-5 py-4">{{ $register->statusLabel() }}</td>
                                <td class="px-5 py-4 text-right">R$ {{ number_format((float) $register->opening_balance, 2, ',', '.') }}</td>
                                <td class="px-5 py-4 text-right">{{ $register->expected_cash !== null ? 'R$ '.number_format((float) $register->expected_cash, 2, ',', '.') : '-' }}</td>
                                <td class="px-5 py-4 text-right">{{ $register->counted_cash !== null ? 'R$ '.number_format((float) $register->counted_cash, 2, ',', '.') : '-' }}</td>
                                <td class="px-5 py-4 text-right font-semibold">{{ $register->cash_difference !== null ? 'R$ '.number_format((float) $register->cash_difference, 2, ',', '.') : '-' }}</td>
                                <td class="px-5 py-4">{{ $register->openedBy?->name }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-8 text-center text-slate-500">Nenhum caixa registrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-5 py-4">
                {{ $recentRegisters->links() }}
            </div>
        </section>
    </div>
</x-app.layout>
