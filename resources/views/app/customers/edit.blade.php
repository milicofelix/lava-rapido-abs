<x-app.layout heading="Editar cliente" title="Editar cliente · AutoFlow">
    <div class="grid gap-5 xl:grid-cols-[1fr_360px]">
        <form method="POST" action="{{ route('customers.update', $customer) }}">
            @csrf
            @method('PUT')
            @include('app.customers._form')
        </form>

        <aside class="space-y-4">
            <section class="rounded-2xl border border-fuchsia-200 bg-fuchsia-50 p-5 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-fuchsia-700">Programa de fidelidade</p>
                <h2 class="mt-1 text-xl font-black text-fuchsia-950">Progresso do cliente</h2>

                @if ($loyaltyProgress['enabled'])
                    @if ($loyaltyProgress['has_active_coupon'])
                        <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                            <p class="text-sm font-black text-emerald-900">Cupom disponível</p>
                            <p class="mt-1 text-sm font-semibold text-emerald-700">Este cliente já possui {{ $loyaltyProgress['active_coupons'] }} cupom(ns) ativo(s). O contador abaixo mostra o andamento para o próximo benefício.</p>
                        </div>
                    @endif

                    <dl class="mt-4 grid gap-3">
                        <div class="rounded-2xl bg-white p-4">
                            <dt class="text-sm font-bold text-slate-500">Lavagens válidas</dt>
                            <dd class="mt-1 text-3xl font-black text-slate-950">{{ $loyaltyProgress['current'] }}/{{ $loyaltyProgress['threshold'] }}</dd>
                            <div class="mt-3 h-2 rounded-full bg-slate-100">
                                <div class="h-2 rounded-full bg-fuchsia-600" style="width: {{ $loyaltyProgress['percent'] }}%"></div>
                            </div>
                        </div>
                        <div class="rounded-2xl bg-white p-4">
                            <dt class="text-sm font-bold text-slate-500">Faltam para o próximo cupom</dt>
                            <dd class="mt-1 text-2xl font-black text-fuchsia-700">{{ $loyaltyProgress['remaining'] }}</dd>
                        </div>
                        <div class="rounded-2xl bg-white p-4">
                            <dt class="text-sm font-bold text-slate-500">Regra atual</dt>
                            <dd class="mt-1 font-black text-slate-950">{{ $loyaltyProgress['label'] }}</dd>
                        </div>
                    </dl>
                @else
                    <p class="mt-4 rounded-2xl bg-white px-4 py-3 text-sm font-semibold text-slate-600">Programa de fidelidade desabilitado para esta unidade.</p>
                @endif
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Cupons</p>
                        <h2 class="mt-1 font-black text-slate-950">Últimos cupons</h2>
                    </div>
                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-black text-blue-700">{{ $loyaltyCoupons->count() }}</span>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse ($loyaltyCoupons as $coupon)
                        <a href="{{ route('loyalty-coupons.show', $coupon) }}" class="block rounded-2xl border border-slate-200 bg-slate-50 p-3 transition hover:border-fuchsia-200 hover:bg-fuchsia-50">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate font-black tracking-wide text-slate-950">{{ $coupon->code }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $coupon->benefitLabel() }}</p>
                                    @if ($coupon->expires_at)
                                        <p class="mt-1 text-xs text-slate-500">Vence em {{ $coupon->expires_at->format('d/m/Y') }}</p>
                                    @endif
                                </div>
                                <span class="shrink-0 rounded-full bg-white px-3 py-1 text-xs font-black text-slate-700">{{ $coupon->statusLabel() }}</span>
                            </div>
                            <p class="mt-3 text-xs font-black text-fuchsia-700">Abrir cupom</p>
                        </a>
                    @empty
                        <p class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">Nenhum cupom emitido para este cliente.</p>
                    @endforelse
                </div>
            </section>
        </aside>
    </div>

    <section class="mt-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 pb-4">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Histórico consolidado</p>
                <h2 class="mt-1 text-xl font-black text-slate-950">Resumo do relacionamento</h2>
                <p class="mt-1 text-sm text-slate-500">Visão rápida de recorrência, consumo e últimos atendimentos deste cliente.</p>
            </div>
            @if ($customerInsights['favorite_service'])
                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-black text-blue-700">Serviço favorito: {{ $customerInsights['favorite_service'] }}</span>
            @endif
        </div>

        <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                <p class="text-sm font-bold text-slate-500">Lavagens totais</p>
                <p class="mt-2 text-3xl font-black text-slate-950">{{ $customerInsights['total_wash_orders'] }}</p>
                <p class="mt-1 text-xs font-semibold text-slate-500">{{ $customerInsights['delivered_wash_orders'] }} entregue{{ $customerInsights['delivered_wash_orders'] === 1 ? '' : 's' }}</p>
            </div>
            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                <p class="text-sm font-bold text-slate-500">Receita gerada</p>
                <p class="mt-2 text-3xl font-black text-emerald-700">R$ {{ number_format($customerInsights['total_revenue'], 2, ',', '.') }}</p>
                <p class="mt-1 text-xs font-semibold text-slate-500">Pagamentos registrados</p>
            </div>
            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                <p class="text-sm font-bold text-slate-500">Ticket médio</p>
                <p class="mt-2 text-3xl font-black text-blue-700">R$ {{ number_format($customerInsights['average_ticket'], 2, ',', '.') }}</p>
                <p class="mt-1 text-xs font-semibold text-slate-500">Por lavagem paga</p>
            </div>
            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                <p class="text-sm font-bold text-slate-500">Última lavagem</p>
                <p class="mt-2 text-2xl font-black text-slate-950">{{ $customerInsights['last_wash_order']?->entered_at?->format('d/m/Y') ?? '-' }}</p>
                <p class="mt-1 truncate text-xs font-semibold text-slate-500">{{ $customerInsights['last_wash_order']?->vehicle?->plate ?? 'Sem histórico' }}</p>
            </div>
            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                <p class="text-sm font-bold text-slate-500">Cupons ativos</p>
                <p class="mt-2 text-3xl font-black text-fuchsia-700">{{ $customerInsights['active_coupons_count'] }}</p>
                <p class="mt-1 text-xs font-semibold text-slate-500">Benefícios disponíveis</p>
            </div>
        </div>

        <div class="mt-5 grid gap-5 xl:grid-cols-[1fr_1fr]">
            <div class="rounded-2xl border border-slate-200 p-4">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="font-black text-slate-950">Veículos do cliente</h3>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">{{ $customerInsights['vehicles']->count() }}</span>
                </div>
                <div class="mt-4 space-y-3">
                    @forelse ($customerInsights['vehicles'] as $vehicle)
                        <div class="flex items-center justify-between gap-3 rounded-2xl bg-slate-50 p-3">
                            <div class="min-w-0">
                                <p class="truncate font-black text-slate-950">{{ $vehicle->plate }}</p>
                                <p class="mt-1 truncate text-sm text-slate-500">{{ trim(($vehicle->brand ?? '').' '.($vehicle->model ?? '')) ?: 'Modelo não informado' }}</p>
                            </div>
                            <span class="shrink-0 rounded-full bg-white px-3 py-1 text-xs font-black text-blue-700 ring-1 ring-blue-100">{{ $vehicle->wash_orders_count }} {{ $vehicle->wash_orders_count === 1 ? 'lavagem' : 'lavagens' }}</span>
                        </div>
                    @empty
                        <p class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">Nenhum veículo cadastrado.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 p-4">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="font-black text-slate-950">Serviços mais consumidos</h3>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">{{ $customerInsights['top_services']->count() }}</span>
                </div>
                <div class="mt-4 space-y-3">
                    @forelse ($customerInsights['top_services'] as $service)
                        <div class="rounded-2xl bg-slate-50 p-3">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-black text-slate-950">{{ $service->service_name }}</p>
                                <span class="rounded-full bg-white px-3 py-1 text-xs font-black text-slate-700">{{ $service->total }}x</span>
                            </div>
                            <p class="mt-1 text-sm font-semibold text-slate-500">R$ {{ number_format((float) $service->revenue, 2, ',', '.') }} em serviços</p>
                        </div>
                    @empty
                        <p class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">Nenhum serviço consumido ainda.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200">
            <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                <h3 class="font-black text-slate-950">Últimas lavagens</h3>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($customerInsights['recent_wash_orders'] as $washOrder)
                    <div class="grid gap-3 px-4 py-3 md:grid-cols-[1fr_160px_150px_140px_auto] md:items-center">
                        <div class="min-w-0">
                            <p class="truncate font-black text-slate-950">{{ $washOrder->code }}</p>
                            <p class="mt-1 truncate text-sm text-slate-500">{{ $washOrder->vehicle?->plate }} · {{ $washOrder->services->pluck('pivot.service_name')->filter()->implode(', ') ?: 'Serviço não informado' }}</p>
                        </div>
                        <p class="text-sm font-bold text-slate-600">{{ $washOrder->entered_at?->format('d/m/Y H:i') }}</p>
                        <span class="w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-700">{{ $washOrder->statusLabel() }}</span>
                        <span class="w-fit rounded-full {{ $washOrder->hasIdentifiedPayment() ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }} px-3 py-1 text-xs font-black">{{ $washOrder->paymentStatusLabel() }}</span>
                        <a href="{{ route('wash-orders.show', $washOrder) }}" class="rounded-xl border border-slate-200 px-3 py-2 text-center text-sm font-bold text-slate-700 hover:bg-slate-50">Abrir</a>
                    </div>
                @empty
                    <p class="px-4 py-8 text-center text-sm text-slate-500">Nenhuma lavagem registrada para este cliente.</p>
                @endforelse
            </div>
        </div>
    </section>
</x-app.layout>
