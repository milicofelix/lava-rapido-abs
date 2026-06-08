<x-app.layout heading="Lavagem {{ $washOrder->code }}" title="Lavagem {{ $washOrder->code }} · Lava Rapido ABS">
    <div class="grid gap-5 xl:grid-cols-[1fr_360px]">
        <div class="space-y-5">
            <section class="rounded-lg border border-zinc-200 bg-white p-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-sm text-zinc-500">Cliente</p>
                        <h2 class="text-xl font-semibold">{{ $washOrder->customer->name }}</h2>
                        <p class="mt-1 text-sm text-zinc-600">{{ $washOrder->customer->phone }}</p>
                    </div>
                    @include('app.wash-orders._status-badge', ['status' => $washOrder->status, 'label' => $washOrder->statusLabel()])
                </div>

                <dl class="mt-5 grid gap-4 md:grid-cols-3">
                    <div>
                        <dt class="text-sm text-zinc-500">Veiculo</dt>
                        <dd class="font-medium">{{ $washOrder->vehicle->plate }} · {{ $washOrder->vehicle->brand }} {{ $washOrder->vehicle->model }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">Entrada</dt>
                        <dd class="font-medium">{{ $washOrder->entered_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">Previsao</dt>
                        <dd class="font-medium">{{ $washOrder->estimated_completion_at?->format('d/m/Y H:i') ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">Responsavel</dt>
                        <dd class="font-medium">{{ $washOrder->assignedUser?->name ?? 'Sem responsavel' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">Total</dt>
                        <dd class="font-medium">R$ {{ number_format((float) $washOrder->total_amount, 2, ',', '.') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">Conclusao</dt>
                        <dd class="font-medium">{{ $washOrder->completed_at?->format('d/m/Y H:i') ?? '-' }}</dd>
                    </div>
                </dl>

                @if ($washOrder->notes)
                    <p class="mt-5 rounded-md bg-zinc-100 px-4 py-3 text-sm text-zinc-700">{{ $washOrder->notes }}</p>
                @endif
            </section>

            <section class="rounded-lg border border-zinc-200 bg-white">
                <div class="border-b border-zinc-200 px-5 py-4">
                    <h2 class="font-semibold">Servicos selecionados</h2>
                </div>
                <div class="divide-y divide-zinc-100">
                    @foreach ($washOrder->services as $service)
                        <div class="flex items-center justify-between gap-4 px-5 py-4">
                            <div>
                                <p class="font-medium">{{ $service->pivot->service_name }}</p>
                                <p class="text-sm text-zinc-500">{{ $service->pivot->estimated_minutes }} min</p>
                            </div>
                            <p class="font-semibold">R$ {{ number_format((float) $service->pivot->price, 2, ',', '.') }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="rounded-lg border border-zinc-200 bg-white">
                <div class="border-b border-zinc-200 px-5 py-4">
                    <h2 class="font-semibold">Historico de status</h2>
                </div>
                <div class="divide-y divide-zinc-100">
                    @foreach ($washOrder->statusHistories->sortByDesc('created_at') as $history)
                        <div class="px-5 py-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <p class="font-medium">{{ $statuses[$history->to_status] ?? $history->to_status }}</p>
                                <p class="text-sm text-zinc-500">{{ $history->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                            <p class="mt-1 text-sm text-zinc-500">{{ $history->user?->name ?? 'Sistema' }}</p>
                            @if ($history->notes)
                                <p class="mt-2 text-sm text-zinc-700">{{ $history->notes }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        <aside class="h-fit rounded-lg border border-zinc-200 bg-white p-5">
            <h2 class="font-semibold">Atualizar status</h2>
            <form method="POST" action="{{ route('wash-orders.update-status', $washOrder) }}" class="mt-4 space-y-4">
                @csrf
                @method('PATCH')
                <label class="block">
                    <span class="text-sm font-medium">Novo status</span>
                    <select name="status" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2">
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($washOrder->status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="text-sm font-medium">Observacao</span>
                    <textarea name="notes" rows="3" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2"></textarea>
                    @error('notes') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                </label>
                <button class="w-full rounded-md bg-cyan-700 px-4 py-2.5 text-sm font-semibold text-white">Salvar status</button>
            </form>
            <a href="{{ route('wash-orders.index') }}" class="mt-3 block rounded-md border border-zinc-300 px-4 py-2.5 text-center text-sm font-semibold">Voltar</a>
        </aside>
    </div>
</x-app.layout>
