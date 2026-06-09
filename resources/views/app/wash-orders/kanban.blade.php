<x-app.layout heading="Kanban operacional" title="Kanban · AutoFlow">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3" data-realtime-kanban>
        <div>
            <p class="text-sm text-zinc-500">Fluxo de atendimento das lavagens abertas e entregues recentemente.</p>
            @if (request()->boolean('realtime'))
                <p class="mt-1 text-xs font-medium text-cyan-700">Atualizado em tempo real.</p>
            @endif
        </div>
        <a href="{{ route('wash-orders.create') }}" class="rounded-md bg-cyan-700 px-4 py-2 text-sm font-semibold text-white">Nova lavagem</a>
    </div>

    <div class="grid gap-3 overflow-x-auto pb-4 xl:grid-cols-5">
        @foreach ($columns as $column)
            <section class="min-h-[520px] min-w-56 rounded-lg border border-zinc-200 bg-zinc-100" data-kanban-column data-target-status="{{ $column['target_status'] }}">
                <header class="sticky top-0 z-[1] flex items-center justify-between gap-3 border-b border-zinc-200 bg-zinc-100 px-3 py-2.5">
                    <h2 class="text-sm font-semibold">{{ $column['title'] }}</h2>
                    <span class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-zinc-600">{{ $column['orders']->count() }}</span>
                </header>

                <div class="space-y-2.5 p-2.5" data-kanban-dropzone>
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
                        @endphp

                        <article
                            class="rounded-md border border-zinc-200 bg-white p-3 shadow-sm transition hover:border-cyan-300"
                            draggable="true"
                            data-kanban-card
                            data-update-url="{{ route('wash-orders.update-status', $washOrder) }}"
                            data-current-status="{{ $washOrder->status }}"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <a href="{{ route('wash-orders.show', $washOrder) }}" class="font-semibold text-zinc-950">{{ $washOrder->vehicle->plate }}</a>
                                    <p class="mt-0.5 truncate text-xs text-zinc-500">{{ $washOrder->vehicle->brand }} {{ $washOrder->vehicle->model }}</p>
                                </div>
                                <span class="shrink-0 rounded-full bg-zinc-100 px-2 py-1 text-[11px] font-semibold text-zinc-600">{{ $washOrder->statusLabel() }}</span>
                            </div>

                            <dl class="mt-3 space-y-1.5 text-xs">
                                <div class="flex justify-between gap-2">
                                    <dt class="text-zinc-500">Cliente</dt>
                                    <dd class="max-w-28 truncate font-medium">{{ $washOrder->customer->name }}</dd>
                                </div>
                                <div class="flex justify-between gap-2">
                                    <dt class="text-zinc-500">Tempo</dt>
                                    <dd class="font-medium">{{ $washOrder->entered_at->diffForHumans(null, true) }}</dd>
                                </div>
                                <div class="flex justify-between gap-2">
                                    <dt class="text-zinc-500">Equipe</dt>
                                    <dd class="max-w-28 truncate font-medium">
                                        {{ $washOrder->teamMembers->isNotEmpty() ? $washOrder->teamMembers->pluck('name')->take(2)->join(', ') : 'Sem equipe' }}
                                        @if ($washOrder->teamMembers->count() > 2)
                                            +{{ $washOrder->teamMembers->count() - 2 }}
                                        @endif
                                    </dd>
                                </div>
                                <div class="flex justify-between gap-2">
                                    <dt class="text-zinc-500">Valor</dt>
                                    <dd class="font-semibold">R$ {{ number_format((float) $washOrder->total_amount, 2, ',', '.') }}</dd>
                                </div>
                            </dl>

                            <div class="mt-3 flex flex-wrap gap-1">
                                @foreach ($washOrder->services->take(2) as $service)
                                    <span class="max-w-full truncate rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] text-zinc-600">{{ $service->pivot->service_name }}</span>
                                @endforeach
                                @if ($washOrder->services->count() > 2)
                                    <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] text-zinc-600">+{{ $washOrder->services->count() - 2 }}</span>
                                @endif
                            </div>

                            <div class="mt-3 grid grid-cols-2 gap-1.5">
                                <a href="{{ route('wash-orders.show', $washOrder) }}" class="rounded-md border border-zinc-300 px-2 py-1.5 text-center text-xs font-semibold">Detalhes</a>
                                @if ($nextStatus)
                                    <form method="POST" action="{{ route('wash-orders.update-status', $washOrder) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="{{ $nextStatus }}">
                                        <input type="hidden" name="notes" value="Status atualizado pelo Kanban.">
                                        <button class="w-full rounded-md bg-zinc-950 px-2 py-1.5 text-xs font-semibold text-white">{{ $nextLabel }}</button>
                                    </form>
                                @else
                                    <span class="rounded-md bg-zinc-100 px-2 py-1.5 text-center text-xs font-semibold text-zinc-500">Concluido</span>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="rounded-md border border-dashed border-zinc-300 bg-white px-3 py-6 text-center text-sm text-zinc-500">
                            Sem lavagens nesta etapa.
                        </div>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>

    <script>
        const token = document.querySelector('meta[name="csrf-token"]').content;
        let draggedCard = null;

        document.querySelectorAll('[data-kanban-card]').forEach((card) => {
            card.addEventListener('dragstart', (event) => {
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
                column.classList.add('border-cyan-400');
            });

            column.addEventListener('dragleave', () => {
                column.classList.remove('border-cyan-400');
            });

            column.addEventListener('drop', async (event) => {
                event.preventDefault();
                column.classList.remove('border-cyan-400');

                if (!draggedCard) {
                    return;
                }

                const status = column.dataset.targetStatus;

                if (draggedCard.dataset.currentStatus === status) {
                    return;
                }

                await fetch(draggedCard.dataset.updateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'text/html',
                    },
                    body: JSON.stringify({
                        _method: 'PATCH',
                        status,
                        notes: 'Status atualizado por arrastar no Kanban.',
                    }),
                });

                window.location.reload();
            });
        });
    </script>
</x-app.layout>
