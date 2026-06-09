<x-app.layout heading="Veiculos" title="Veiculos · AutoFlow">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex w-full gap-2 sm:w-auto">
            <input name="search" value="{{ $search }}" placeholder="Buscar por placa, modelo, marca ou cliente" class="w-full rounded-md border border-zinc-300 px-3 py-2 sm:w-96">
            <button class="rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold">Buscar</button>
        </form>
        <a href="{{ route('vehicles.create') }}" class="rounded-md bg-cyan-700 px-4 py-2 text-sm font-semibold text-white">Novo veiculo</a>
    </div>

    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white">
        <table class="min-w-full divide-y divide-zinc-200">
            <thead class="bg-zinc-100 text-left text-sm text-zinc-600">
                <tr>
                    <th class="px-4 py-3">Placa</th>
                    <th class="px-4 py-3">Veiculo</th>
                    <th class="px-4 py-3">Cliente</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 text-sm">
                @forelse ($vehicles as $vehicle)
                    <tr>
                        <td class="px-4 py-3 font-semibold">{{ $vehicle->plate }}</td>
                        <td class="px-4 py-3">{{ $vehicle->brand }} {{ $vehicle->model }}<br><span class="text-zinc-500">{{ $vehicle->color }} · {{ ucfirst($vehicle->type) }}</span></td>
                        <td class="px-4 py-3">{{ $vehicle->customer->name }}</td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('vehicles.edit', $vehicle) }}" class="font-semibold text-cyan-700">Editar</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-zinc-500">Nenhum veiculo encontrado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $vehicles->links() }}</div>
</x-app.layout>
