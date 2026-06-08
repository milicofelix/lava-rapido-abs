<x-app.layout heading="Dashboard" title="Dashboard · Lava Rapido ABS">
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Clientes', 'value' => $customerCount],
            ['label' => 'Veiculos', 'value' => $vehicleCount],
            ['label' => 'Servicos', 'value' => $serviceCount],
            ['label' => 'Servicos ativos', 'value' => $activeServiceCount],
        ] as $card)
            <div class="rounded-lg border border-zinc-200 bg-white p-5">
                <p class="text-sm text-zinc-500">{{ $card['label'] }}</p>
                <p class="mt-2 text-3xl font-semibold">{{ $card['value'] }}</p>
            </div>
        @endforeach
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
