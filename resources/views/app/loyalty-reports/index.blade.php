<x-app.layout heading="Fidelidade" title="Fidelidade · AutoFlow">
    <div class="space-y-5">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-fuchsia-700">Programa de fidelidade</p>
                    <h2 class="mt-1 text-2xl font-black text-slate-950">Relatorio gerencial</h2>
                    <p class="mt-1 text-sm text-slate-500">Acompanhe cupons gerados, uso, descontos concedidos e clientes perto de ganhar beneficio.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('loyalty-campaigns.index') }}" class="rounded-xl bg-fuchsia-50 px-4 py-2.5 text-sm font-bold text-fuchsia-700 ring-1 ring-fuchsia-200 hover:bg-fuchsia-100">Campanhas</a>
                    @if ($loyaltyProgram)
                        <form method="POST" action="{{ route('loyalty-reports.process-coupons') }}">
                            @csrf
                            <button class="rounded-xl bg-fuchsia-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-fuchsia-800">Processar cupons</button>
                        </form>
                    @endif
                    <a href="{{ route('loyalty-reports.export', request()->query()) }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm hover:bg-slate-50">Exportar CSV</a>
                    @if ($loyaltyProgram)
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700 ring-1 ring-emerald-200">Programa ativo</span>
                    @else
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600 ring-1 ring-slate-200">Programa desativado</span>
                    @endif
                </div>
            </div>

            @if (session('status'))
                <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            @error('loyalty_program')
                <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">
                    {{ $message }}
                </div>
            @enderror

            <form method="GET" class="mt-5 grid gap-3 lg:grid-cols-[160px_160px_1fr_1fr_190px_auto]">
                <label class="block">
                    <span class="mb-1 block text-xs font-bold text-slate-500">Início</span>
                    <input type="date" name="start" value="{{ $filters['start'] }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    @error('start') <span class="mt-1 block text-xs font-bold text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-bold text-slate-500">Fim</span>
                    <input type="date" name="end" value="{{ $filters['end'] }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    @error('end') <span class="mt-1 block text-xs font-bold text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-bold text-slate-500">Cliente</span>
                    <select name="customer_id" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <option value="">Todos os clientes</option>
                        @foreach ($customersForFilter as $customer)
                            <option value="{{ $customer->id }}" @selected($filters['customer_id'] === $customer->id)>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                    @error('customer_id') <span class="mt-1 block text-xs font-bold text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-bold text-slate-500">Busca</span>
                    <input name="search" value="{{ $filters['search'] }}" placeholder="Código, nome, telefone ou CPF" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    @error('search') <span class="mt-1 block text-xs font-bold text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-bold text-slate-500">Status</span>
                    <select name="status" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <option value="">Todos</option>
                        @foreach ($statuses as $status => $label)
                            <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status') <span class="mt-1 block text-xs font-bold text-red-600">{{ $message }}</span> @enderror
                </label>
                <div class="flex items-end gap-2">
                    <button class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-800">Filtrar</button>
                    <a href="{{ route('loyalty-reports.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50">Limpar</a>
                </div>
            </form>
        </section>

        <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Cupons ativos</p>
                <p class="mt-2 text-3xl font-black text-emerald-700">{{ $metrics['active_coupons'] }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Usados no periodo</p>
                <p class="mt-2 text-3xl font-black text-blue-700">{{ $metrics['used_coupons'] }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Expirados no periodo</p>
                <p class="mt-2 text-3xl font-black text-amber-700">{{ $metrics['expired_coupons'] }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Descontos concedidos</p>
                <p class="mt-2 text-3xl font-black text-fuchsia-700">R$ {{ number_format($metrics['discount_granted'], 2, ',', '.') }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Retenção e recorrência</p>
                    <h2 class="mt-1 text-xl font-black text-slate-950">Clientes que voltam para lavar</h2>
                    <p class="mt-1 text-sm text-slate-500">Indicadores calculados pelas lavagens entregues no período filtrado.</p>
                </div>
                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-black text-blue-700 ring-1 ring-blue-100">{{ $retentionReport['orders_count'] }} lavagem{{ $retentionReport['orders_count'] === 1 ? '' : 's' }}</span>
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-100">
                    <p class="text-sm font-bold text-slate-500">Clientes atendidos</p>
                    <p class="mt-2 text-3xl font-black text-slate-950">{{ $retentionReport['served_customers'] }}</p>
                </div>
                <div class="rounded-2xl bg-emerald-50 p-4 ring-1 ring-emerald-100">
                    <p class="text-sm font-bold text-emerald-700">Clientes recorrentes</p>
                    <p class="mt-2 text-3xl font-black text-emerald-800">{{ $retentionReport['returning_customers'] }}</p>
                </div>
                <div class="rounded-2xl bg-blue-50 p-4 ring-1 ring-blue-100">
                    <p class="text-sm font-bold text-blue-700">Taxa de recorrência</p>
                    <p class="mt-2 text-3xl font-black text-blue-800">{{ number_format($retentionReport['recurrence_rate'], 1, ',', '.') }}%</p>
                </div>
                <div class="rounded-2xl bg-fuchsia-50 p-4 ring-1 ring-fuchsia-100">
                    <p class="text-sm font-bold text-fuchsia-700">Média por cliente</p>
                    <p class="mt-2 text-3xl font-black text-fuchsia-800">{{ number_format($retentionReport['average_orders_per_customer'], 1, ',', '.') }}</p>
                </div>
            </div>

            <div class="mt-5 grid gap-5 xl:grid-cols-[0.8fr_1.2fr]">
                <div class="rounded-2xl border border-slate-100 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="font-black text-slate-950">Frequência no período</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ $retentionReport['retained_from_before'] }} cliente{{ $retentionReport['retained_from_before'] === 1 ? '' : 's' }} já tinha{{ $retentionReport['retained_from_before'] === 1 ? '' : 'm' }} histórico anterior.</p>
                        </div>
                    </div>
                    <div class="mt-4 space-y-3">
                        @foreach ($retentionReport['frequency_buckets'] as $bucket)
                            <div>
                                <div class="mb-1 flex items-center justify-between gap-3 text-sm">
                                    <span class="font-bold text-slate-700">{{ $bucket['label'] }}</span>
                                    <span class="font-black text-slate-950">{{ $bucket['count'] }} cliente{{ $bucket['count'] === 1 ? '' : 's' }}</span>
                                </div>
                                <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-3 rounded-full bg-blue-700" style="width: {{ $bucket['percent'] > 0 ? max(4, $bucket['percent']) : 0 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-100 p-4">
                    <h3 class="font-black text-slate-950">Evolução mensal</h3>
                    <div class="mt-4 space-y-3">
                        @forelse ($retentionReport['monthly_series'] as $month)
                            <div class="grid gap-2 sm:grid-cols-[74px_1fr_auto] sm:items-center">
                                <span class="text-sm font-black text-slate-700">{{ $month['label'] }}</span>
                                <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-3 rounded-full bg-emerald-600" style="width: {{ $month['percent'] > 0 ? max(4, $month['percent']) : 0 }}%"></div>
                                </div>
                                <span class="text-sm font-bold text-slate-600">{{ $month['orders'] }} lav. · {{ $month['customers'] }} cli. · {{ number_format($month['recurrence_rate'], 1, ',', '.') }}%</span>
                            </div>
                        @empty
                            <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm font-semibold text-slate-500">Nenhuma lavagem entregue no período.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-black text-slate-950">Clientes próximos de ganhar cupom</h2>
                    <p class="mt-1 text-sm text-slate-500">Priorize o relacionamento com quem falta pouco para receber um beneficio.</p>
                </div>
                <span class="rounded-full bg-fuchsia-50 px-3 py-1 text-xs font-black text-fuchsia-700">{{ $nearRewardCustomers->count() }} cliente{{ $nearRewardCustomers->count() === 1 ? '' : 's' }}</span>
            </div>

            <div class="mt-4 grid gap-3 lg:grid-cols-2 xl:grid-cols-4">
                @forelse ($nearRewardCustomers as $customer)
                    @php($progress = $customer->loyalty_progress)
                    <a href="{{ route('customers.edit', $customer) }}" class="rounded-2xl border border-fuchsia-100 bg-fuchsia-50/60 p-4 transition hover:border-fuchsia-300 hover:bg-fuchsia-50">
                        <p class="truncate font-black text-slate-950">{{ $customer->name }}</p>
                        <p class="mt-1 truncate text-sm font-semibold text-slate-600">{{ $customer->phone }}</p>
                        <div class="mt-3 flex items-center justify-between text-xs font-black text-slate-600">
                            <span>{{ $progress['current'] }}/{{ $progress['threshold'] }}</span>
                            <span>Faltam {{ $progress['remaining'] }}</span>
                        </div>
                        <div class="mt-2 h-2 rounded-full bg-white">
                            <div class="h-2 rounded-full bg-fuchsia-600" style="width: {{ $progress['percent'] }}%"></div>
                        </div>
                        <p class="mt-2 text-xs font-bold text-slate-500">{{ $progress['label'] }}</p>
                    </a>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm font-semibold text-slate-500 lg:col-span-2 xl:col-span-4">
                        Nenhum cliente perto do beneficio neste momento.
                    </div>
                @endforelse
            </div>
        </section>

        <div class="grid gap-5 xl:grid-cols-[0.9fr_1.1fr]">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="font-black text-slate-950">Progresso dos clientes</h2>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($customerProgress as $customer)
                        @php($progress = $customer->loyalty_progress)
                        <article class="px-5 py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate font-black text-slate-950">{{ $customer->name }}</p>
                                    <p class="mt-1 text-xs font-semibold text-slate-500">{{ $customer->wash_orders_count }} lavagem{{ $customer->wash_orders_count === 1 ? '' : 's' }} cadastrada{{ $customer->wash_orders_count === 1 ? '' : 's' }}</p>
                                </div>
                                @if ($progress['has_active_coupon'])
                                    <span class="shrink-0 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-black text-emerald-700">Cupom ativo</span>
                                @endif
                            </div>
                            @if ($progress['enabled'])
                                <div class="mt-3 flex items-center justify-between text-xs font-black text-slate-600">
                                    <span>{{ $progress['current'] }}/{{ $progress['threshold'] }} lavagens validas</span>
                                    <span>{{ $progress['remaining'] === 0 ? 'Beneficio alcançado' : 'Faltam '.$progress['remaining'] }}</span>
                                </div>
                                <div class="mt-2 h-2 rounded-full bg-slate-100">
                                    <div class="h-2 rounded-full bg-blue-700" style="width: {{ $progress['percent'] }}%"></div>
                                </div>
                            @else
                                <p class="mt-3 rounded-xl bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-500">Programa desativado para esta unidade.</p>
                            @endif
                        </article>
                    @empty
                        <p class="px-5 py-8 text-center text-sm text-slate-500">Nenhum cliente encontrado.</p>
                    @endforelse
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="font-black text-slate-950">Cupons do periodo</h2>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($coupons as $coupon)
                        @php($statusTone = match ($coupon->effectiveStatus()) {
                            \App\Models\LoyaltyCoupon::STATUS_ACTIVE => 'bg-emerald-50 text-emerald-700',
                            \App\Models\LoyaltyCoupon::STATUS_USED => 'bg-blue-50 text-blue-700',
                            \App\Models\LoyaltyCoupon::STATUS_EXPIRED => 'bg-amber-50 text-amber-700',
                            default => 'bg-slate-100 text-slate-600',
                        })
                        <article class="grid gap-3 px-5 py-4 md:grid-cols-[1fr_150px_120px_auto] md:items-center">
                            <div class="min-w-0">
                                <a href="{{ route('loyalty-coupons.show', $coupon) }}" class="font-black text-slate-950 hover:text-blue-700">{{ $coupon->code }}</a>
                                <p class="mt-1 truncate text-sm font-semibold text-slate-600">{{ $coupon->customer?->name ?? 'Cliente não informado' }}</p>
                                <p class="mt-1 truncate text-xs text-slate-500">{{ $coupon->benefitLabel() }}</p>
                            </div>
                            <div class="text-sm text-slate-600">
                                <p class="font-bold text-slate-900">{{ $coupon->earned_at?->format('d/m/Y') ?? '-' }}</p>
                                <p class="text-xs">gerado</p>
                            </div>
                            <div>
                                <span class="rounded-full px-3 py-1 text-xs font-black {{ $statusTone }}">{{ $coupon->statusLabel() }}</span>
                            </div>
                            <div class="flex justify-start md:justify-end">
                                <a href="{{ route('loyalty-coupons.show', $coupon) }}" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Abrir</a>
                            </div>
                        </article>
                    @empty
                        <p class="px-5 py-8 text-center text-sm text-slate-500">Nenhum cupom encontrado para os filtros informados.</p>
                    @endforelse
                </div>
                @if ($coupons->hasPages())
                    <div class="border-t border-slate-100 px-5 py-4">{{ $coupons->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-app.layout>
