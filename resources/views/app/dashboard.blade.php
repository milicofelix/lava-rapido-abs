<x-app.layout heading="Dashboard" title="Dashboard · AutoFlow">
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Clientes', 'value' => $customerCount],
            ['label' => 'Veiculos', 'value' => $vehicleCount],
            ['label' => 'Servicos', 'value' => $serviceCount],
            ['label' => 'Servicos ativos', 'value' => $activeServiceCount],
            ['label' => 'Lavagens hoje', 'value' => $washOrdersToday],
            ['label' => 'Em andamento', 'value' => $activeWashOrders],
            ['label' => 'Prontas', 'value' => $readyWashOrders],
        ] as $card)
            <div class="rounded-lg border border-zinc-200 bg-white p-5">
                <p class="text-sm text-zinc-500">{{ $card['label'] }}</p>
                <p class="mt-2 text-3xl font-semibold">{{ $card['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 rounded-lg border border-zinc-200 bg-white">
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
    </div>

    <div class="mt-6 rounded-lg border border-zinc-200 bg-white">
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
</x-app.layout>
