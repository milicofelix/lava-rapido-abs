<x-app.layout heading="Unidades" title="Unidades · AutoFlow">
    <div class="space-y-5">
        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5 text-sm text-blue-950">
            <p class="font-bold">Painel comercial do Super Admin</p>
            <p class="mt-1">Gerencie o ciclo comercial das unidades: trial, assinatura, suspensão e reativação.</p>
        </div>

        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
                <p class="font-bold">Revise os dados enviados.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="grid gap-4 md:grid-cols-5">
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <p class="text-sm text-slate-500">Trial</p>
                <p class="mt-2 text-2xl font-black text-amber-700">{{ $summary['trial'] }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <p class="text-sm text-slate-500">Assinantes</p>
                <p class="mt-2 text-2xl font-black text-emerald-700">{{ $summary['active'] }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <p class="text-sm text-slate-500">Expiradas</p>
                <p class="mt-2 text-2xl font-black text-rose-700">{{ $summary['expired'] }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <p class="text-sm text-slate-500">Suspensas</p>
                <p class="mt-2 text-2xl font-black text-slate-700">{{ $summary['suspended'] }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <p class="text-sm text-slate-500">Total</p>
                <p class="mt-2 text-2xl font-black text-slate-950">{{ $summary['total'] }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <form method="GET" class="grid gap-3 lg:grid-cols-[1fr_240px_auto_auto]">
                <input name="search" value="{{ $search }}" placeholder="Buscar por unidade, dono, e-mail, endereço ou cidade" class="rounded-xl border border-slate-300 px-4 py-2 text-sm">
                <select name="status" class="rounded-xl border border-slate-300 px-4 py-2 text-sm">
                    <option value="">Todos os status</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="rounded-xl bg-slate-950 px-5 py-2 text-sm font-bold text-white">Filtrar</button>
                <a href="{{ route('super-admin.locations.index') }}" class="rounded-xl border border-slate-300 px-5 py-2 text-center text-sm font-bold text-slate-700">Limpar</a>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-black text-slate-950">Unidades cadastradas</h2>
                <p class="mt-1 text-sm text-slate-500">Controle comercial manual antes da integração com pagamento real.</p>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse ($locations as $location)
                    @php
                        $owner = $location->owners->first();
                        $subscriptionStatus = $location->subscriptionStatus();
                        $badgeClass = match ($subscriptionStatus) {
                            \App\Models\WashLocation::ACCOUNT_STATUS_ACTIVE => 'bg-emerald-100 text-emerald-700',
                            \App\Models\WashLocation::ACCOUNT_STATUS_EXPIRED => 'bg-rose-100 text-rose-700',
                            \App\Models\WashLocation::ACCOUNT_STATUS_SUSPENDED => 'bg-slate-200 text-slate-700',
                            default => 'bg-amber-100 text-amber-800',
                        };
                    @endphp
                    <article class="grid gap-5 px-5 py-5 xl:grid-cols-[1fr_360px]">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-lg font-black text-slate-950">{{ $location->name }}</h3>
                                <span class="rounded-full px-3 py-1 text-xs font-black {{ $badgeClass }}">{{ $location->accountStatusLabel() }}</span>
                                @if ($location->blocked_at)
                                    <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-black text-rose-700">Bloqueada</span>
                                @else
                                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-700">Operação liberada</span>
                                @endif
                            </div>

                            <dl class="mt-4 grid gap-3 text-sm text-slate-600 md:grid-cols-2 lg:grid-cols-3">
                                <div>
                                    <dt class="text-xs font-bold uppercase text-slate-400">Dono</dt>
                                    <dd class="mt-1 font-semibold text-slate-800">{{ $owner?->name ?? 'Sem dono vinculado' }}</dd>
                                    @if ($owner?->email)
                                        <dd class="text-xs text-slate-500">{{ $owner->email }}</dd>
                                    @endif
                                </div>
                                <div>
                                    <dt class="text-xs font-bold uppercase text-slate-400">Cidade</dt>
                                    <dd class="mt-1 font-semibold text-slate-800">{{ $location->city }}</dd>
                                    <dd class="text-xs text-slate-500">{{ $location->fullAddress() }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-bold uppercase text-slate-400">Trial até</dt>
                                    <dd class="mt-1 font-semibold text-slate-800">{{ $location->trial_ends_at?->format('d/m/Y') ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-bold uppercase text-slate-400">Assinatura até</dt>
                                    <dd class="mt-1 font-semibold text-slate-800">{{ $location->subscription_ends_at?->format('d/m/Y') ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-bold uppercase text-slate-400">Usuários</dt>
                                    <dd class="mt-1 font-semibold text-slate-800">{{ $location->users_count }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-bold uppercase text-slate-400">Mapa público</dt>
                                    <dd class="mt-1 font-semibold text-slate-800">{{ $location->public_visible ? 'Visível' : 'Oculto' }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div class="space-y-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <form method="POST" action="{{ route('super-admin.locations.extend-trial', ['washLocation' => $location->id]) }}" class="grid gap-2 sm:grid-cols-[1fr_auto]">
                                @csrf
                                @method('PATCH')
                                <select name="days" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                    <option value="7">Prorrogar trial +7 dias</option>
                                    <option value="15">Prorrogar trial +15 dias</option>
                                    <option value="30">Prorrogar trial +30 dias</option>
                                </select>
                                <button class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-bold text-amber-800 hover:bg-amber-100">Prorrogar</button>
                            </form>

                            <form method="POST" action="{{ route('super-admin.locations.activate-subscription', ['washLocation' => $location->id]) }}" class="grid gap-2 sm:grid-cols-[1fr_auto]">
                                @csrf
                                @method('PATCH')
                                <select name="plan_id" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                    <option value="">Selecionar plano</option>
                                    @foreach ($plans as $plan)
                                        <option value="{{ $plan->id }}">{{ $plan->name }} - {{ $plan->formattedPrice() }}</option>
                                    @endforeach
                                </select>
                                <input type="date" name="subscription_ends_at" value="{{ now()->addMonth()->format('Y-m-d') }}" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                <button class="rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-bold text-emerald-800 hover:bg-emerald-100">Ativar</button>
                            </form>

                            <div class="grid gap-2 sm:grid-cols-2">
                                <form method="POST" action="{{ route('super-admin.locations.suspend', ['washLocation' => $location->id]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="w-full rounded-xl border border-rose-300 bg-white px-4 py-2 text-sm font-bold text-rose-700 hover:bg-rose-50">Suspender</button>
                                </form>
                                <form method="POST" action="{{ route('super-admin.locations.reactivate', ['washLocation' => $location->id]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="w-full rounded-xl border border-blue-300 bg-white px-4 py-2 text-sm font-bold text-blue-700 hover:bg-blue-50">Reativar</button>
                                </form>
                            </div>
                        </div>
                    </article>
                @empty
                    <p class="px-5 py-10 text-center text-sm text-slate-500">Nenhuma unidade encontrada.</p>
                @endforelse
            </div>

            <div class="border-t border-slate-200 px-5 py-4">
                {{ $locations->links() }}
            </div>
        </section>
    </div>
</x-app.layout>
