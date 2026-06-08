<x-app.layout heading="Lavagens" title="Lavagens · Lava Rapido ABS">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex w-full flex-wrap gap-2 sm:w-auto">
            <input name="search" value="{{ $search }}" placeholder="Buscar por codigo, cliente ou placa" class="w-full rounded-md border border-zinc-300 px-3 py-2 sm:w-80">
            <select name="status" class="rounded-md border border-zinc-300 px-3 py-2">
                <option value="">Todos os status</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold">Filtrar</button>
        </form>
        <a href="{{ route('wash-orders.create') }}" class="rounded-md bg-cyan-700 px-4 py-2 text-sm font-semibold text-white">Nova lavagem</a>
    </div>

    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white">
        <table class="min-w-full divide-y divide-zinc-200">
            <thead class="bg-zinc-100 text-left text-sm text-zinc-600">
                <tr>
                    <th class="px-4 py-3">Codigo</th>
                    <th class="px-4 py-3">Cliente</th>
                    <th class="px-4 py-3">Veiculo</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Previsao</th>
                    <th class="px-4 py-3">Total</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 text-sm">
                @forelse ($washOrders as $washOrder)
                    <tr>
                        <td class="px-4 py-3 font-semibold">{{ $washOrder->code }}</td>
                        <td class="px-4 py-3">{{ $washOrder->customer->name }}</td>
                        <td class="px-4 py-3">{{ $washOrder->vehicle->plate }}<br><span class="text-zinc-500">{{ $washOrder->vehicle->brand }} {{ $washOrder->vehicle->model }}</span></td>
                        <td class="px-4 py-3">@include('app.wash-orders._status-badge', ['status' => $washOrder->status, 'label' => $washOrder->statusLabel()])</td>
                        <td class="px-4 py-3">{{ $washOrder->estimated_completion_at?->format('H:i') ?? '-' }}</td>
                        <td class="px-4 py-3">R$ {{ number_format((float) $washOrder->total_amount, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('wash-orders.show', $washOrder) }}" class="font-semibold text-cyan-700">Abrir</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-zinc-500">Nenhuma lavagem encontrada.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $washOrders->links() }}</div>
</x-app.layout>
