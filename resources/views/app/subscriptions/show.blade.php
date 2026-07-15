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

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4" data-tour="subscription-summary">
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

        <section class="rounded-2xl border border-blue-200 bg-blue-50 p-5 text-sm text-blue-950" data-tour="subscription-choice">
            <p class="font-bold">Escolha de plano</p>
            @if ($mercadoPagoConfigured)
                <p class="mt-1">Escolha um plano para abrir o checkout do Mercado Pago. A assinatura será ativada automaticamente após a confirmação do pagamento.</p>
                @if ($mercadoPagoEnvironment === 'teste')
                    <p class="mt-3 inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-800">Mercado Pago em modo teste</p>
                @elseif ($mercadoPagoLiveCheckoutAllowed)
                    <p class="mt-3 inline-flex rounded-full bg-rose-100 px-3 py-1 text-xs font-black text-rose-800">Mercado Pago em produção: cobrança real habilitada</p>
                @else
                    <p class="mt-3 inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-800">Token de produção detectado, mas cobrança real bloqueada</p>
                @endif
            @else
                <p class="mt-1">Mercado Pago ainda não configurado. Depois de escolher um plano, o Super Admin confirma a assinatura no Admin Produto.</p>
            @endif
            @if ($currentSubscription?->status === \App\Models\Subscription::STATUS_PENDING && $currentSubscription->checkout_url)
                <div class="mt-4 flex flex-wrap gap-3" data-tour="subscription-pending">
                    <a href="{{ $currentSubscription->checkout_url }}" class="inline-flex rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-800">Continuar pagamento pendente</a>
                    <form method="POST" action="{{ route('subscriptions.cancel-pending') }}">
                        @csrf
                        @method('PATCH')
                        <button class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancelar pendência</button>
                    </form>
                </div>
            @elseif ($currentSubscription?->status === \App\Models\Subscription::STATUS_PENDING)
                <form method="POST" action="{{ route('subscriptions.cancel-pending') }}" class="mt-4" data-tour="subscription-pending">
                    @csrf
                    @method('PATCH')
                    <button class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancelar escolha de plano</button>
                </form>
            @endif
        </section>

        <section class="grid gap-4 lg:grid-cols-3" data-tour="subscription-plans">
            @forelse ($plans as $plan)
                @php
                    $isCurrentPlan = $activePlanId !== null && (int) $activePlanId === (int) $plan->id;
                @endphp
                <article class="rounded-2xl border {{ $isCurrentPlan ? 'border-emerald-300 bg-emerald-50/40 ring-2 ring-emerald-100' : 'border-slate-200 bg-white' }} p-5 shadow-sm" @if ($loop->first) data-tour="subscription-plan-card" @endif>
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-black text-slate-950">{{ $plan->name }}</h2>
                            <p class="mt-1 text-sm text-slate-500">Trial de {{ $plan->trial_days }} dia{{ $plan->trial_days === 1 ? '' : 's' }}</p>
                        </div>
                        @if ($isCurrentPlan)
                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-800">Plano atual</span>
                        @else
                            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-black text-blue-700">Disponível</span>
                        @endif
                    </div>
                    <p class="mt-5 text-3xl font-black {{ $isCurrentPlan ? 'text-emerald-700' : 'text-blue-700' }}">{{ $plan->formattedPrice() }}</p>
                    <form method="POST" action="{{ route('subscriptions.choose') }}" class="mt-5" @if ($loop->first) data-tour="subscription-plan-action" @endif>
                        @csrf
                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                        <button @if ($isCurrentPlan || ($mercadoPagoConfigured && ! $mercadoPagoLiveCheckoutAllowed)) disabled @endif class="w-full rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-800 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-slate-500">{{ $isCurrentPlan ? 'Assinatura ativa' : ($mercadoPagoConfigured ? 'Pagar com Mercado Pago' : 'Escolher plano') }}</button>
                        @if ($mercadoPagoConfigured && ! $mercadoPagoLiveCheckoutAllowed)
                            <p class="mt-2 text-xs font-bold text-amber-700">Checkout bloqueado até habilitar MERCADO_PAGO_LIVE_ENABLED=true.</p>
                        @elseif ($isCurrentPlan)
                            <p class="mt-2 text-xs font-bold text-emerald-700">Este é o plano ativo da unidade.</p>
                        @endif
                    </form>
                </article>
            @empty
                <p class="rounded-2xl border border-dashed border-slate-200 bg-white px-5 py-10 text-center text-sm text-slate-500 lg:col-span-3">Nenhum plano ativo disponível no momento.</p>
            @endforelse
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" data-tour="subscription-history">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                <div>
                    <h2 class="text-lg font-black text-slate-950">Histórico de assinatura</h2>
                    <p class="mt-1 text-sm text-slate-500">Últimas escolhas de plano, pagamentos e renovações da unidade.</p>
                </div>
            </div>

            @if ($subscriptionHistory->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-black uppercase text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Plano</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3">Período</th>
                                <th class="px-5 py-3">Pagamento</th>
                                <th class="px-5 py-3">Referencia</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($subscriptionHistory as $subscription)
                                @php
                                    $statusClasses = match ($subscription->status) {
                                        \App\Models\Subscription::STATUS_ACTIVE => 'bg-emerald-100 text-emerald-800',
                                        \App\Models\Subscription::STATUS_PENDING => 'bg-amber-100 text-amber-800',
                                        \App\Models\Subscription::STATUS_EXPIRED => 'bg-slate-200 text-slate-700',
                                        \App\Models\Subscription::STATUS_CANCELED => 'bg-rose-100 text-rose-800',
                                        default => 'bg-slate-100 text-slate-700',
                                    };
                                @endphp
                                <tr class="align-top">
                                    <td class="px-5 py-4">
                                        <p class="font-black text-slate-950">{{ $subscription->plan?->name ?? 'Plano removido' }}</p>
                                        <p class="mt-1 text-xs text-slate-500">Criada em {{ $subscription->created_at->format('d/m/Y H:i') }}</p>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="rounded-full px-3 py-1 text-xs font-black {{ $statusClasses }}">{{ $subscription->statusLabel() }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-slate-700">
                                        <p>Início: {{ $subscription->started_at?->format('d/m/Y') ?? '-' }}</p>
                                        <p class="mt-1">Fim: {{ $subscription->ends_at?->format('d/m/Y') ?? '-' }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-slate-700">
                                        <p>{{ $subscription->payment_provider === 'mercado_pago' ? 'Mercado Pago' : 'Manual' }}</p>
                                        <p class="mt-1 text-xs text-slate-500">Pago em {{ $subscription->paid_at?->format('d/m/Y H:i') ?? '-' }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-xs text-slate-500">
                                        <p>Pagamento: {{ $subscription->provider_payment_id ?? '-' }}</p>
                                        <p class="mt-1">Preferência: {{ $subscription->provider_preference_id ?? '-' }}</p>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="px-5 py-8 text-center text-sm text-slate-500">Nenhum histórico de assinatura registrado ainda.</p>
            @endif
        </section>
    </div>

    @php
        $subscriptionTour = [
            'key' => 'subscriptions.show.v1',
            'title' => 'Entendendo a assinatura',
            'steps' => [
                [
                    'target' => '[data-tour="subscription-summary"]',
                    'title' => 'Situação da unidade',
                    'body' => 'Aqui ficam o plano atual, status da assinatura, próxima cobrança e dias restantes para acompanhar a liberação da unidade.',
                ],
                [
                    'target' => '[data-tour="subscription-choice"]',
                    'title' => 'Escolha de plano',
                    'body' => 'Este bloco explica como o checkout está configurado e mostra pendências quando existe uma escolha de plano ainda não concluída.',
                ],
                [
                    'target' => '[data-tour="subscription-pending"]',
                    'title' => 'Pagamento pendente',
                    'body' => 'Quando houver uma tentativa em aberto, use estes atalhos para continuar o checkout ou cancelar a escolha antes de selecionar outro plano.',
                ],
                [
                    'target' => '[data-tour="subscription-plans"]',
                    'title' => 'Planos disponíveis',
                    'body' => 'Os cards mostram somente planos ativos para contratação. O plano contratado fica destacado como Plano atual.',
                ],
                [
                    'target' => '[data-tour="subscription-plan-card"]',
                    'title' => 'Card do plano',
                    'body' => 'Confira nome, trial, preço e selo do plano antes de iniciar a contratação.',
                ],
                [
                    'target' => '[data-tour="subscription-plan-action"]',
                    'title' => 'Contratação',
                    'body' => 'Use o botão do card para escolher o plano ou abrir o Mercado Pago quando o checkout estiver configurado.',
                ],
                [
                    'target' => '[data-tour="subscription-history"]',
                    'title' => 'Histórico',
                    'body' => 'Aqui ficam as escolhas, pagamentos, renovações e referências do provedor para conferência futura.',
                ],
            ],
        ];
    @endphp

    <script type="application/json" data-onboarding-tour>
        {!! json_encode($subscriptionTour, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
</x-app.layout>
