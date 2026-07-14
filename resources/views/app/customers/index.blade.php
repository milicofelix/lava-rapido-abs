<x-app.layout heading="Clientes" title="Clientes · AutoFlow">
    <div class="space-y-5">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Relacionamento</p>
                    <h2 class="mt-1 text-2xl font-black text-slate-950">Clientes cadastrados</h2>
                    <p class="mt-1 text-sm text-slate-500">Localize clientes por nome, telefone ou placa vinculada.</p>
                </div>
                <a href="{{ route('customers.create') }}" class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-800">Novo cliente</a>
            </div>

            <form method="GET" class="mt-5 grid gap-3 md:grid-cols-[1fr_auto]">
                <label class="block">
                    <span class="sr-only">Buscar cliente</span>
                    <input name="search" value="{{ $search }}" placeholder="Buscar por nome, telefone ou placa" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </label>
                <div class="flex gap-2">
                    <button class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Buscar</button>
                    @if ($search !== '')
                        <a href="{{ route('customers.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-500 hover:bg-slate-50">Limpar</a>
                    @endif
                </div>
            </form>
        </section>

        <section class="grid gap-3 md:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Clientes na lista</p>
                <p class="mt-2 text-3xl font-black text-slate-950">{{ $customers->total() }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Veiculos nesta pagina</p>
                <p class="mt-2 text-3xl font-black text-blue-700">{{ $customers->getCollection()->sum('vehicles_count') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Pagina atual</p>
                <p class="mt-2 text-3xl font-black text-emerald-700">{{ $customers->count() }}</p>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-black text-slate-950">Lista de clientes</h2>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse ($customers as $customer)
                    <article class="grid gap-4 px-5 py-4 md:grid-cols-[1fr_220px_190px_120px_auto] md:items-center">
                        <div class="min-w-0">
                            <p class="truncate font-black text-slate-950">{{ $customer->name }}</p>
                            @if ($customer->notes)
                                <p class="mt-1 line-clamp-1 text-sm text-slate-500">{{ $customer->notes }}</p>
                            @endif
                        </div>
                        <div class="text-sm text-slate-600">
                            <p class="font-bold text-slate-900">{{ $customer->phone }}</p>
                            <p class="truncate">{{ $customer->email ?: 'E-mail nao informado' }}</p>
                        </div>
                        <div>
                            @php($progress = $customer->loyalty_progress)
                            @if ($progress['enabled'])
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-xs font-black uppercase tracking-[0.12em] text-slate-500">Fidelidade</p>
                                    @if ($progress['has_active_coupon'])
                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-black text-emerald-700">Cupom disponível</span>
                                    @endif
                                </div>
                                <div class="mt-1 h-2 rounded-full bg-slate-100">
                                    <div class="h-2 rounded-full bg-fuchsia-600" style="width: {{ $progress['percent'] }}%"></div>
                                </div>
                                <p class="mt-1 text-xs font-bold text-slate-600">{{ $progress['current'] }}/{{ $progress['threshold'] }} lavadas para o próximo · {{ $progress['active_coupons'] }} ativo(s)</p>
                            @else
                                <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-500">Fidelidade off</span>
                            @endif
                        </div>
                        <div>
                            <span class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-black text-blue-700">{{ $customer->vehicles_count }} veiculo{{ $customer->vehicles_count === 1 ? '' : 's' }}</span>
                        </div>
                        <div class="flex justify-start md:justify-end">
                            <a href="{{ route('customers.edit', $customer) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Editar</a>
                        </div>
                    </article>
                @empty
                    <div class="px-5 py-12 text-center">
                        <p class="font-black text-slate-950">Nenhum cliente encontrado</p>
                        <p class="mt-1 text-sm text-slate-500">Cadastre o primeiro cliente ou ajuste a busca atual.</p>
                        <a href="{{ route('customers.create') }}" class="mt-4 inline-flex rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white">Novo cliente</a>
                    </div>
                @endforelse
            </div>
        </section>

        <div>{{ $customers->links() }}</div>
    </div>
</x-app.layout>
