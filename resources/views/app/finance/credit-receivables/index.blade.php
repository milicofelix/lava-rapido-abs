<x-app.layout heading="Fiado / Contas a receber" title="Fiado · AutoFlow">
    <div class="space-y-5">
        ('app.components.errors')

        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-slate-500">Lavagens marcadas como fiado / pendente e recebimento posterior.</p>
            <div class="flex gap-2">
                <a href="{{ route('finance.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold">Financeiro</a>
                @if (\App\Models\AppSetting::isModuleEnabled('module_cash_register'))
                    <a href="{{ route('finance.cash-registers.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold">Caixa</a>
                @endif
            </div>
        </div>

        <section class="grid gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <p class="text-sm text-slate-500">Total pendente</p>
                <p class="mt-2 text-2xl font-semibold">R$ {{ number_format((float) $totalPending, 2, ',', '.') }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <p class="text-sm text-slate-500">Ordens pendentes</p>
                <p class="mt-2 text-2xl font-semibold">{{ $orders->total() }}</p>
            </div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">
                <p class="text-sm text-amber-700">Proximo passo</p>
                <p class="mt-2 font-semibold text-amber-950">Receber e baixar no financeiro</p>
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-semibold">Contas em aberto</h2>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($orders as $order)
                    <div class="grid gap-4 px-5 py-5 xl:grid-cols-[1fr_420px]">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('wash-orders.show', $order) }}" class="text-lg font-semibold text-blue-700 hover:text-blue-900">{{ $order->code }}</a>
                                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">{{ $order->paymentStatusLabel() }}</span>
                            </div>
                            <p class="mt-2 text-sm text-slate-600">{{ $order->customer->name }} · {{ $order->vehicle->plate }} · {{ $order->vehicle->brand }} {{ $order->vehicle->model }}</p>
                            <p class="mt-1 text-sm text-slate-500">Entrada: {{ $order->entered_at?->format('d/m/Y H:i') }}</p>
                            <p class="mt-3 text-2xl font-semibold">R$ {{ number_format((float) $order->total_amount, 2, ',', '.') }}</p>
                        </div>

                        <form method="POST" action="{{ route('finance.credit-receivables.receive', $order) }}" class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                            @csrf
                            @method('PATCH')
                            <div class="grid gap-3 md:grid-cols-2">
                                <label class="block">
                                    <span class="text-sm font-medium">Metodo</span>
                                    <select name="method" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
                                        @foreach ($methods as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="block">
                                    <span class="text-sm font-medium">Valor recebido</span>
                                    <input name="amount" type="number" step="0.01" min="0.01" value="{{ old('amount', $order->total_amount) }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
                                </label>
                            </div>
                            <label class="mt-3 block">
                                <span class="text-sm font-medium">Observacao</span>
                                <input name="notes" value="{{ old('notes') }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" placeholder="Ex: Recebido via Pix depois da retirada">
                            </label>
                            <button class="mt-4 rounded-md bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white">Marcar como recebido</button>
                        </form>
                    </div>
                @empty
                    <p class="px-5 py-10 text-center text-slate-500">Nenhum fiado em aberto.</p>
                @endforelse
            </div>
            <div class="border-t border-slate-200 px-5 py-4">
                {{ $orders->links() }}
            </div>
        </section>
    </div>
</x-app.layout>
