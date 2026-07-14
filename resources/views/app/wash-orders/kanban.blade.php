<x-app.layout heading="Kanban operacional" title="Kanban · AutoFlow">
    @php
        $canCreateWashOrder = auth()->user()->canAccess(\App\Support\Access\AccessControl::CREATE_WASH_ORDER);
        $canUpdateStatus = auth()->user()->canAccess(\App\Support\Access\AccessControl::UPDATE_WASH_ORDER_STATUS);
        $columnStyles = [
            \App\Models\WashOrder::STATUS_AWAITING => ['border' => 'border-slate-300', 'bar' => 'bg-slate-400', 'title' => 'text-slate-800', 'bg' => 'bg-slate-50'],
            \App\Models\WashOrder::STATUS_WASHING => ['border' => 'border-blue-300', 'bar' => 'bg-blue-600', 'title' => 'text-blue-700', 'bg' => 'bg-blue-50'],
            \App\Models\WashOrder::STATUS_FINISHING => ['border' => 'border-orange-300', 'bar' => 'bg-orange-500', 'title' => 'text-orange-700', 'bg' => 'bg-orange-50'],
            \App\Models\WashOrder::STATUS_READY => ['border' => 'border-emerald-300', 'bar' => 'bg-emerald-600', 'title' => 'text-emerald-700', 'bg' => 'bg-emerald-50'],
            \App\Models\WashOrder::STATUS_DELIVERED => ['border' => 'border-slate-700', 'bar' => 'bg-slate-800', 'title' => 'text-slate-900', 'bg' => 'bg-slate-50'],
        ];
    @endphp

    <div class="space-y-5" data-realtime-kanban>
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Fluxo de lavagens</p>
                    <h2 class="mt-1 text-2xl font-black text-slate-950">Kanban operacional</h2>
                    <p class="mt-1 text-sm text-slate-500">Acompanhe a operação por etapa e avance status sem sair do quadro.</p>
                    @if (request()->boolean('realtime'))
                        <p class="mt-2 inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">Atualizado em tempo real.</p>
                    @endif
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('wash-orders.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Lista</a>
                    @if ($canCreateWashOrder)
                        <a href="{{ route('wash-orders.create') }}" class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-800">Nova lavagem</a>
                    @endif
                </div>
            </div>
        </section>

        <div class="grid gap-4 overflow-x-auto pb-4 xl:grid-cols-5">
            @foreach ($columns as $column)
                @php($style = $columnStyles[$column['target_status']] ?? $columnStyles[\App\Models\WashOrder::STATUS_AWAITING])
                <section class="relative min-h-[560px] min-w-[17rem] overflow-hidden rounded-2xl border {{ $style['border'] }} {{ $style['bg'] }} shadow-sm transition" data-kanban-column data-target-status="{{ $column['target_status'] }}">
                    <div class="h-1.5 {{ $style['bar'] }}"></div>
                    <header class="sticky top-0 z-[1] flex items-center justify-between gap-3 border-b border-white/70 bg-white/80 px-4 py-3 backdrop-blur">
                        <h2 class="text-base font-black {{ $style['title'] }}">{{ $column['title'] }}</h2>
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-black text-slate-700 shadow-sm">{{ $column['orders']->count() }}</span>
                    </header>

                    <div class="space-y-3 p-3" data-kanban-dropzone>
                        @forelse ($column['orders'] as $washOrder)
                            @php
                                $nextStatus = match ($column['target_status']) {
                                    \App\Models\WashOrder::STATUS_AWAITING => \App\Models\WashOrder::STATUS_WASHING,
                                    \App\Models\WashOrder::STATUS_WASHING => \App\Models\WashOrder::STATUS_FINISHING,
                                    \App\Models\WashOrder::STATUS_FINISHING => \App\Models\WashOrder::STATUS_READY,
                                    \App\Models\WashOrder::STATUS_READY => \App\Models\WashOrder::STATUS_DELIVERED,
                                    default => null,
                                };
                                $nextLabel = $nextStatus ? (\App\Models\WashOrder::statuses()[$nextStatus] ?? 'Avancar') : null;
                                $operatorCanTouchOrder = ! auth()->user()->isOperator() || $washOrder->teamMembers->contains('id', auth()->id());
                                $canTouchOrder = $canUpdateStatus && $operatorCanTouchOrder;
                                $deliveryBlockedByPayment = $nextStatus === \App\Models\WashOrder::STATUS_DELIVERED && ! $washOrder->hasIdentifiedPayment();
                                $cardDraggable = $canTouchOrder ? 'true' : 'false';
                            @endphp

                            <article
                                class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm transition hover:border-blue-200 hover:shadow-md"
                                draggable="{{ $cardDraggable }}"
                                data-kanban-card
                                data-can-update="{{ $canTouchOrder ? '1' : '0' }}"
                                data-update-url="{{ route('wash-orders.update-status', $washOrder) }}"
                                data-current-status="{{ $washOrder->status }}"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <a href="{{ route('wash-orders.show', $washOrder) }}" class="truncate text-lg font-black tracking-wide text-slate-950">{{ $washOrder->vehicle->plate }}</a>
                                        <p class="mt-0.5 truncate text-xs font-bold text-slate-500">{{ $washOrder->vehicle->brand }} {{ $washOrder->vehicle->model }}</p>
                                    </div>
                                    <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-black text-slate-600">{{ $washOrder->statusLabel() }}</span>
                                </div>

                                <div class="mt-3 space-y-1.5 text-xs">
                                    <div class="flex justify-between gap-2">
                                        <span class="text-slate-500">Cliente</span>
                                        <span class="max-w-32 truncate font-bold text-slate-800">{{ $washOrder->customer->name }}</span>
                                    </div>
                                    <div class="flex justify-between gap-2">
                                        <span class="text-slate-500">Entrada</span>
                                        <span class="font-bold text-slate-800">{{ $washOrder->entered_at->format('H:i') }} · {{ $washOrder->entered_at->diffForHumans(null, true) }}</span>
                                    </div>
                                    <div class="flex justify-between gap-2">
                                        <span class="text-slate-500">Equipe</span>
                                        <span class="max-w-32 truncate font-bold text-slate-800">
                                            {{ $washOrder->teamMembers->isNotEmpty() ? $washOrder->teamMembers->pluck('name')->take(2)->join(', ') : 'Sem equipe' }}
                                            @if ($washOrder->teamMembers->count() > 2)
                                                +{{ $washOrder->teamMembers->count() - 2 }}
                                            @endif
                                        </span>
                                    </div>
                                    <div class="flex justify-between gap-2">
                                        <span class="text-slate-500">Valor</span>
                                        <span class="font-black text-slate-950">R$ {{ number_format((float) $washOrder->total_amount, 2, ',', '.') }}</span>
                                    </div>
                                </div>

                                <div class="mt-3 flex flex-wrap gap-1">
                                    @foreach ($washOrder->services->take(2) as $service)
                                        <span class="max-w-full truncate rounded-full bg-blue-50 px-2 py-0.5 text-[11px] font-bold text-blue-700">{{ $service->pivot->service_name }}</span>
                                    @endforeach
                                    @if ($washOrder->services->count() > 2)
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600">+{{ $washOrder->services->count() - 2 }}</span>
                                    @endif
                                </div>

                                <div class="mt-3 grid grid-cols-2 gap-2">
                                    <a href="{{ route('wash-orders.show', $washOrder) }}" class="rounded-xl border border-slate-200 px-2 py-2 text-center text-xs font-bold text-slate-700 hover:bg-slate-50">Detalhes</a>
                                    @if ($nextStatus && $canTouchOrder && ! $deliveryBlockedByPayment)
                                        <form method="POST" action="{{ route('wash-orders.update-status', $washOrder) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="{{ $nextStatus }}">
                                            <input type="hidden" name="notes" value="Status atualizado pelo Kanban.">
                                            <button class="w-full rounded-xl bg-slate-950 px-2 py-2 text-xs font-bold text-white hover:bg-slate-800">{{ $nextLabel }}</button>
                                        </form>
                                    @elseif ($deliveryBlockedByPayment)
                                        <span class="rounded-xl bg-amber-50 px-2 py-2 text-center text-xs font-black text-amber-700">Pagamento pendente</span>
                                    @elseif (! $canTouchOrder)
                                        <span class="rounded-xl bg-slate-100 px-2 py-2 text-center text-xs font-black text-slate-500">Restrito</span>
                                    @else
                                        <span class="rounded-xl bg-slate-100 px-2 py-2 text-center text-xs font-black text-slate-500">Concluido</span>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <div class="rounded-2xl border border-dashed border-slate-300 bg-white/70 px-3 py-10 text-center text-sm font-bold text-slate-500">
                                Sem lavagens.
                            </div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    </div>

    <script>
        const token = document.querySelector('meta[name="csrf-token"]').content;
        let draggedCard = null;

        document.querySelectorAll('[data-kanban-card]').forEach((card) => {
            card.addEventListener('dragstart', (event) => {
                if (card.dataset.canUpdate !== '1') {
                    event.preventDefault();
                    return;
                }

                draggedCard = card;
                event.dataTransfer.effectAllowed = 'move';
                card.classList.add('opacity-60');
            });

            card.addEventListener('dragend', () => {
                card.classList.remove('opacity-60');
                draggedCard = null;
            });
        });

        document.querySelectorAll('[data-kanban-column]').forEach((column) => {
            column.addEventListener('dragover', (event) => {
                event.preventDefault();
                column.classList.add('ring-2', 'ring-blue-300');
            });

            column.addEventListener('dragleave', () => {
                column.classList.remove('ring-2', 'ring-blue-300');
            });

            column.addEventListener('drop', async (event) => {
                event.preventDefault();
                column.classList.remove('ring-2', 'ring-blue-300');

                if (!draggedCard || draggedCard.dataset.canUpdate !== '1') {
                    return;
                }

                const status = column.dataset.targetStatus;

                if (draggedCard.dataset.currentStatus === status) {
                    return;
                }

                const response = await fetch(draggedCard.dataset.updateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        _method: 'PATCH',
                        status,
                        notes: 'Status atualizado por arrastar no Kanban.',
                    }),
                });

                if (! response.ok) {
                    window.location.reload();
                    return;
                }

                window.location.reload();
            });
        });
    </script>
</x-app.layout>
