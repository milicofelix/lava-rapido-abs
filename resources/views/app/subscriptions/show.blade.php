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

        @if ($location->subscriptionStatus() === \App\Models\WashLocation::ACCOUNT_STATUS_EXPIRED)
            <section class="rounded-2xl border border-rose-200 bg-rose-50 p-5 text-sm text-rose-950">
                <p class="font-black">Assinatura expirada</p>
                <p class="mt-1">Escolha um plano e solicite a ativação para liberar a operação.</p>
            </section>
        @endif

        <section class="grid min-w-0 gap-4 md:grid-cols-2 xl:grid-cols-4" data-tour="subscription-summary">
            <div class="min-w-0 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Plano atual</p>
                <p class="mt-2 break-words text-2xl font-black text-slate-950">{{ $activeSubscription?->plan?->name ?? 'Nenhum' }}</p>
            </div>
            <div class="min-w-0 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Status</p>
                <p class="mt-2 text-2xl font-black text-slate-950">{{ $location->accountStatusLabel() }}</p>
                @if ($currentSubscription)
                    <p class="mt-1 text-xs font-bold text-slate-500">{{ $currentSubscription->statusLabel() }}</p>
                @endif
            </div>
            <div class="min-w-0 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Próxima cobrança</p>
                <p class="mt-2 whitespace-nowrap text-2xl font-black text-slate-950">{{ $activeSubscription?->ends_at?->format('d/m/Y') ?? '-' }}</p>
            </div>
            <div class="min-w-0 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Dias restantes</p>
                <p class="mt-2 text-2xl font-black text-slate-950">{{ $location->trialDaysRemaining() ?? ($location->subscription_ends_at ? max(0, (int) now()->startOfDay()->diffInDays($location->subscription_ends_at->copy()->startOfDay(), false)) : '-') }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-blue-200 bg-blue-50 p-5 text-sm text-blue-950" data-tour="subscription-choice">
            <p class="font-bold">Escolha de plano</p>
            <p class="mt-1">Escolha o plano ideal para sua unidade. O pagamento é feito por Pix e a assinatura será liberada após a confirmação.</p>
            @if ($currentSubscription?->status === \App\Models\Subscription::STATUS_PENDING && $currentSubscription->payment_provider === \App\Models\Subscription::PAYMENT_PROVIDER_MANUAL_PIX)
                @php
                    $pixPayload = $currentSubscription->provider_payload ?? [];
                @endphp
                <div class="mt-5 rounded-2xl border border-emerald-200 bg-white p-4 text-slate-800" data-tour="subscription-pending">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-emerald-700">Pagamento Pix pendente</p>
                            <h2 class="mt-1 text-lg font-black text-slate-950">{{ $currentSubscription->plan?->name ?? 'Plano selecionado' }} · {{ $currentSubscription->plan?->formattedPrice() }}</h2>
                            <p class="mt-1 text-sm text-slate-500">Após pagar, envie o comprovante informando a referência abaixo para liberar sua assinatura.</p>
                        </div>
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-800">Aguardando conferência</span>
                    </div>

                    <dl class="mt-4 grid gap-3 md:grid-cols-3">
                        <div class="rounded-xl bg-slate-50 p-3">
                            <dt class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Chave Pix</dt>
                            <dd class="mt-1 break-all font-black text-slate-950">{{ $pixPayload['pix_key'] ?? $pixKey }}</dd>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-3">
                            <dt class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Valor</dt>
                            <dd class="mt-1 font-black text-emerald-700">{{ $currentSubscription->plan?->formattedPrice() }}</dd>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-3">
                            <dt class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Referência</dt>
                            <dd class="mt-1 break-all font-black text-slate-950">{{ $currentSubscription->external_reference }}</dd>
                        </div>
                    </dl>

                    @if (! empty($pixPayload['copy_paste']))
                        <label class="mt-4 block">
                            <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Pix Copia e Cola</span>
                            <textarea readonly rows="4" data-subscription-pix-code class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700">{{ $pixPayload['copy_paste'] }}</textarea>
                        </label>
                    @endif

                    <div class="mt-4 flex flex-wrap gap-3">
                        <button type="button" data-copy-subscription-pix class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-700">Copiar Pix Copia e Cola</button>
                        <button type="button" data-copy-value="{{ $pixPayload['pix_key'] ?? $pixKey }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Copiar chave Pix</button>
                        <form method="POST" action="{{ route('subscriptions.cancel-pending') }}">
                            @csrf
                            @method('PATCH')
                            <button class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancelar pendência</button>
                        </form>
                    </div>
                </div>
            @endif
            @if ($currentSubscription?->status === \App\Models\Subscription::STATUS_PENDING && $currentSubscription->checkout_url && $currentSubscription->payment_provider !== \App\Models\Subscription::PAYMENT_PROVIDER_MERCADO_PAGO)
                <div class="mt-4 flex flex-wrap gap-3" data-tour="subscription-pending">
                    <a href="{{ $currentSubscription->checkout_url }}" class="inline-flex rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-800">Continuar pagamento pendente</a>
                    <form method="POST" action="{{ route('subscriptions.cancel-pending') }}">
                        @csrf
                        @method('PATCH')
                        <button class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancelar pendência</button>
                    </form>
                </div>
            @elseif ($currentSubscription?->status === \App\Models\Subscription::STATUS_PENDING && $currentSubscription->payment_provider !== \App\Models\Subscription::PAYMENT_PROVIDER_MANUAL_PIX)
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
                            <p class="mt-1 text-sm text-slate-500">Período gratuito de {{ $plan->trial_days }} dia{{ $plan->trial_days === 1 ? '' : 's' }}</p>
                        </div>
                        @if ($isCurrentPlan)
                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-800">Plano atual</span>
                        @else
                            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-black text-blue-700">Disponível</span>
                        @endif
                    </div>
                    <p class="mt-5 text-3xl font-black {{ $isCurrentPlan ? 'text-emerald-700' : 'text-blue-700' }}">{{ $plan->formattedPrice() }}</p>
                    <div class="mt-5 space-y-2" @if ($loop->first) data-tour="subscription-plan-action" @endif>
                        <form method="POST" action="{{ route('subscriptions.choose-pix') }}">
                            @csrf
                            <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                            <button @if ($isCurrentPlan) disabled @endif class="w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-slate-500">{{ $isCurrentPlan ? 'Assinatura ativa' : 'Pagar via Pix' }}</button>
                        </form>
                        {{-- Mercado Pago preservado no backend para uma próxima fase de cobrança automática.
                        <form method="POST" action="{{ route('subscriptions.choose') }}">
                            @csrf
                            <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                            <button @if ($isCurrentPlan || ($mercadoPagoConfigured && ! $mercadoPagoLiveCheckoutAllowed)) disabled @endif class="w-full rounded-xl border border-blue-200 bg-white px-4 py-2.5 text-sm font-bold text-blue-700 hover:bg-blue-50 disabled:cursor-not-allowed disabled:border-slate-200 disabled:bg-slate-100 disabled:text-slate-500">{{ $isCurrentPlan ? 'Assinatura ativa' : ($mercadoPagoConfigured ? 'Pagar com Mercado Pago' : 'Escolher plano') }}</button>
                            @if ($mercadoPagoConfigured && ! $mercadoPagoLiveCheckoutAllowed)
                                <p class="mt-2 text-xs font-bold text-amber-700">Checkout bloqueado até habilitar MERCADO_PAGO_LIVE_ENABLED=true.</p>
                            @elseif ($isCurrentPlan)
                                <p class="mt-2 text-xs font-bold text-emerald-700">Este é o plano ativo da unidade.</p>
                            @endif
                        </form>
                        --}}
                        @if ($isCurrentPlan)
                            <p class="mt-2 text-xs font-bold text-emerald-700">Este é o plano ativo da unidade.</p>
                        @endif
                    </div>
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
                <div class="space-y-3 p-4 md:hidden">
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
                        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex min-w-0 items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="break-words font-black text-slate-950">{{ $subscription->plan?->name ?? 'Plano removido' }}</p>
                                    <p class="mt-1 text-xs font-semibold text-slate-500">Criada em {{ $subscription->created_at->format('d/m/Y H:i') }}</p>
                                </div>
                                <span class="shrink-0 rounded-full px-3 py-1 text-xs font-black {{ $statusClasses }}">{{ $subscription->statusLabel() }}</span>
                            </div>

                            <dl class="mt-4 grid gap-3 text-sm">
                                <div class="rounded-xl bg-white px-3 py-2">
                                    <dt class="text-xs font-black uppercase tracking-[0.12em] text-slate-500">Período</dt>
                                    <dd class="mt-1 font-bold text-slate-800">
                                        <span class="whitespace-nowrap">Início: {{ $subscription->started_at?->format('d/m/Y') ?? '-' }}</span>
                                        <span class="mx-1 text-slate-300">•</span>
                                        <span class="whitespace-nowrap">Fim: {{ $subscription->ends_at?->format('d/m/Y') ?? '-' }}</span>
                                    </dd>
                                </div>
                                <div class="rounded-xl bg-white px-3 py-2">
                                    <dt class="text-xs font-black uppercase tracking-[0.12em] text-slate-500">Pagamento</dt>
                                    <dd class="mt-1 font-bold text-slate-800">{{ $subscription->paymentProviderLabel() }}</dd>
                                    <dd class="mt-1 text-xs font-semibold text-slate-500">Pago em {{ $subscription->paid_at?->format('d/m/Y H:i') ?? '-' }}</dd>
                                </div>
                                <div class="rounded-xl bg-white px-3 py-2">
                                    <dt class="text-xs font-black uppercase tracking-[0.12em] text-slate-500">Referência</dt>
                                    <dd class="mt-1 break-all text-xs font-semibold text-slate-600">Pagamento: {{ $subscription->provider_payment_id ?? '-' }}</dd>
                                    <dd class="mt-1 break-all text-xs font-semibold text-slate-600">Preferência: {{ $subscription->provider_preference_id ?? '-' }}</dd>
                                </div>
                            </dl>
                        </article>
                    @endforeach
                </div>

                <div class="hidden overflow-x-auto md:block">
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
                                        <p>{{ $subscription->paymentProviderLabel() }}</p>
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
                    'body' => 'Este bloco explica como escolher um plano e mostra pendências quando existe uma escolha ainda não concluída.',
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
                    'body' => 'Confira nome, período gratuito, preço e selo do plano antes de iniciar a contratação.',
                ],
                [
                    'target' => '[data-tour="subscription-plan-action"]',
                    'title' => 'Contratação',
                    'body' => 'Use o botão do card para gerar o pagamento via Pix do plano escolhido.',
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
    <script>
        document.querySelectorAll('[data-copy-subscription-pix]').forEach((button) => {
            button.addEventListener('click', async () => {
                const textarea = document.querySelector('[data-subscription-pix-code]');

                if (! textarea) {
                    return;
                }

                textarea.select();
                textarea.setSelectionRange(0, textarea.value.length);

                try {
                    await navigator.clipboard.writeText(textarea.value);
                } catch (error) {
                    document.execCommand('copy');
                }

                button.textContent = 'Pix copiado';
                setTimeout(() => {
                    button.textContent = 'Copiar Pix Copia e Cola';
                }, 2200);
            });
        });

        document.querySelectorAll('[data-copy-value]').forEach((button) => {
            button.addEventListener('click', async () => {
                const originalText = button.textContent;

                try {
                    await navigator.clipboard.writeText(button.dataset.copyValue || '');
                } catch (error) {
                    const textarea = document.createElement('textarea');
                    textarea.value = button.dataset.copyValue || '';
                    textarea.style.position = 'fixed';
                    textarea.style.opacity = '0';
                    document.body.appendChild(textarea);
                    textarea.select();
                    document.execCommand('copy');
                    textarea.remove();
                }

                button.textContent = 'Chave copiada';
                setTimeout(() => {
                    button.textContent = originalText;
                }, 2200);
            });
        });
    </script>
</x-app.layout>
