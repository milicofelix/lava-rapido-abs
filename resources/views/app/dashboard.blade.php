<x-app.layout heading="Dashboard" title="Dashboard · AutoFlow">
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Lavagens hoje', 'value' => $washOrdersToday],
            ['label' => 'Em andamento', 'value' => $activeWashOrders],
            ['label' => 'Prontas', 'value' => $readyWashOrders],
            ['label' => 'Faturamento do dia', 'value' => 'R$ '.number_format((float) $todayRevenue, 2, ',', '.')],
            ['label' => 'Ticket medio', 'value' => 'R$ '.number_format((float) $ticketAverage, 2, ',', '.')],
            ['label' => 'Servico mais vendido', 'value' => $topService ? $topService['name'] : '-'],
            ['label' => 'Tempo medio', 'value' => $averageWashMinutes > 0 ? $averageWashMinutes.' min' : '-'],
            ['label' => 'Base cadastrada', 'value' => $customerCount.' clientes'],
        ] as $card)
            <div class="rounded-lg border border-zinc-200 bg-white p-5">
                <p class="text-sm text-zinc-500">{{ $card['label'] }}</p>
                <p class="mt-2 text-2xl font-semibold">{{ $card['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-5 xl:grid-cols-3">
        <section class="rounded-lg border border-zinc-200 bg-white">
            <div class="border-b border-zinc-200 px-5 py-4">
                <h2 class="font-semibold">Lavagens por dia</h2>
            </div>
            <div class="space-y-3 p-5">
                @foreach ($washOrdersByDay as $day)
                    <div class="grid grid-cols-[48px_1fr_48px] items-center gap-3 text-sm">
                        <span class="text-zinc-500">{{ $day['label'] }}</span>
                        <div class="h-2 rounded-full bg-zinc-100">
                            <div class="h-2 rounded-full bg-cyan-700" style="width: {{ $day['percent'] }}%"></div>
                        </div>
                        <span class="text-right font-medium">{{ $day['count'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-lg border border-zinc-200 bg-white">
            <div class="border-b border-zinc-200 px-5 py-4">
                <h2 class="font-semibold">Faturamento semanal</h2>
            </div>
            <div class="space-y-3 p-5">
                @foreach ($revenueByDay as $day)
                    <div class="grid grid-cols-[48px_1fr_96px] items-center gap-3 text-sm">
                        <span class="text-zinc-500">{{ $day['label'] }}</span>
                        <div class="h-2 rounded-full bg-zinc-100">
                            <div class="h-2 rounded-full bg-emerald-700" style="width: {{ $day['percent'] }}%"></div>
                        </div>
                        <span class="text-right font-medium">R$ {{ number_format((float) $day['total'], 2, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-lg border border-zinc-200 bg-white">
            <div class="border-b border-zinc-200 px-5 py-4">
                <h2 class="font-semibold">Servicos mais vendidos</h2>
            </div>
            <div class="space-y-4 p-5">
                @forelse ($topServices as $service)
                    <div class="space-y-2">
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="font-medium">{{ $service['name'] }}</span>
                            <span class="text-zinc-500">{{ $service['count'] }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-zinc-100">
                            <div class="h-2 rounded-full bg-indigo-700" style="width: {{ $service['percent'] }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500">Nenhum servico vendido nesta semana.</p>
                @endforelse
            </div>
        </section>
    </div>

    <div class="mt-6 grid gap-5 xl:grid-cols-[1fr_360px]">
        <section class="rounded-lg border border-zinc-200 bg-white">
        <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-4">
            <h2 class="font-semibold">Lavagens recentes</h2>
            <div class="flex gap-2">
                <a href="{{ route('kanban') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium">Kanban</a>
                <a href="{{ route('wash-orders.create') }}" class="rounded-md bg-cyan-700 px-3 py-2 text-sm font-medium text-white">Nova lavagem</a>
            </div>
        </div>
        <div class="divide-y divide-zinc-100">
            @forelse ($recentWashOrders as $washOrder)
                <a href="{{ route('wash-orders.show', $washOrder) }}" class="flex items-center justify-between gap-4 px-5 py-4 hover:bg-zinc-50">
                    <div>
                        <p class="font-medium">{{ $washOrder->code }} · {{ $washOrder->vehicle->plate }}</p>
                        <p class="text-sm text-zinc-500">{{ $washOrder->customer->name }} · {{ $washOrder->statusLabel() }}</p>
                    </div>
                    <span class="text-sm font-semibold">R$ {{ number_format((float) $washOrder->total_amount, 2, ',', '.') }}</span>
                </a>
            @empty
                <p class="px-5 py-8 text-sm text-zinc-500">Nenhuma lavagem aberta ainda.</p>
            @endforelse
        </div>
        </section>

        <section class="space-y-5">
            <div class="rounded-lg border border-zinc-200 bg-white">
                <div class="border-b border-zinc-200 px-5 py-4">
                    <h2 class="font-semibold">Cadastros</h2>
                </div>
                <div class="grid grid-cols-3 divide-x divide-zinc-100 text-center">
                    <div class="p-4">
                        <p class="text-sm text-zinc-500">Clientes</p>
                        <p class="mt-1 text-xl font-semibold">{{ $customerCount }}</p>
                    </div>
                    <div class="p-4">
                        <p class="text-sm text-zinc-500">Veiculos</p>
                        <p class="mt-1 text-xl font-semibold">{{ $vehicleCount }}</p>
                    </div>
                    <div class="p-4">
                        <p class="text-sm text-zinc-500">Servicos</p>
                        <p class="mt-1 text-xl font-semibold">{{ $activeServiceCount }}/{{ $serviceCount }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white">
                <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-4">
                    <h2 class="font-semibold">Clientes recentes</h2>
                    <a href="{{ route('customers.create') }}" class="rounded-md bg-cyan-700 px-3 py-2 text-sm font-medium text-white">Novo cliente</a>
                </div>
                <div class="divide-y divide-zinc-100">
                    @forelse ($recentCustomers as $customer)
                        <div class="flex items-center justify-between gap-4 px-5 py-4">
                            <div>
                                <p class="font-medium">{{ $customer->name }}</p>
                                <p class="text-sm text-zinc-500">{{ $customer->phone }}</p>
                            </div>
                            <span class="rounded-full bg-zinc-100 px-3 py-1 text-sm">{{ $customer->vehicles_count }} veiculo(s)</span>
                        </div>
                    @empty
                        <p class="px-5 py-8 text-sm text-zinc-500">Nenhum cliente cadastrado ainda.</p>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
</x-app.layout>
