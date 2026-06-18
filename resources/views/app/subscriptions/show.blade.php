<x-app.layout heading="Assinatura" title="Assinatura · AutoFlow">
    <div class="space-y-5">
        @if ($paymentReturn)
            @php
                $returnClasses = [
                    'success' => 'border-emerald-200 bg-emerald-50 text-emerald-950',
                    'warning' => 'border-amber-200 bg-amber-50 text-amber-950',
                    'danger' => 'border-rose-200 bg-rose-50 text-rose-950',
                ][$paymentReturn['type']] ?? 'border-slate-200 bg-slate-50 text-slate-950';
            @endphp
            <section class="rounded-2xl border p-5 text-sm {{ $returnClasses }}">
                <p class="font-black">{{ $paymentReturn['title'] }}</p>
                <p class="mt-1">{{ $paymentReturn['message'] }}</p>
            </section>
        @endif

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Plano atual</p>
                <p class="mt-2 text-2xl font-black text-slate-950">{{ $activeSubscription?->plan?->name ?? ($currentSubscription?->plan?->name ?? 'Nenhum') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Status</p>
                <p class="mt-2 text-2xl font-black text-slate-950">{{ $location->accountStatusLabel() }}</p>
                @if ($currentSubscription)
                    <p class="mt-1 text-xs font-bold text-slate-500">{{ $currentSubscription->statusLabel() }}</p>
                @endif
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Próxima cobrança</p>
                <p class="mt-2 text-2xl font-black text-slate-950">{{ $activeSubscription?->ends_at?->format('d/m/Y') ?? $location->subscription_ends_at?->format('d/m/Y') ?? '-' }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Dias restantes</p>
                <p class="mt-2 text-2xl font-black text-slate-950">{{ $location->trialDaysRemaining() ?? ($location->subscription_ends_at ? max(0, (int) now()->startOfDay()->diffInDays($location->subscription_ends_at->copy()->startOfDay(), false)) : '-') }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-blue-200 bg-blue-50 p-5 text-sm text-blue-950">
            <p class="font-bold">Escolha de plano</p>
            @if ($mercadoPagoConfigured)
                <p class="mt-1">Escolha um plano para abrir o checkout do Mercado Pago. A assinatura sera ativada automaticamente apos a confirmacao do pagamento.</p>
            @else
                <p class="mt-1">Mercado Pago ainda nao configurado. Depois de escolher um plano, o Super Admin confirma a assinatura no Admin Produto.</p>
            @endif
            @if ($currentSubscription?->status === \App\Models\Subscription::STATUS_PENDING && $currentSubscription->checkout_url)
                <div class="mt-4 flex flex-wrap gap-3">
                    <a href="{{ $currentSubscription->checkout_url }}" class="inline-flex rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-800">Continuar pagamento pendente</a>
                    <form method="POST" action="{{ route('subscriptions.cancel-pending') }}">
                        @csrf
                        @method('PATCH')
                        <button class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancelar pendencia</button>
                    </form>
                </div>
            @elseif ($currentSubscription?->status === \App\Models\Subscription::STATUS_PENDING)
                <form method="POST" action="{{ route('subscriptions.cancel-pending') }}" class="mt-4">
                    @csrf
                    @method('PATCH')
                    <button class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancelar escolha de plano</button>
                </form>
            @endif
        </section>

        <section class="grid gap-4 lg:grid-cols-3">
            @forelse ($plans as $plan)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-black text-slate-950">{{ $plan->name }}</h2>
                            <p class="mt-1 text-sm text-slate-500">Trial de {{ $plan->trial_days }} dia{{ $plan->trial_days === 1 ? '' : 's' }}</p>
                        </div>
                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-700">Ativo</span>
                    </div>
                    <p class="mt-5 text-3xl font-black text-blue-700">{{ $plan->formattedPrice() }}</p>
                    <form method="POST" action="{{ route('subscriptions.choose') }}" class="mt-5">
                        @csrf
                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                        <button class="w-full rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-800">{{ $mercadoPagoConfigured ? 'Pagar com Mercado Pago' : 'Escolher plano' }}</button>
                    </form>
                </article>
            @empty
                <p class="rounded-2xl border border-dashed border-slate-200 bg-white px-5 py-10 text-center text-sm text-slate-500 lg:col-span-3">Nenhum plano ativo disponivel no momento.</p>
            @endforelse
        </section>
    </div>
</x-app.layout>
