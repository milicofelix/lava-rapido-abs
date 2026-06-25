<x-app.layout heading="Servicos" title="Servicos · AutoFlow">
    <div class="space-y-5">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Catalogo operacional</p>
                    <h2 class="mt-1 text-2xl font-black text-slate-950">Servicos cadastrados</h2>
                    <p class="mt-1 text-sm text-slate-500">Organize os servicos, precos, tempo estimado e disponibilidade para novas lavagens.</p>
                </div>
                <a href="{{ route('services.create') }}" class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-800">Novo servico</a>
            </div>

            <form method="GET" class="mt-5 grid gap-3 md:grid-cols-[1fr_auto]">
                <label class="block">
                    <span class="sr-only">Buscar servico</span>
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

        <section class="grid gap-3 md:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Servicos na lista</p>
                <p class="mt-2 text-3xl font-black text-slate-950">{{ $services->total() }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Ativos nesta pagina</p>
                <p class="mt-2 text-3xl font-black text-emerald-700">{{ $services->getCollection()->where('active', true)->count() }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Preco medio</p>
                <p class="mt-2 text-3xl font-black text-blue-700">
                    R$ {{ number_format((float) $services->getCollection()->avg('base_price'), 2, ',', '.') }}
                </p>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-black text-slate-950">Lista de servicos</h2>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse ($services as $service)
                    <article class="grid gap-4 px-5 py-4 md:grid-cols-[1fr_160px_130px_120px_auto] md:items-center">
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
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Preco</p>
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
                        <p class="font-black text-slate-950">Nenhum servico encontrado</p>
                        <p class="mt-1 text-sm text-slate-500">Cadastre o primeiro servico ou ajuste a busca atual.</p>
                        <a href="{{ route('services.create') }}" class="mt-4 inline-flex rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white">Novo servico</a>
                    </div>
                @endforelse
            </div>
        </section>

        <div>{{ $services->links() }}</div>
    </div>
</x-app.layout>
