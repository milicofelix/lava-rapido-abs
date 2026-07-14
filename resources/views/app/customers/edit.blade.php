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
</x-app.layout>
