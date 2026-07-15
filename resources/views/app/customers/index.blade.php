<x-app.layout heading="Clientes" title="Clientes · AutoFlow">
    <div class="space-y-5">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" data-tour="customers-header">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Relacionamento</p>
                    <h2 class="mt-1 text-2xl font-black text-slate-950">Clientes cadastrados</h2>
                    <p class="mt-1 text-sm text-slate-500">Localize clientes por nome, telefone ou placa vinculada.</p>
                </div>
                <a href="{{ route('customers.create') }}" class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-800">Novo cliente</a>
            </div>

            <form method="GET" class="mt-5 grid gap-3 md:grid-cols-[1fr_auto]" data-tour="customers-search">
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

        @if (session('import_summary'))
            @php
                $importSummary = session('import_summary');
            @endphp
            <section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm" data-tour="customers-import-result">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-700">Importação</p>
                        <h2 class="mt-1 font-black text-emerald-950">Resumo do arquivo</h2>
                        <p class="mt-1 text-sm font-semibold text-emerald-800">
                            {{ $importSummary['imported_rows'] }} linha(s) importada(s), {{ $importSummary['skipped_rows'] }} ignorada(s).
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-sm font-bold text-emerald-950 sm:grid-cols-4">
                        <span class="rounded-xl bg-white px-3 py-2">{{ $importSummary['created_customers'] }} clientes novos</span>
                        <span class="rounded-xl bg-white px-3 py-2">{{ $importSummary['updated_customers'] }} atualizados</span>
                        <span class="rounded-xl bg-white px-3 py-2">{{ $importSummary['created_vehicles'] }} veículos novos</span>
                        <span class="rounded-xl bg-white px-3 py-2">{{ $importSummary['updated_vehicles'] }} atualizados</span>
                    </div>
                </div>
                @if (! empty($importSummary['errors']))
                    <div class="mt-4 rounded-2xl border border-amber-200 bg-white p-4">
                        <p class="text-sm font-black text-amber-800">Linhas não importadas</p>
                        <ul class="mt-2 space-y-1 text-sm font-semibold text-amber-700">
                            @foreach (array_slice($importSummary['errors'], 0, 5) as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        @if (count($importSummary['errors']) > 5)
                            <p class="mt-2 text-xs font-bold text-amber-700">Mais {{ count($importSummary['errors']) - 5 }} erro(s) oculto(s).</p>
                        @endif
                    </div>
                @endif
            </section>
        @endif

        <section class="grid gap-3 md:grid-cols-3" data-tour="customers-indicators">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Clientes na lista</p>
                <p class="mt-2 text-3xl font-black text-slate-950">{{ $customers->total() }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Veículos nesta página</p>
                <p class="mt-2 text-3xl font-black text-blue-700">{{ $customers->getCollection()->sum('vehicles_count') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-bold text-slate-500">Página atual</p>
                <p class="mt-2 text-3xl font-black text-emerald-700">{{ $customers->count() }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-blue-100 bg-blue-50 p-5 shadow-sm" data-tour="customers-import">
            <div class="grid gap-5 lg:grid-cols-[1fr_420px] lg:items-start">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Importação em lote</p>
                    <h2 class="mt-1 font-black text-blue-950">Clientes e veículos por CSV</h2>
                    <p class="mt-1 text-sm font-semibold text-blue-800">Use para cadastrar uma carteira antiga sem digitar cliente por cliente. A placa é opcional; quando informada, marca e modelo precisam existir no catálogo de veículos.</p>
                    <div class="mt-3 rounded-2xl bg-white p-3 text-xs font-bold text-slate-600">
                        Cabeçalho aceito: <span class="text-slate-950">nome,telefone,email,observacao,placa,marca,modelo,cor,observacao_veiculo</span>
                    </div>
                    <a href="{{ route('customers.import-template') }}" class="mt-3 inline-flex rounded-xl border border-blue-200 bg-white px-4 py-2 text-sm font-black text-blue-700 hover:bg-blue-100">Baixar modelo CSV</a>
                </div>
                <form method="POST" action="{{ route('customers.import') }}" enctype="multipart/form-data" class="rounded-2xl bg-white p-4 shadow-sm">
                    @csrf
                    <label class="block">
                        <span class="text-sm font-black text-blue-950">Arquivo CSV</span>
                        <input type="file" name="customers_file" accept=".csv,text/csv,text/plain" class="mt-2 w-full rounded-xl border border-blue-100 bg-blue-50 px-3 py-2.5 text-sm font-bold text-blue-950 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-700 file:px-3 file:py-2 file:text-sm file:font-bold file:text-white">
                        @error('customers_file') <span class="mt-1 block text-sm font-semibold text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <button class="mt-4 w-full rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-800">Importar clientes</button>
                </form>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" data-tour="customers-list">
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
                            <p class="truncate">{{ $customer->email ?: 'E-mail não informado' }}</p>
                        </div>
                        <div>
                            @php
                                $progress = $customer->loyalty_progress;
                            @endphp
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

    @php
        $customersTour = [
            'key' => 'customers.index.v1',
            'title' => 'Gerenciando clientes',
            'steps' => [
                [
                    'target' => '[data-tour="customers-header"]',
                    'title' => 'Carteira de clientes',
                    'body' => 'Aqui você cadastra novos clientes e mantém a base organizada para abrir lavagens com menos erro.',
                ],
                [
                    'target' => '[data-tour="customers-search"]',
                    'title' => 'Busca rápida',
                    'body' => 'Pesquise por nome, telefone ou placa. Isso ajuda a encontrar o cliente certo antes de editar ou abrir uma lavagem.',
                ],
                [
                    'target' => '[data-tour="customers-import-result"]',
                    'title' => 'Resumo da importação',
                    'body' => 'Depois de importar um CSV, o sistema mostra quantos clientes e veículos foram criados, atualizados ou ignorados.',
                ],
                [
                    'target' => '[data-tour="customers-indicators"]',
                    'title' => 'Indicadores da lista',
                    'body' => 'Use estes números para conferir o tamanho da busca atual, veículos carregados na página e quantidade exibida.',
                ],
                [
                    'target' => '[data-tour="customers-import"]',
                    'title' => 'Importação em lote',
                    'body' => 'Use o modelo CSV para trazer uma carteira antiga de clientes e veículos. Isso evita cadastrar tudo manualmente.',
                ],
                [
                    'target' => '[data-tour="customers-list"]',
                    'title' => 'Lista de clientes',
                    'body' => 'A lista mostra contato, progresso de fidelidade, quantidade de veículos e o botão para editar o cadastro.',
                ],
            ],
        ];
    @endphp
    <script type="application/json" data-onboarding-tour>
        {!! json_encode($customersTour, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
</x-app.layout>
