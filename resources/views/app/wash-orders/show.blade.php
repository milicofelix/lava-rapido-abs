<x-app.layout heading="Lavagem {{ $washOrder->code }}" title="Lavagem {{ $washOrder->code }} · AutoFlow">
    @php($canSeeWashFinancial = auth()->user()->canAccess(\App\Support\Access\AccessControl::REGISTER_PAYMENT) || auth()->user()->canAccess(\App\Support\Access\AccessControl::VIEW_FINANCE))
    @php($canSendWashNotifications = auth()->user()->canAccess(\App\Support\Access\AccessControl::SEND_WASH_NOTIFICATIONS))

    <div class="grid gap-5 xl:grid-cols-[1fr_380px]">
        <div class="space-y-5">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Ordem {{ $washOrder->code }}</p>
                        <h2 class="mt-1 truncate text-2xl font-black text-slate-950">{{ $washOrder->customer->name }}</h2>
                        <p class="mt-1 text-sm font-bold text-slate-500">{{ $washOrder->customer->phone }}</p>
                    </div>
                    @include('app.wash-orders._status-badge', ['status' => $washOrder->status, 'label' => $washOrder->statusLabel()])
                </div>

                <dl class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <dt class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Veiculo</dt>
                        <dd class="mt-1 font-black text-slate-950">{{ $washOrder->vehicle->plate }}</dd>
                        <dd class="mt-1 text-sm text-slate-500">{{ $washOrder->vehicle->brand }} {{ $washOrder->vehicle->model }}</dd>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <dt class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Entrada</dt>
                        <dd class="mt-1 font-black text-slate-950">{{ $washOrder->entered_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <dt class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Previsao</dt>
                        <dd class="mt-1 font-black text-slate-950">{{ $washOrder->estimated_completion_at?->format('d/m/Y H:i') ?? '-' }}</dd>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <dt class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Equipe</dt>
                        <dd class="mt-1 font-black text-slate-950">{{ $washOrder->teamMembers->isNotEmpty() ? $washOrder->teamMembers->pluck('name')->join(', ') : 'Sem equipe definida' }}</dd>
                    </div>
                    @if ($canSeeWashFinancial)
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <dt class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Total</dt>
                            <dd class="mt-1 font-black text-slate-950">R$ {{ number_format((float) $washOrder->total_amount, 2, ',', '.') }}</dd>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <dt class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Financeiro</dt>
                            <dd class="mt-1 font-black text-slate-950">{{ $washOrder->paymentStatusLabel() }}</dd>
                        </div>
                    @endif
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <dt class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Conclusao</dt>
                        <dd class="mt-1 font-black text-slate-950">{{ $washOrder->completed_at?->format('d/m/Y H:i') ?? '-' }}</dd>
                    </div>
                </dl>

                @if ($washOrder->notes)
                    <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                        {{ $washOrder->notes }}
                    </div>
                @endif
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Execucao</p>
                    <h2 class="mt-1 font-black text-slate-950">Servicos selecionados</h2>
                </div>
                <div class="divide-y divide-slate-100">
                    @foreach ($washOrder->services as $service)
                        <div class="flex items-center justify-between gap-4 px-5 py-4">
                            <div class="min-w-0">
                                <p class="truncate font-black text-slate-950">{{ $service->pivot->service_name }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $service->pivot->estimated_minutes }} min</p>
                            </div>
                            @if ($canSeeWashFinancial)
                                <p class="shrink-0 font-black text-slate-950">R$ {{ number_format((float) $service->pivot->price, 2, ',', '.') }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>

            @if ($canSeeWashFinancial)
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-700">Financeiro</p>
                        <h2 class="mt-1 font-black text-slate-950">Pagamentos</h2>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @forelse ($washOrder->payments->sortByDesc('paid_at') as $payment)
                            <div class="px-5 py-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="font-black text-slate-950">{{ $payment->methodLabel() }}</p>
                                        <p class="mt-1 text-sm text-slate-500">
                                            {{ $payment->paid_at?->format('d/m/Y H:i') ?? '-' }} · {{ $payment->user?->name ?? 'Sistema' }}
                                        </p>
                                    </div>
                                    <p class="font-black text-emerald-700">R$ {{ number_format((float) $payment->amount, 2, ',', '.') }}</p>
                                </div>
                                @if ($payment->notes)
                                    <p class="mt-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-700">{{ $payment->notes }}</p>
                                @endif
                            </div>
                        @empty
                            <p class="px-5 py-4 text-sm text-slate-500">Nenhum pagamento registrado.</p>
                        @endforelse
                    </div>
                </section>
            @endif

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Linha do tempo</p>
                    <h2 class="mt-1 font-black text-slate-950">Historico de status</h2>
                </div>
                <div class="divide-y divide-slate-100">
                    @foreach ($washOrder->statusHistories->sortByDesc('created_at') as $history)
                        <div class="px-5 py-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <p class="font-black text-slate-950">{{ $statuses[$history->to_status] ?? $history->to_status }}</p>
                                <p class="text-sm font-bold text-slate-500">{{ $history->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                            <p class="mt-1 text-sm text-slate-500">{{ $history->user?->name ?? 'Sistema' }}</p>
                            @if ($history->notes)
                                <p class="mt-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-700">{{ $history->notes }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        <aside class="h-fit space-y-5 xl:sticky xl:top-24">
            @if ($canUpdateStatus)
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Operacao</p>
                    <h2 class="mt-1 font-black text-slate-950">Atualizar status</h2>
                    <form method="POST" action="{{ route('wash-orders.update-status', $washOrder) }}" class="mt-4 space-y-4">
                        @csrf
                        @method('PATCH')
                        <label class="block">
                            <span class="text-sm font-bold text-slate-700">Novo status</span>
                            <select name="status" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                @foreach ($statusOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($washOrder->status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </label>
                        <label class="block">
                            <span class="text-sm font-bold text-slate-700">Observacao</span>
                            <textarea name="notes" rows="3" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"></textarea>
                            @error('notes') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </label>
                        <button class="w-full rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-800">Salvar status</button>
                    </form>
                </section>
            @else
                <section class="rounded-2xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-600">
                    Status restrito a responsaveis da equipe desta lavagem.
                </section>
            @endif

            @if ($canSeeWashFinancial)
                <section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-700">Recebimento</p>
                    <h2 class="mt-1 font-black text-emerald-950">Registrar pagamento</h2>
                    <form method="POST" action="{{ route('payments.store', $washOrder) }}" class="mt-4 space-y-3">
                        @csrf
                        <label class="block">
                            <span class="text-sm font-bold text-emerald-950">Metodo</span>
                            <select name="method" class="mt-1 w-full rounded-xl border border-emerald-200 bg-white px-3 py-2.5 text-sm shadow-sm">
                                @foreach ($paymentMethods as $value => $label)
                                    <option value="{{ $value }}" @selected(old('method', 'pix') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('method') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </label>
                        <label class="block">
                            <span class="text-sm font-bold text-emerald-950">Valor</span>
                            <input name="amount" type="number" min="0" step="0.01" value="{{ old('amount', $washOrder->total_amount) }}" class="mt-1 w-full rounded-xl border border-emerald-200 bg-white px-3 py-2.5 text-sm shadow-sm">
                            @error('amount') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </label>
                        <label class="block">
                            <span class="text-sm font-bold text-emerald-950">Observacao</span>
                            <textarea name="notes" rows="3" class="mt-1 w-full rounded-xl border border-emerald-200 bg-white px-3 py-2.5 text-sm shadow-sm">{{ old('notes') }}</textarea>
                            @error('notes') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </label>
                        <button class="w-full rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-emerald-800">Registrar pagamento</button>
                    </form>
                </section>
            @endif

            @if ($canSendWashNotifications)
                <section class="rounded-2xl border border-blue-200 bg-blue-50 p-5 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Cliente</p>
                    <h2 class="mt-1 font-black text-blue-950">Link do cliente</h2>
                    <p class="mt-1 text-xs font-semibold text-blue-800">Compartilhe este link para o cliente acompanhar a lavagem em tempo real.</p>
                    <a href="{{ $washOrder->trackingUrl() }}" target="_blank" class="mt-3 block break-all rounded-2xl bg-white px-3 py-2 text-sm font-bold text-blue-800">{{ $washOrder->trackingUrl() }}</a>
                    @if ($washOrder->customer->whatsappTrackingUrl($washOrder))
                        <a href="{{ $washOrder->customer->whatsappTrackingUrl($washOrder) }}" target="_blank" rel="noopener" class="mt-3 inline-flex rounded-xl bg-emerald-700 px-3 py-2 text-xs font-bold text-white shadow-sm hover:bg-emerald-800">Compartilhar via WhatsApp</a>
                    @endif

                    <div class="mt-5 border-t border-blue-200 pt-4">
                        <h3 class="font-black text-blue-950">Notificacao manual</h3>
                        <p class="mt-1 text-xs font-semibold text-blue-800">Prepare a mensagem e envie manualmente pelo WhatsApp. Nenhuma API paga e usada nesta fase.</p>
                    </div>

                    <form method="POST" action="{{ route('wash-orders.notifications.whatsapp-manual.store', $washOrder) }}" class="mt-4 space-y-3">
                        @csrf
                        <label class="block">
                            <span class="text-sm font-bold text-blue-950">Modelo de mensagem</span>
                            <select name="template_key" class="mt-1 w-full rounded-xl border border-blue-200 bg-white px-3 py-2.5 text-sm shadow-sm">
                                @foreach ($notificationTemplates as $value => $label)
                                    <option value="{{ $value }}" @selected(old('template_key') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('template_key') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </label>
                        <label class="block">
                            <span class="text-sm font-bold text-blue-950">Observacao opcional</span>
                            <textarea name="notes" rows="2" class="mt-1 w-full rounded-xl border border-blue-200 bg-white px-3 py-2.5 text-sm shadow-sm" placeholder="Ex.: Estamos finalizando a secagem.">{{ old('notes') }}</textarea>
                            @error('notes') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </label>
                        <button class="w-full rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-800">Preparar mensagem</button>
                    </form>

                    @php($lastNotification = $washOrder->customerNotifications->sortByDesc('created_at')->first())
                    @if ($lastNotification)
                        <div class="mt-4 rounded-2xl border border-blue-200 bg-white p-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-black text-slate-950">Ultima mensagem preparada</p>
                                    <p class="text-xs text-slate-500">{{ $lastNotification->templateLabel() }} · {{ $lastNotification->statusLabel() }}</p>
                                </div>
                                @if ($lastNotification->action_url)
                                    <a href="{{ $lastNotification->action_url }}" target="_blank" rel="noopener" class="shrink-0 rounded-xl bg-emerald-700 px-3 py-2 text-xs font-bold text-white">Abrir WhatsApp</a>
                                @endif
                            </div>
                            <textarea readonly rows="5" data-copy-message class="mt-3 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700">{{ $lastNotification->message }}</textarea>
                            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                <button type="button" data-copy-message-button class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700">Copiar mensagem</button>
                                <form method="POST" action="{{ route('wash-orders.notifications.mark-as-sent', [$washOrder, $lastNotification]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="w-full rounded-xl border border-emerald-300 px-3 py-2 text-xs font-bold text-emerald-800">Marcar como enviada</button>
                                </form>
                            </div>
                        </div>
                    @endif
                </section>
            @endif

            @if ($canSeeWashFinancial)
                <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-amber-700">Documento</p>
                    <h2 class="mt-1 font-black text-amber-950">Recibo</h2>
                    <p class="mt-1 text-xs font-semibold text-amber-800">Gere um comprovante simples da lavagem para imprimir ou salvar como PDF pelo navegador.</p>
                    <a href="{{ route('wash-orders.receipt', $washOrder) }}" target="_blank" rel="noopener" class="mt-3 inline-flex w-full justify-center rounded-xl bg-amber-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-amber-800">Imprimir recibo</a>
                </section>
            @endif

            <a href="{{ auth()->user()->canAccess(\App\Support\Access\AccessControl::CREATE_WASH_ORDER) ? route('wash-orders.index') : route('kanban') }}" class="block rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-center text-sm font-bold text-slate-700 shadow-sm hover:bg-slate-50">Voltar</a>
        </aside>
    </div>
</x-app.layout>
