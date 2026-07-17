<x-app.layout heading="Campanhas de fidelidade" title="Campanhas de fidelidade · AutoFlow">
    <div class="space-y-5">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" data-tour="loyalty-campaigns-header">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-fuchsia-700">Fidelidade</p>
                    <h2 class="mt-1 text-2xl font-black text-slate-950">Campanhas e promoções</h2>
                    <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">Use segmentos prontos para falar com clientes perto de ganhar benefício, cupons vencendo e clientes que não retornam há algum tempo.</p>
                </div>
                <div class="flex flex-wrap gap-2" data-tour="loyalty-campaigns-actions">
                    <a href="{{ route('loyalty-reports.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm hover:bg-slate-50">Relatório</a>
                    @if ($program)
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700 ring-1 ring-emerald-200">Programa ativo</span>
                    @else
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600 ring-1 ring-slate-200">Programa desativado</span>
                    @endif
                </div>
            </div>

            <div class="mt-5 rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm font-semibold leading-6 text-blue-900" data-tour="loyalty-campaigns-notice">
                Esta tela prepara campanhas manuais. O envio oficial/automático por WhatsApp continua fora desta etapa.
            </div>
        </section>

        @php
            $hasMarkedCampaignItem = false;
        @endphp

        <section class="grid gap-5 xl:grid-cols-3" data-tour="loyalty-campaigns-grid">
            @foreach ($campaigns as $campaign)
                @php
                    $toneClasses = match ($campaign['tone']) {
                        'amber' => [
                            'eyebrow' => 'text-amber-700',
                            'badge' => 'bg-amber-50 text-amber-700 ring-amber-200',
                            'panel' => 'border-amber-100 bg-amber-50/40',
                            'button' => 'bg-amber-600 hover:bg-amber-700',
                        ],
                        'blue' => [
                            'eyebrow' => 'text-blue-700',
                            'badge' => 'bg-blue-50 text-blue-700 ring-blue-200',
                            'panel' => 'border-blue-100 bg-blue-50/40',
                            'button' => 'bg-blue-700 hover:bg-blue-800',
                        ],
                        default => [
                            'eyebrow' => 'text-fuchsia-700',
                            'badge' => 'bg-fuchsia-50 text-fuchsia-700 ring-fuchsia-200',
                            'panel' => 'border-fuchsia-100 bg-fuchsia-50/40',
                            'button' => 'bg-fuchsia-700 hover:bg-fuchsia-800',
                        ],
                    };
                @endphp

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" @if ($loop->first) data-tour="loyalty-campaigns-card" @endif>
                    <div class="border-b border-slate-100 px-5 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.16em] {{ $toneClasses['eyebrow'] }}">Campanha</p>
                                <h2 class="mt-1 font-black text-slate-950">{{ $campaign['title'] }}</h2>
                                <p class="mt-1 text-sm leading-5 text-slate-500">{{ $campaign['description'] }}</p>
                            </div>
                            <span class="shrink-0 rounded-full px-3 py-1 text-xs font-black ring-1 {{ $toneClasses['badge'] }}">{{ $campaign['items']->count() }}</span>
                        </div>
                    </div>

                    <div class="divide-y divide-slate-100">
                        @forelse ($campaign['items'] as $item)
                            @php
                                $whatsappUrl = $item['customer']?->whatsappManualUrl($item['message']);
                                $isFirstCampaignItem = ! $hasMarkedCampaignItem;
                                $hasMarkedCampaignItem = true;
                            @endphp
                            <article class="p-5">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate font-black text-slate-950">{{ $item['title'] }}</p>
                                        <p class="mt-1 text-sm font-semibold text-slate-600">{{ $item['subtitle'] }}</p>
                                        <p class="mt-1 text-xs font-bold text-slate-500">{{ $item['meta'] }}</p>
                                    </div>
                                    <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-black ring-1 {{ $toneClasses['badge'] }}">{{ $item['badge'] }}</span>
                                </div>

                                @if ($item['progress'] !== null)
                                    <div class="mt-3 h-2 rounded-full bg-slate-100">
                                        <div class="h-2 rounded-full bg-fuchsia-600" style="width: {{ $item['progress'] }}%"></div>
                                    </div>
                                @endif

                                <div class="mt-4 rounded-2xl border p-3 text-sm leading-6 text-slate-700 {{ $toneClasses['panel'] }}" @if ($isFirstCampaignItem) data-tour="loyalty-campaigns-message" @endif>
                                    {{ $item['message'] }}
                                </div>

                                <div class="mt-4 flex flex-wrap gap-2" @if ($isFirstCampaignItem) data-tour="loyalty-campaigns-item-actions" @endif>
                                    @if ($whatsappUrl)
                                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="rounded-xl px-4 py-2 text-sm font-black text-white shadow-sm {{ $toneClasses['button'] }}">Enviar manualmente</a>
                                    @else
                                        <span class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-bold text-slate-500">Sem WhatsApp</span>
                                    @endif
                                    @if ($item['customer'])
                                        <a href="{{ route('customers.edit', $item['customer']) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Abrir cliente</a>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <p class="px-5 py-10 text-center text-sm font-semibold text-slate-500">Nenhum cliente neste segmento agora.</p>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </section>
    </div>

    @php
        $loyaltyCampaignsTour = [
            'key' => 'loyalty-campaigns.index.v1',
            'title' => 'Entendendo as Campanhas',
            'steps' => [
                [
                    'target' => '[data-tour="loyalty-campaigns-header"]',
                    'title' => 'Campanhas manuais',
                    'body' => 'Esta tela separa clientes em grupos prontos para contato, como quem está perto de ganhar cupom, quem tem cupom vencendo ou quem não retorna há algum tempo.',
                ],
                [
                    'target' => '[data-tour="loyalty-campaigns-actions"]',
                    'title' => 'Voltar ao relatório',
                    'body' => 'Use Relatório para voltar à visão gerencial da fidelidade e conferir métricas, cupons e progresso dos clientes.',
                ],
                [
                    'target' => '[data-tour="loyalty-campaigns-notice"]',
                    'title' => 'Envio manual',
                    'body' => 'O sistema prepara mensagens e links, mas o envio oficial ou automático por WhatsApp continua fora desta etapa.',
                ],
                [
                    'target' => '[data-tour="loyalty-campaigns-grid"]',
                    'title' => 'Segmentos prontos',
                    'body' => 'Cada coluna representa um tipo de campanha. O número no topo mostra quantos clientes entraram naquele segmento agora.',
                ],
                [
                    'target' => '[data-tour="loyalty-campaigns-card"]',
                    'title' => 'Campanha',
                    'body' => 'Leia a descrição para entender o objetivo do segmento antes de entrar em contato com os clientes listados.',
                ],
                [
                    'target' => '[data-tour="loyalty-campaigns-message"]',
                    'title' => 'Mensagem sugerida',
                    'body' => 'A mensagem já vem personalizada com nome, cupom ou contexto do cliente para facilitar o envio manual.',
                ],
                [
                    'target' => '[data-tour="loyalty-campaigns-item-actions"]',
                    'title' => 'Contato e cadastro',
                    'body' => 'Envie a mensagem pelo WhatsApp manual ou abra o cadastro do cliente para revisar telefone, histórico e observações.',
                ],
            ],
        ];
    @endphp
    <script type="application/json" data-onboarding-tour>
        {!! json_encode($loyaltyCampaignsTour, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
</x-app.layout>
