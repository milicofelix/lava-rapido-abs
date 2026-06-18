<x-app.layout heading="{{ $greeting }}, {{ auth()->user()->name }}!" title="Dashboard · AutoFlow">
    <div class="space-y-5">
        @if ($currentLocation)
            <section class="rounded-2xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-950 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Unidade atual</p>
                        <h2 class="mt-1 text-lg font-black">{{ $currentLocation->name }}</h2>
                        <p class="mt-1 text-blue-700">Os indicadores desta tela estao filtrados por este lava-rapido.</p>
                    </div>
                    <span class="rounded-full bg-white px-3 py-1 text-xs font-black text-blue-700">{{ $currentLocation->accountStatusLabel() }}</span>
                </div>
            </section>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Dashboard executivo</p>
                    <h2 class="mt-1 text-xl font-black text-slate-950">Visao do mes</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ ucfirst($monthLabel) }}</p>
                </div>
                @if (auth()->user()->isTeamManager())
                    <a href="{{ route('finance.index') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Abrir financeiro</a>
                @endif
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-4">
                @foreach ([
                    ['label' => 'Lavagens do mes', 'value' => number_format($monthlyWashOrders, 0, ',', '.'), 'comparison' => $executiveComparisons['washOrders'], 'color' => 'bg-blue-50 text-blue-700', 'icon' => 'M'],
                    ['label' => 'Receita do mes', 'value' => 'R$ '.number_format((float) $monthlyRevenue, 2, ',', '.'), 'comparison' => $executiveComparisons['revenue'], 'color' => 'bg-emerald-50 text-emerald-700', 'icon' => '$'],
                    ['label' => 'Ticket medio', 'value' => 'R$ '.number_format((float) $monthlyTicketAverage, 2, ',', '.'), 'comparison' => $executiveComparisons['ticketAverage'], 'color' => 'bg-violet-50 text-violet-700', 'icon' => 'T'],
                    ['label' => 'Clientes recorrentes', 'value' => count($monthlyRecurringCustomers), 'comparison' => $executiveComparisons['recurringCustomers'], 'color' => 'bg-amber-50 text-amber-700', 'icon' => 'R'],
                ] as $metric)
                    @php
                        $comparisonClass = match ($metric['comparison']['tone']) {
                            'positive' => 'text-emerald-600',
                            'negative' => 'text-red-600',
                            default => 'text-slate-500',
                        };
                    @endphp
                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $metric['color'] }} text-sm font-black">{{ $metric['icon'] }}</span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-slate-700">{{ $metric['label'] }}</p>
                                <p class="mt-1 text-2xl font-black text-slate-950">{{ $metric['value'] }}</p>
                                <p class="mt-1 text-xs font-semibold {{ $comparisonClass }}">{{ $metric['comparison']['label'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-5 grid gap-5 xl:grid-cols-2">
                <div class="rounded-xl border border-slate-200 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="font-black text-slate-950">Top servicos do mes</h3>
                        <span class="rounded-lg bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">{{ count($monthlyTopServices) }} servicos</span>
                    </div>
                    <div class="mt-4 space-y-3">
                        @forelse ($monthlyTopServices as $service)
                            <div class="grid grid-cols-[1fr_auto] gap-3 text-sm">
                                <div class="min-w-0">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="truncate font-bold text-slate-800">{{ $service['name'] }}</span>
                                        <span class="shrink-0 text-xs font-bold text-slate-500">{{ $service['count'] }} venda{{ $service['count'] === 1 ? '' : 's' }}</span>
                                    </div>
                                    <div class="mt-2 h-2 rounded-full bg-slate-100">
                                        <div class="h-2 rounded-full bg-blue-600" style="width: {{ $service['percent'] }}%"></div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">Nenhum servico vendido neste mes.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="font-black text-slate-950">Clientes recorrentes</h3>
                        <span class="rounded-lg bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">2+ lavagens</span>
                    </div>
                    <div class="mt-4 space-y-3">
                        @forelse ($monthlyRecurringCustomers as $customer)
                            <div class="grid grid-cols-[1fr_auto] items-center gap-3 rounded-lg bg-slate-50 px-3 py-2 text-sm">
                                <div class="min-w-0">
                                    <p class="truncate font-bold text-slate-800">{{ $customer['name'] }}</p>
                                    <div class="mt-2 h-2 rounded-full bg-white">
                                        <div class="h-2 rounded-full bg-amber-500" style="width: {{ $customer['percent'] }}%"></div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-black text-slate-950">{{ $customer['count'] }}</p>
                                    <p class="text-xs text-slate-500">R$ {{ number_format((float) $customer['revenue'], 2, ',', '.') }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">Nenhum cliente recorrente neste mes.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ([
                ['label' => 'Lavagens hoje', 'value' => $washOrdersToday, 'hint' => '14% vs ontem', 'color' => 'from-blue-500 to-blue-700', 'icon' => 'L'],
                ['label' => 'Em andamento', 'value' => $activeWashOrders, 'hint' => 'Ver detalhes', 'color' => 'from-amber-400 to-orange-500', 'icon' => 'T'],
                ['label' => 'Prontas', 'value' => $readyWashOrders, 'hint' => 'Ver detalhes', 'color' => 'from-emerald-400 to-green-600', 'icon' => 'P'],
                ['label' => 'Entregues hoje', 'value' => $deliveredWashOrdersToday, 'hint' => 'Finalizadas', 'color' => 'from-slate-600 to-slate-950', 'icon' => 'E'],
                ['label' => 'Faturamento hoje', 'value' => 'R$ '.number_format((float) $todayRevenue, 2, ',', '.'), 'hint' => '21% vs ontem', 'color' => 'from-violet-500 to-purple-700', 'icon' => '$'],
            ] as $card)
                <div class="min-w-0 rounded-xl border border-slate-200 bg-white p-4 shadow-sm" aria-label="{{ $card['label'] }}: {{ $card['value'] }}">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br {{ $card['color'] }} text-base font-bold text-white shadow-lg">{{ $card['icon'] }}</span>
                            <div class="min-w-0">
                                <p class="truncate text-xs font-semibold text-slate-700">{{ $card['label'] }}</p>
                                <p class="mt-1 truncate text-xl font-bold text-slate-950">{{ $card['value'] }}</p>
                                <p class="mt-1 truncate text-xs font-semibold text-green-600">{{ $card['hint'] }}</p>
                            </div>
                        </div>
                        <div class="hidden h-10 w-14 shrink-0 items-end gap-1 2xl:flex">
                            @foreach ([20, 34, 26, 48, 42, 60] as $bar)
                                <span class="w-1.5 rounded-full bg-gradient-to-t {{ $card['color'] }}" style="height: {{ $bar }}%"></span>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </section>

        <section class="grid gap-5 2xl:grid-cols-[1fr_420px]">
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-sm font-bold text-blue-700">K</span>
                        <h2 class="text-xl font-bold">Fluxo de Lavagens</h2>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('kanban') }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold">Visualizacao: Kanban</a>
                        <a href="{{ route('wash-orders.index') }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold">Filtrar</a>
                    </div>
                </div>

                <div class="grid gap-3 lg:grid-cols-3 xl:grid-cols-5">
                    @foreach ($kanbanColumns as $column)
                        @php
                            $columnColor = match ($column['key']) {
                                'washing' => 'border-t-blue-600 text-blue-700 bg-blue-50',
                                'finishing' => 'border-t-orange-500 text-orange-600 bg-orange-50',
                                'ready' => 'border-t-green-600 text-green-700 bg-green-50',
                                'delivered' => 'border-t-slate-700 text-slate-800 bg-slate-50',
                                default => 'border-t-slate-400 text-slate-700 bg-slate-50',
                            };
                        @endphp
                        <div class="min-h-[360px] rounded-xl border border-slate-200 border-t-4 {{ $columnColor }} p-2">
                            <div class="flex items-center justify-between px-2 py-2">
                                <h3 class="font-bold">{{ $column['title'] }}</h3>
                                <span class="rounded-full bg-white px-2 py-1 text-xs font-bold text-slate-700 shadow-sm">{{ $column['count'] }}</span>
                            </div>

                            <div class="space-y-2">
                                @forelse ($column['orders'] as $order)
                                    <a href="{{ route('wash-orders.show', $order['id']) }}" class="block rounded-lg border border-slate-200 bg-white p-3 text-slate-950 shadow-sm hover:border-blue-200">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="font-bold">{{ $order['plate'] }}</p>
                                                <p class="mt-1 text-xs text-slate-600">{{ $order['customer'] }}</p>
                                            </div>
                                            <span class="text-xs font-bold text-slate-500">{{ $order['brand'] }}</span>
                                        </div>
                                        <p class="mt-3 text-xs font-semibold">{{ $order['service'] }}</p>
                                        <p class="mt-3 text-xs text-slate-500">Inicio: {{ $order['time'] }}</p>
                                    </a>
                                @empty
                                    <div class="rounded-lg border border-dashed border-slate-200 bg-white/50 px-3 py-8 text-center text-sm text-slate-500">Sem lavagens.</div>
                                @endforelse
                            </div>

                            @if ($column['remaining'] > 0)
                                <p class="mt-3 text-center text-sm font-semibold">+ {{ $column['remaining'] }} na fila</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <aside class="space-y-4">
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="font-bold">Atividades recentes</h2>
                    <div class="mt-4 space-y-4">
                        @forelse ($recentActivities as $activity)
                            <div class="flex gap-3">
                                <span class="mt-1 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $activity['color'] }} text-xs font-bold text-white">A</span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex justify-between gap-3">
                                        <p class="truncate text-sm font-bold">{{ $activity['title'] }}</p>
                                        <span class="shrink-0 text-xs text-slate-500">Ha {{ $activity['time'] }}</span>
                                    </div>
                                    <p class="text-xs text-slate-500">{{ $activity['subtitle'] }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">Nenhuma atividade recente.</p>
                        @endforelse
                    </div>
                    <a href="{{ route('wash-orders.index') }}" class="mt-5 block text-center text-sm font-bold text-blue-700">Ver todas as atividades -></a>
                </section>
            </aside>
        </section>

        <section class="grid gap-5 xl:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="font-bold">Resumo Financeiro</h2>
                    @if (auth()->user()->isTeamManager())
                        <a href="{{ route('finance.index') }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold">Hoje</a>
                    @endif
                </div>
                <p class="mt-5 text-2xl font-bold">R$ {{ number_format((float) $todayRevenue, 2, ',', '.') }}</p>
                <p class="text-sm text-slate-500">Faturamento liquido</p>

                <div class="mt-5 grid gap-5 md:grid-cols-[180px_1fr]">
                    <div class="relative mx-auto h-36 w-36 rounded-full bg-[conic-gradient(#2563eb_0_35%,#10b981_35%_76%,#f59e0b_76%_95%,#8b5cf6_95%_100%)]">
                        <div class="absolute inset-8 rounded-full bg-white"></div>
                    </div>
                    <div class="space-y-3">
                        @forelse ($financeByMethod as $method)
                            <div class="grid grid-cols-[1fr_auto_auto] items-center gap-3 text-sm">
                                <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full {{ $method['color'] }}"></span>{{ $method['label'] }}</span>
                                <strong>R$ {{ number_format((float) $method['total'], 2, ',', '.') }}</strong>
                                <span class="text-slate-500">{{ number_format($method['percent'], 0) }}%</span>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">Nenhum pagamento hoje.</p>
                        @endforelse
                    </div>
                </div>
                @if (auth()->user()->isTeamManager())
                    <a href="{{ route('finance.index') }}" class="mt-5 block border-t border-slate-200 pt-4 text-center text-sm font-bold text-blue-700">Ver relatorio completo</a>
                @endif
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="font-bold">Servicos mais realizados</h2>
                    <span class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold">Hoje</span>
                </div>
                <div class="mt-6 space-y-4">
                    @forelse ($topServices as $service)
                        <div class="grid grid-cols-[160px_1fr_40px_42px] items-center gap-3 text-sm">
                            <span class="truncate font-semibold">{{ $service['name'] }}</span>
                            <div class="h-2 rounded-full bg-slate-100">
                                <div class="h-2 rounded-full bg-blue-600" style="width: {{ $service['percent'] }}%"></div>
                            </div>
                            <span class="text-right font-semibold">{{ $service['count'] }}</span>
                            <span class="text-right text-slate-500">{{ number_format($service['percent'], 0) }}%</span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Nenhum servico vendido nesta semana.</p>
                    @endforelse
                </div>
                @if (auth()->user()->isTeamManager())
                    <a href="{{ route('services.index') }}" class="mt-5 block border-t border-slate-200 pt-4 text-center text-sm font-bold text-blue-700">Ver todos os servicos</a>
                @endif
            </div>
        </section>
    </div>
</x-app.layout>
