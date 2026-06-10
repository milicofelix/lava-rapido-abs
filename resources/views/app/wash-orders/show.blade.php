<x-app.layout heading="Lavagem {{ $washOrder->code }}" title="Lavagem {{ $washOrder->code }} · AutoFlow">
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
                        <dt class="text-sm text-zinc-500">Equipe</dt>
                        <dd class="font-medium">
                            {{ $washOrder->teamMembers->isNotEmpty() ? $washOrder->teamMembers->pluck('name')->join(', ') : 'Sem equipe definida' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">Total</dt>
                        <dd class="font-medium">R$ {{ number_format((float) $washOrder->total_amount, 2, ',', '.') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">Financeiro</dt>
                        <dd class="font-medium">{{ $washOrder->paymentStatusLabel() }}</dd>
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
                    <h2 class="font-semibold">Pagamentos</h2>
                </div>
                <div class="divide-y divide-zinc-100">
                    @forelse ($washOrder->payments->sortByDesc('paid_at') as $payment)
                        <div class="px-5 py-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="font-medium">{{ $payment->methodLabel() }}</p>
                                    <p class="text-sm text-zinc-500">
                                        {{ $payment->paid_at?->format('d/m/Y H:i') ?? '-' }} · {{ $payment->user?->name ?? 'Sistema' }}
                                    </p>
                                </div>
                                <p class="font-semibold">R$ {{ number_format((float) $payment->amount, 2, ',', '.') }}</p>
                            </div>
                            @if ($payment->notes)
                                <p class="mt-2 text-sm text-zinc-700">{{ $payment->notes }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="px-5 py-4 text-sm text-zinc-500">Nenhum pagamento registrado.</p>
                    @endforelse
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
            <section class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 p-4">
                <h2 class="font-semibold text-emerald-950">Registrar pagamento</h2>
                <form method="POST" action="{{ route('payments.store', $washOrder) }}" class="mt-4 space-y-3">
                    @csrf
                    <label class="block">
                        <span class="text-sm font-medium">Metodo</span>
                        <select name="method" class="mt-1 w-full rounded-md border border-emerald-200 bg-white px-3 py-2">
                            @foreach ($paymentMethods as $value => $label)
                                <option value="{{ $value }}" @selected(old('method', 'pix') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('method') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium">Valor</span>
                        <input name="amount" type="number" min="0" step="0.01" value="{{ old('amount', $washOrder->total_amount) }}" class="mt-1 w-full rounded-md border border-emerald-200 bg-white px-3 py-2">
                        @error('amount') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium">Observacao</span>
                        <textarea name="notes" rows="3" class="mt-1 w-full rounded-md border border-emerald-200 bg-white px-3 py-2">{{ old('notes') }}</textarea>
                        @error('notes') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <button class="w-full rounded-md bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white">Registrar pagamento</button>
                </form>
            </section>

            <section class="mb-5 rounded-md border border-cyan-200 bg-cyan-50 p-4">
                <h2 class="font-semibold text-cyan-950">Link do cliente</h2>
                <p class="mt-1 text-xs text-cyan-800">Compartilhe este link para o cliente acompanhar a lavagem em tempo real.</p>
                <a href="{{ $washOrder->trackingUrl() }}" target="_blank" class="mt-3 block break-all text-sm font-medium text-cyan-800">{{ $washOrder->trackingUrl() }}</a>
                @if ($washOrder->customer->whatsappTrackingUrl($washOrder))
                    <a href="{{ $washOrder->customer->whatsappTrackingUrl($washOrder) }}" target="_blank" rel="noopener" class="mt-3 inline-flex rounded-md bg-emerald-700 px-3 py-2 text-xs font-semibold text-white">Compartilhar via WhatsApp</a>
                @endif

                <div class="mt-5 border-t border-cyan-200 pt-4">
                    <h3 class="font-semibold text-cyan-950">Notificacao manual</h3>
                    <p class="mt-1 text-xs text-cyan-800">Prepare a mensagem e envie manualmente pelo WhatsApp. Nenhuma API paga e usada nesta fase.</p>
                </div>

                <form method="POST" action="{{ route('wash-orders.notifications.whatsapp-manual.store', $washOrder) }}" class="mt-4 space-y-3">
                    @csrf
                    <label class="block">
                        <span class="text-sm font-medium">Modelo de mensagem</span>
                        <select name="template_key" class="mt-1 w-full rounded-md border border-cyan-200 bg-white px-3 py-2">
                            @foreach ($notificationTemplates as $value => $label)
                                <option value="{{ $value }}" @selected(old('template_key') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('template_key') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium">Observacao opcional</span>
                        <textarea name="notes" rows="2" class="mt-1 w-full rounded-md border border-cyan-200 bg-white px-3 py-2" placeholder="Ex.: Estamos finalizando a secagem.">{{ old('notes') }}</textarea>
                        @error('notes') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <button class="w-full rounded-md bg-cyan-700 px-4 py-2.5 text-sm font-semibold text-white">Preparar mensagem</button>
                </form>

                @php($lastNotification = $washOrder->customerNotifications->sortByDesc('created_at')->first())
                @if ($lastNotification)
                    <div class="mt-4 rounded-md border border-cyan-200 bg-white p-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-950">Ultima mensagem preparada</p>
                                <p class="text-xs text-slate-500">{{ $lastNotification->templateLabel() }} · {{ $lastNotification->statusLabel() }}</p>
                            </div>
                            @if ($lastNotification->action_url)
                                <a href="{{ $lastNotification->action_url }}" target="_blank" rel="noopener" class="shrink-0 rounded-md bg-emerald-700 px-3 py-2 text-xs font-semibold text-white">Abrir WhatsApp</a>
                            @endif
                        </div>
                        <textarea readonly rows="5" data-copy-message class="mt-3 w-full rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700">{{ $lastNotification->message }}</textarea>
                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                            <button type="button" data-copy-message-button class="rounded-md border border-slate-300 px-3 py-2 text-xs font-semibold">Copiar mensagem</button>
                            <form method="POST" action="{{ route('wash-orders.notifications.mark-as-sent', [$washOrder, $lastNotification]) }}">
                                @csrf
                                @method('PATCH')
                                <button class="w-full rounded-md border border-emerald-300 px-3 py-2 text-xs font-semibold text-emerald-800">Marcar como enviada</button>
                            </form>
                        </div>
                    </div>
                @endif
            </section>

            <section class="mb-5 rounded-md border border-amber-200 bg-amber-50 p-4">
                <h2 class="font-semibold text-amber-950">Recibo</h2>
                <p class="mt-1 text-xs text-amber-800">Gere um comprovante simples da lavagem para imprimir ou salvar como PDF pelo navegador.</p>
                <a href="{{ route('wash-orders.receipt', $washOrder) }}" target="_blank" rel="noopener" class="mt-3 inline-flex w-full justify-center rounded-md bg-amber-700 px-4 py-2.5 text-sm font-semibold text-white">Imprimir recibo</a>
            </section>

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
