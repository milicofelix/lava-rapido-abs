<x-app.layout heading="Solicitações de lava-rápidos" title="Solicitações · AutoFlow">
    <div class="space-y-5">
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-950">
            <p class="font-bold">Área do dono do produto</p>
            <p class="mt-1">Ao aprovar uma solicitação, a unidade nasce em trial e passa a seguir as regras de assinatura do SaaS.</p>
        </div>

        <section class="grid gap-4 md:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <p class="text-sm text-slate-500">Pendentes</p>
                <p class="mt-2 text-2xl font-black text-amber-700">{{ $summary['pending'] }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <p class="text-sm text-slate-500">Aprovadas</p>
                <p class="mt-2 text-2xl font-black text-emerald-700">{{ $summary['approved'] }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <p class="text-sm text-slate-500">Rejeitadas</p>
                <p class="mt-2 text-2xl font-black text-rose-700">{{ $summary['rejected'] }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <p class="text-sm text-slate-500">Total</p>
                <p class="mt-2 text-2xl font-black text-slate-950">{{ $summary['total'] }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <form method="GET" class="grid gap-3 lg:grid-cols-[1fr_240px_auto_auto]">
                <input name="search" value="{{ $search }}" placeholder="Buscar por responsável, e-mail, telefone, lava-rápido ou cidade" class="rounded-xl border border-slate-300 px-4 py-2 text-sm">
                <select name="status" class="rounded-xl border border-slate-300 px-4 py-2 text-sm">
                    <option value="">Todos os status</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="rounded-xl bg-slate-950 px-5 py-2 text-sm font-bold text-white">Filtrar</button>
                <a href="{{ route('super-admin.location-requests.index') }}" class="rounded-xl border border-slate-300 px-5 py-2 text-center text-sm font-bold text-slate-700">Limpar</a>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-black text-slate-950">Solicitações recebidas</h2>
                <p class="mt-1 text-sm text-slate-500">Controle manual antes de publicar qualquer unidade no mapa.</p>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse ($requests as $requestItem)
                    @php
                        $badgeClass = match ($requestItem->status) {
                            \App\Models\WashLocationRequest::STATUS_APPROVED => 'bg-emerald-100 text-emerald-700',
                            \App\Models\WashLocationRequest::STATUS_REJECTED => 'bg-rose-100 text-rose-700',
                            default => 'bg-amber-100 text-amber-800',
                        };
                    @endphp
                    <article class="grid gap-4 px-5 py-5 lg:grid-cols-[1fr_220px]">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('super-admin.location-requests.show', $requestItem) }}" class="text-lg font-black text-blue-700 hover:text-blue-900">{{ $requestItem->business_name }}</a>
                                <span class="rounded-full px-3 py-1 text-xs font-black {{ $badgeClass }}">{{ $requestItem->statusLabel() }}</span>
                            </div>
                            <p class="mt-2 text-sm text-slate-600">Responsável: <strong>{{ $requestItem->responsible_name }}</strong></p>
                            <p class="mt-1 text-sm text-slate-500">{{ $requestItem->city }}/{{ $requestItem->state }} · {{ $requestItem->phone }} · {{ $requestItem->email }}</p>
                            <p class="mt-1 text-xs text-slate-400">Solicitado em {{ $requestItem->created_at?->format('d/m/Y H:i') }}</p>
                            @if ($requestItem->washLocation)
                                <p class="mt-2 text-xs font-bold text-emerald-700">Unidade: {{ $requestItem->washLocation->accountStatusLabel() }}</p>
                            @endif
                        </div>
                        <div class="flex items-center justify-start lg:justify-end">
                            <a href="{{ route('super-admin.location-requests.show', $requestItem) }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Ver detalhes</a>
                        </div>
                    </article>
                @empty
                    <p class="px-5 py-10 text-center text-sm text-slate-500">Nenhuma solicitação encontrada.</p>
                @endforelse
            </div>

            <div class="border-t border-slate-200 px-5 py-4">
                {{ $requests->links() }}
            </div>
        </section>
    </div>
</x-app.layout>
