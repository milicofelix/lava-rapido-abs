<x-app.layout heading="Serviços" title="Serviços · AutoFlow">
    <div class="space-y-5">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" data-tour="services-header">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Catálogo operacional</p>
                    <h2 class="mt-1 text-2xl font-black text-slate-950">Serviços cadastrados</h2>
                    <p class="mt-1 text-sm text-slate-500">Organize os serviços, preços, tempo estimado e disponibilidade para novas lavagens.</p>
                </div>
                <a href="{{ route('services.create') }}" class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-800" data-tour="services-create">Novo serviço</a>
            </div>

            <form method="GET" class="mt-5 grid gap-3 md:grid-cols-[1fr_auto]" data-tour="services-search">
                <label class="block">
                    <span class="sr-only">Buscar serviço</span>
                    <input name="search" value="{{ $search }}" placeholder="Buscar por nome ou categoria" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </label>
                <div class="flex gap-2">
                    <button class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Buscar</button>
                    @if ($search !== '')
                        <a href="{{ route('services.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-500 hover:bg-slate-50">Limpar</a>
                    @endif
                </div>
            </form>
        </section>

        <section class="grid gap-3 md:grid-cols-3" data-tour="services-indicators">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Serviços na lista</p>
                <p class="mt-2 text-3xl font-black text-slate-950">{{ $services->total() }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Ativos nesta página</p>
                <p class="mt-2 text-3xl font-black text-emerald-700">{{ $services->getCollection()->where('active', true)->count() }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Preço médio</p>
                <p class="mt-2 text-3xl font-black text-blue-700">
                    R$ {{ number_format((float) $services->getCollection()->avg('base_price'), 2, ',', '.') }}
                </p>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" data-tour="services-list">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-black text-slate-950">Lista de serviços</h2>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse ($services as $service)
                    <article class="grid gap-4 px-5 py-4 md:grid-cols-[1fr_160px_130px_120px_auto] md:items-center" @if ($loop->first) data-tour="services-row" @endif>
                        <div class="min-w-0">
                            <p class="truncate font-black text-slate-950">{{ $service->name }}</p>
                            @if ($service->description)
                                <p class="mt-1 line-clamp-1 text-sm text-slate-500">{{ $service->description }}</p>
                            @endif
                        </div>
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Categoria</p>
                            <p class="mt-1 truncate text-sm font-bold text-slate-900">{{ $service->category }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Preço</p>
                            <p class="mt-1 text-sm font-black text-slate-950">R$ {{ number_format((float) $service->base_price, 2, ',', '.') }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-black text-blue-700">{{ $service->estimated_minutes }} min</span>
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-black {{ $service->active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $service->active ? 'Ativo' : 'Inativo' }}</span>
                        </div>
                        <div class="flex justify-start md:justify-end">
                            <a href="{{ route('services.edit', $service) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Editar</a>
                        </div>
                    </article>
                @empty
                    <div class="px-5 py-12 text-center">
                        <p class="font-black text-slate-950">Nenhum serviço encontrado</p>
                        <p class="mt-1 text-sm text-slate-500">Cadastre o primeiro serviço ou ajuste a busca atual.</p>
                        <a href="{{ route('services.create') }}" class="mt-4 inline-flex rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white">Novo serviço</a>
                    </div>
                @endforelse
            </div>
        </section>

        <div>{{ $services->links() }}</div>
    </div>

    @php
        $servicesTour = [
            'key' => 'services.index.v1',
            'title' => 'Catálogo de serviços',
            'steps' => [
                [
                    'target' => '[data-tour="services-header"]',
                    'title' => 'Catálogo operacional',
                    'body' => 'Esta tela reúne os serviços que poderão ser escolhidos na abertura de uma lavagem.',
                ],
                [
                    'target' => '[data-tour="services-create"]',
                    'title' => 'Novo serviço',
                    'body' => 'Use este botão para cadastrar novas opções, como lavagem completa, cera, polimento ou higienização.',
                ],
                [
                    'target' => '[data-tour="services-search"]',
                    'title' => 'Busca rápida',
                    'body' => 'Procure por nome ou categoria para encontrar serviços em catálogos maiores.',
                ],
                [
                    'target' => '[data-tour="services-indicators"]',
                    'title' => 'Indicadores do catálogo',
                    'body' => 'Acompanhe a quantidade de serviços, quantos estão ativos nesta página e o preço médio exibido.',
                ],
                [
                    'target' => '[data-tour="services-list"]',
                    'title' => 'Lista de serviços',
                    'body' => 'Cada linha mostra nome, categoria, preço, tempo estimado e se o serviço está disponível para novas lavagens.',
                ],
                [
                    'target' => '[data-tour="services-row"]',
                    'title' => 'Editar serviço',
                    'body' => 'Edite um serviço quando precisar ajustar preço, tempo, categoria, descrição ou disponibilidade.',
                ],
            ],
        ];
    @endphp
    <script type="application/json" data-onboarding-tour>
        {!! json_encode($servicesTour, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
</x-app.layout>
