<x-app.layout heading="Planos" title="Planos · AutoFlow">
    <div class="space-y-5">
        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5 text-sm text-blue-950" data-tour="super-plans-intro">
            <p class="font-bold">Planos comerciais</p>
            <p class="mt-1">Crie, edite e desative planos sem alterar codigo. Estes planos aparecem para o dono escolher a assinatura.</p>
        </div>

        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @include('app.components.errors')

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" data-tour="super-plans-create">
            <h2 class="text-lg font-black text-slate-950">Novo plano</h2>
            <form method="POST" action="{{ route('super-admin.plans.store') }}" class="mt-4 grid gap-3 lg:grid-cols-[1fr_180px_160px_auto_auto] lg:items-end" data-tour="super-plans-create-fields">
                @csrf
                <label class="block">
                    <span class="text-sm font-semibold text-slate-700">Nome</span>
                    <input name="name" value="{{ old('name') }}" required placeholder="Starter" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </label>
                <label class="block">
                    <span class="text-sm font-semibold text-slate-700">Preço</span>
                    <input name="price" type="number" step="0.01" min="0" value="{{ old('price') }}" required placeholder="49.90" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </label>
                <label class="block">
                    <span class="text-sm font-semibold text-slate-700">Trial</span>
                    <input name="trial_days" type="number" min="0" max="365" value="{{ old('trial_days', 15) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </label>
                <label class="flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold">
                    <input type="checkbox" name="is_active" value="1" checked class="h-4 w-4 rounded border-slate-300 text-blue-700">
                    Ativo
                </label>
                <button class="rounded-xl bg-blue-700 px-5 py-2 text-sm font-bold text-white hover:bg-blue-800">Criar plano</button>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" data-tour="super-plans-list">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-black text-slate-950">Planos cadastrados</h2>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($plans as $plan)
                    <article class="grid gap-4 px-5 py-5 xl:grid-cols-[1fr_auto]" @if ($loop->first) data-tour="super-plans-row" @endif>
                        <form method="POST" action="{{ route('super-admin.plans.update', $plan) }}" class="grid gap-3 lg:grid-cols-[1fr_180px_160px_auto] lg:items-end">
                            @csrf
                            @method('PUT')
                            <label class="block">
                                <span class="text-sm font-semibold text-slate-700">Nome</span>
                                <input name="name" value="{{ old('name', $plan->name) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            </label>
                            <label class="block">
                                <span class="text-sm font-semibold text-slate-700">Preço</span>
                                <input name="price" type="number" step="0.01" min="0" value="{{ old('price', $plan->price) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            </label>
                            <label class="block">
                                <span class="text-sm font-semibold text-slate-700">Trial</span>
                                <input name="trial_days" type="number" min="0" max="365" value="{{ old('trial_days', $plan->trial_days) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            </label>
                            <label class="flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold">
                                <input type="checkbox" name="is_active" value="1" @checked($plan->is_active) class="h-4 w-4 rounded border-slate-300 text-blue-700">
                                Ativo
                            </label>
                            <button class="rounded-xl border border-blue-300 bg-blue-50 px-4 py-2 text-sm font-bold text-blue-800 hover:bg-blue-100">Salvar</button>
                        </form>

                        <div class="flex items-center gap-3" @if ($loop->first) data-tour="super-plans-status-actions" @endif>
                            <span class="rounded-full px-3 py-1 text-xs font-black {{ $plan->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                                {{ $plan->is_active ? 'Ativo' : 'Inativo' }}
                            </span>
                            @if ($plan->is_active)
                                <form method="POST" action="{{ route('super-admin.plans.deactivate', $plan) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="rounded-xl border border-rose-300 px-4 py-2 text-sm font-bold text-rose-700 hover:bg-rose-50">Desativar</button>
                                </form>
                            @endif
                        </div>
                    </article>
                @empty
                    <p class="px-5 py-10 text-center text-sm text-slate-500">Nenhum plano cadastrado.</p>
                @endforelse
            </div>
        </section>
    </div>

    @php
        $superPlansTour = [
            'key' => 'super-admin.plans.index.v1',
            'title' => 'Planos comerciais',
            'steps' => [
                [
                    'target' => '[data-tour="super-plans-intro"]',
                    'title' => 'Planos do SaaS',
                    'body' => 'Esta tela permite ajustar planos comerciais sem alterar código.',
                ],
                [
                    'target' => '[data-tour="super-plans-create"]',
                    'title' => 'Novo plano',
                    'body' => 'Crie planos que poderão ser escolhidos pelos donos das unidades na assinatura.',
                ],
                [
                    'target' => '[data-tour="super-plans-create-fields"]',
                    'title' => 'Preço e trial',
                    'body' => 'Defina nome, valor, dias de trial e se o plano já nasce ativo.',
                ],
                [
                    'target' => '[data-tour="super-plans-list"]',
                    'title' => 'Planos cadastrados',
                    'body' => 'A lista mostra todos os planos existentes para manutenção comercial.',
                ],
                [
                    'target' => '[data-tour="super-plans-row"]',
                    'title' => 'Edição direta',
                    'body' => 'Edite nome, preço, trial e disponibilidade do plano diretamente na linha.',
                ],
                [
                    'target' => '[data-tour="super-plans-status-actions"]',
                    'title' => 'Ativar ou desativar',
                    'body' => 'Desative planos que não devem mais aparecer para novas escolhas de assinatura.',
                ],
            ],
        ];
    @endphp

    <script type="application/json" data-onboarding-tour>
        {!! json_encode($superPlansTour, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
</x-app.layout>
