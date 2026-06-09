<x-app.layout heading="Servicos" title="Servicos · AutoFlow">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex w-full gap-2 sm:w-auto">
            <input name="search" value="{{ $search }}" placeholder="Buscar por nome ou categoria" class="w-full rounded-md border border-zinc-300 px-3 py-2 sm:w-80">
            <button class="rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold">Buscar</button>
        </form>
        <a href="{{ route('services.create') }}" class="rounded-md bg-cyan-700 px-4 py-2 text-sm font-semibold text-white">Novo servico</a>
    </div>

    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white">
        <table class="min-w-full divide-y divide-zinc-200">
            <thead class="bg-zinc-100 text-left text-sm text-zinc-600">
                <tr>
                    <th class="px-4 py-3">Servico</th>
                    <th class="px-4 py-3">Categoria</th>
                    <th class="px-4 py-3">Preco</th>
                    <th class="px-4 py-3">Tempo</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 text-sm">
                @forelse ($services as $service)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $service->name }}</td>
                        <td class="px-4 py-3">{{ $service->category }}</td>
                        <td class="px-4 py-3">R$ {{ number_format((float) $service->base_price, 2, ',', '.') }}</td>
                        <td class="px-4 py-3">{{ $service->estimated_minutes }} min</td>
                        <td class="px-4 py-3"><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $service->active ? 'bg-emerald-100 text-emerald-800' : 'bg-zinc-100 text-zinc-600' }}">{{ $service->active ? 'Ativo' : 'Inativo' }}</span></td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('services.edit', $service) }}" class="font-semibold text-cyan-700">Editar</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-zinc-500">Nenhum servico encontrado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $services->links() }}</div>
</x-app.layout>
