<x-app.layout heading="Veículos" title="Veículos · AutoFlow">
    <div class="space-y-5">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" data-tour="vehicles-header">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Garagem dos clientes</p>
                    <h2 class="mt-1 text-2xl font-black text-slate-950">Veículos cadastrados</h2>
                    <p class="mt-1 text-sm text-slate-500">Busque por placa, modelo, marca ou cliente responsavel.</p>
                </div>
                <a href="{{ route('vehicles.create') }}" class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-800">Novo veiculo</a>
            </div>

            <form method="GET" class="mt-5 grid gap-3 md:grid-cols-[1fr_auto]" data-tour="vehicles-search">
                <label class="block">
                    <span class="sr-only">Buscar veiculo</span>
                    <input name="search" value="{{ $search }}" placeholder="Buscar por placa, modelo, marca ou cliente" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm uppercase shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </label>
                <div class="flex gap-2">
                    <button class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Buscar</button>
                    @if ($search !== '')
                        <a href="{{ route('vehicles.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-500 hover:bg-slate-50">Limpar</a>
                    @endif
                </div>
            </form>
        </section>

        <section class="grid gap-3 md:grid-cols-3" data-tour="vehicles-indicators">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Veículos na lista</p>
                <p class="mt-2 text-3xl font-black text-slate-950">{{ $vehicles->total() }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Clientes nesta página</p>
                <p class="mt-2 text-3xl font-black text-blue-700">{{ $vehicles->getCollection()->pluck('customer_id')->unique()->count() }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Página atual</p>
                <p class="mt-2 text-3xl font-black text-emerald-700">{{ $vehicles->count() }}</p>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" data-tour="vehicles-list">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-black text-slate-950">Lista de veiculos</h2>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse ($vehicles as $vehicle)
                    <article class="grid gap-4 px-5 py-4 md:grid-cols-[150px_1fr_220px_auto] md:items-center">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Placa</p>
                            <p class="mt-1 text-lg font-black tracking-wide text-slate-950">{{ $vehicle->plate }}</p>
                        </div>
                        <div class="min-w-0">
                            <p class="truncate font-black text-slate-950">{{ $vehicle->brand }} {{ $vehicle->model }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $vehicle->color }} · {{ ucfirst($vehicle->type) }}</p>
                        </div>
                        <div class="text-sm text-slate-600">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Cliente</p>
                            <p class="mt-1 truncate font-bold text-slate-900">{{ $vehicle->customer->name }}</p>
                        </div>
                        <div class="flex justify-start md:justify-end">
                            <a href="{{ route('vehicles.edit', $vehicle) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Editar</a>
                        </div>
                    </article>
                @empty
                    <div class="px-5 py-12 text-center">
                        <p class="font-black text-slate-950">Nenhum veiculo encontrado</p>
                        <p class="mt-1 text-sm text-slate-500">Cadastre o primeiro veiculo ou ajuste a busca atual.</p>
                        <a href="{{ route('vehicles.create') }}" class="mt-4 inline-flex rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white">Novo veiculo</a>
                    </div>
                @endforelse
            </div>
        </section>

        <div>{{ $vehicles->links() }}</div>
    </div>

    @php
        $vehiclesTour = [
            'key' => 'vehicles.index.v1',
            'title' => 'Gerenciando veículos',
            'steps' => [
                [
                    'target' => '[data-tour="vehicles-header"]',
                    'title' => 'Garagem dos clientes',
                    'body' => 'Aqui ficam os veículos vinculados aos clientes. Esse vínculo é usado na abertura da lavagem para evitar escolher o carro errado.',
                ],
                [
                    'target' => '[data-tour="vehicles-search"]',
                    'title' => 'Busca por veículo',
                    'body' => 'Pesquise por placa, modelo, marca ou cliente responsável para encontrar rapidamente o cadastro correto.',
                ],
                [
                    'target' => '[data-tour="vehicles-indicators"]',
                    'title' => 'Resumo da listagem',
                    'body' => 'Estes números ajudam a conferir quantos veículos existem na busca atual e quantos clientes aparecem nesta página.',
                ],
                [
                    'target' => '[data-tour="vehicles-list"]',
                    'title' => 'Lista de veículos',
                    'body' => 'Cada linha mostra placa, marca, modelo, cor, tipo e cliente vinculado. Use Editar para corrigir dados do veículo.',
                ],
            ],
        ];
    @endphp
    <script type="application/json" data-onboarding-tour>
        {!! json_encode($vehiclesTour, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
</x-app.layout>
