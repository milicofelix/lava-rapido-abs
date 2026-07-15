<x-app.layout title="Relatórios executivos - AutoFlow" heading="Relatórios executivos">
    @php
        $money = fn ($value) => 'R$ '.number_format((float) $value, 2, ',', '.');
        $number = fn ($value) => number_format((float) $value, 0, ',', '.');
        $variationLabel = function ($value) {
            if ($value === null) {
                return 'sem base anterior';
            }

            if ((float) $value === 0.0) {
                return 'estável';
            }

            return (($value > 0) ? '+' : '').number_format((float) $value, 1, ',', '.').'% vs período anterior';
        };
        $variationClass = fn ($value) => $value === null
            ? 'bg-blue-50 text-blue-700 ring-blue-100'
            : ((float) $value >= 0 ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-red-50 text-red-700 ring-red-100');
    @endphp

    <section class="space-y-5">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" data-tour="executive-report-period">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Visão gerencial</p>
                    <h2 class="mt-1 text-2xl font-black text-slate-950">Resumo executivo do período</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Comparando {{ \Illuminate\Support\Carbon::parse($start)->format('d/m/Y') }} até {{ \Illuminate\Support\Carbon::parse($end)->format('d/m/Y') }}
                        com {{ \Illuminate\Support\Carbon::parse($previousStart)->format('d/m/Y') }} até {{ \Illuminate\Support\Carbon::parse($previousEnd)->format('d/m/Y') }}.
                    </p>
                </div>

                <form method="GET" action="{{ route('reports.executive') }}" class="grid gap-3 sm:grid-cols-[1fr_1fr_auto_auto_auto] sm:items-end" data-tour="executive-report-filters">
                    <label class="block">
                        <span class="text-xs font-bold text-slate-500">Início</span>
                        <input type="date" name="start" value="{{ $start }}" max="{{ today()->toDateString() }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </label>
                    <label class="block">
                        <span class="text-xs font-bold text-slate-500">Fim</span>
                        <input type="date" name="end" value="{{ $end }}" max="{{ today()->toDateString() }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </label>
                    <button class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-black text-white shadow-sm transition hover:bg-blue-800">Filtrar</button>
                    <a href="{{ route('reports.executive') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-center text-sm font-bold text-slate-700 hover:bg-slate-50">Mês atual</a>
                    <a href="{{ route('reports.executive.pdf', ['start' => $start, 'end' => $end]) }}" class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-2.5 text-center text-sm font-bold text-blue-700 hover:bg-blue-100">Exportar PDF</a>
                </form>
            </div>

            @if ($errors->any())
                <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4" data-tour="executive-report-kpis">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Receita do período</p>
                <p class="mt-3 text-3xl font-black text-slate-950">{{ $money($summary['revenue']) }}</p>
                <span class="mt-4 inline-flex rounded-full px-3 py-1 text-xs font-black ring-1 {{ $variationClass($variations['revenue']) }}">{{ $variationLabel($variations['revenue']) }}</span>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Lavagens no período</p>
                <p class="mt-3 text-3xl font-black text-slate-950">{{ $number($summary['orders_count']) }}</p>
                <span class="mt-4 inline-flex rounded-full px-3 py-1 text-xs font-black ring-1 {{ $variationClass($variations['orders']) }}">{{ $variationLabel($variations['orders']) }}</span>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Ticket médio</p>
                <p class="mt-3 text-3xl font-black text-slate-950">{{ $money($summary['ticket_average']) }}</p>
                <span class="mt-4 inline-flex rounded-full px-3 py-1 text-xs font-black ring-1 {{ $variationClass($variations['ticket_average']) }}">{{ $variationLabel($variations['ticket_average']) }}</span>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Clientes recorrentes</p>
                <p class="mt-3 text-3xl font-black text-slate-950">{{ $number($summary['recurring_customers_count']) }}</p>
                <span class="mt-4 inline-flex rounded-full px-3 py-1 text-xs font-black ring-1 {{ $variationClass($variations['recurring_customers']) }}">{{ $variationLabel($variations['recurring_customers']) }}</span>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-4" data-tour="executive-report-operational-kpis">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Entregues</p>
                <p class="mt-2 text-2xl font-black text-slate-950">{{ $number($summary['delivered_count']) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Canceladas</p>
                <p class="mt-2 text-2xl font-black text-slate-950">{{ $number($summary['canceled_count']) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Clientes ativos</p>
                <p class="mt-2 text-2xl font-black text-slate-950">{{ $number($summary['active_customers_count']) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Novos clientes</p>
                <p class="mt-2 text-2xl font-black text-slate-950">{{ $number($summary['new_customers_count']) }}</p>
            </div>
        </div>

        <div class="grid gap-5 xl:grid-cols-[1.2fr_0.8fr]">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" data-tour="executive-report-top-services">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-black text-slate-950">Top serviços</p>
                        <p class="text-xs text-slate-500">Serviços mais realizados no período.</p>
                    </div>
                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-black text-blue-700">{{ count($topServices) }} itens</span>
                </div>
                <div class="mt-5 space-y-4">
                    @forelse ($topServices as $service)
                        <div>
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <p class="font-bold text-slate-800">{{ $service['service_name'] }}</p>
                                <p class="font-black text-slate-950">{{ $service['total'] }} lav.</p>
                            </div>
                            <div class="mt-2 h-2 rounded-full bg-slate-100">
                                <div class="h-2 rounded-full bg-blue-700" style="width: {{ max(6, $service['share']) }}%"></div>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">{{ $money($service['revenue']) }} em serviços</p>
                        </div>
                    @empty
                        <p class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">Nenhum serviço no período.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" data-tour="executive-report-payments">
                <p class="text-sm font-black text-slate-950">Pagamentos por método</p>
                <p class="text-xs text-slate-500">Distribuição financeira do período.</p>
                <div class="mt-5 space-y-4">
                    @forelse ($paymentMethods as $method)
                        <div>
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <p class="font-bold text-slate-800">{{ $method['label'] }}</p>
                                <p class="font-black text-slate-950">{{ $money($method['total']) }}</p>
                            </div>
                            <div class="mt-2 h-2 rounded-full bg-slate-100">
                                <div class="h-2 rounded-full bg-emerald-500" style="width: {{ max(6, $method['share']) }}%"></div>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">{{ $method['count'] }} pagamento{{ $method['count'] === 1 ? '' : 's' }}</p>
                        </div>
                    @empty
                        <p class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">Nenhum pagamento no período.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="grid gap-5 xl:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-2" data-tour="executive-report-top-customers">
                <p class="text-sm font-black text-slate-950">Top clientes</p>
                <p class="text-xs text-slate-500">Clientes com mais lavagens no período.</p>
                <div class="mt-5 divide-y divide-slate-100">
                    @forelse ($topCustomers as $customer)
                        <div class="grid gap-3 py-3 sm:grid-cols-[1fr_auto] sm:items-center">
                            <div>
                                <p class="font-bold text-slate-900">{{ $customer['name'] }}</p>
                                <p class="text-xs text-slate-500">{{ $customer['phone'] ?? 'Sem telefone' }}</p>
                                <div class="mt-2 h-2 rounded-full bg-slate-100">
                                    <div class="h-2 rounded-full bg-cyan-500" style="width: {{ max(6, $customer['share']) }}%"></div>
                                </div>
                            </div>
                            <div class="text-left sm:text-right">
                                <p class="text-sm font-black text-slate-950">{{ $customer['orders_count'] }} lav.</p>
                                <p class="text-xs text-slate-500">{{ $money($customer['revenue']) }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">Nenhum cliente com lavagem no período.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" data-tour="executive-report-statuses">
                <p class="text-sm font-black text-slate-950">Status das lavagens</p>
                <p class="text-xs text-slate-500">Distribuição operacional.</p>
                <div class="mt-5 space-y-4">
                    @forelse ($statusDistribution as $status)
                        <div>
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <p class="font-bold text-slate-800">{{ $status['label'] }}</p>
                                <p class="font-black text-slate-950">{{ $status['total'] }}</p>
                            </div>
                            <div class="mt-2 h-2 rounded-full bg-slate-100">
                                <div class="h-2 rounded-full bg-slate-800" style="width: {{ max(6, $status['share']) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">Nenhuma lavagem no período.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" data-tour="executive-report-daily-volume">
            <p class="text-sm font-black text-slate-950">Volume diário</p>
            <p class="text-xs text-slate-500">Ajuda a perceber picos de movimento dentro do período filtrado.</p>
            <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
                @forelse ($dailyVolume as $day)
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm font-black text-slate-950">{{ $day['label'] }}</p>
                            <p class="text-sm font-bold text-slate-600">{{ $day['total'] }}</p>
                        </div>
                        <div class="mt-3 h-16 rounded-lg bg-white p-1">
                            <div class="flex h-full items-end">
                                <div class="w-full rounded-md bg-blue-600" style="height: {{ max(8, $day['share']) }}%"></div>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500 lg:col-span-4 xl:col-span-7">Nenhuma movimentação no período.</p>
                @endforelse
            </div>
        </div>
    </section>

    @php
        $executiveReportTour = [
            'key' => 'reports.executive.v1',
            'title' => 'Relatórios executivos',
            'steps' => [
                [
                    'target' => '[data-tour="executive-report-period"]',
                    'title' => 'Visão do período',
                    'body' => 'O relatório compara o período selecionado com o período anterior para mostrar evolução de receita, volume e clientes.',
                ],
                [
                    'target' => '[data-tour="executive-report-filters"]',
                    'title' => 'Filtros e exportação',
                    'body' => 'Use início e fim para analisar uma janela específica, volte ao mês atual ou exporte o PDF para compartilhar.',
                ],
                [
                    'target' => '[data-tour="executive-report-kpis"]',
                    'title' => 'Indicadores principais',
                    'body' => 'Receita, lavagens, ticket médio e recorrência mostram rapidamente se a unidade está crescendo ou caindo.',
                ],
                [
                    'target' => '[data-tour="executive-report-operational-kpis"]',
                    'title' => 'Indicadores operacionais',
                    'body' => 'Entregues, canceladas, clientes ativos e novos clientes ajudam a separar resultado financeiro de operação.',
                ],
                [
                    'target' => '[data-tour="executive-report-top-services"]',
                    'title' => 'Top serviços',
                    'body' => 'Veja quais serviços mais movimentaram a unidade e quanto cada um gerou no período.',
                ],
                [
                    'target' => '[data-tour="executive-report-payments"]',
                    'title' => 'Métodos de pagamento',
                    'body' => 'Esta área mostra como o dinheiro entrou: Pix, dinheiro, cartão ou outros métodos.',
                ],
                [
                    'target' => '[data-tour="executive-report-top-customers"]',
                    'title' => 'Top clientes',
                    'body' => 'Identifique clientes com mais lavagens e maior faturamento para ações de relacionamento e fidelidade.',
                ],
                [
                    'target' => '[data-tour="executive-report-statuses"]',
                    'title' => 'Status das lavagens',
                    'body' => 'A distribuição por status ajuda a encontrar gargalos e cancelamentos no período.',
                ],
                [
                    'target' => '[data-tour="executive-report-daily-volume"]',
                    'title' => 'Volume diário',
                    'body' => 'Use o volume por dia para enxergar picos de movimento e planejar equipe ou promoções.',
                ],
            ],
        ];
    @endphp

    <script type="application/json" data-onboarding-tour>
        {!! json_encode($executiveReportTour, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
</x-app.layout>
