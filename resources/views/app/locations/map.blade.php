<x-app.layout heading="Mapa de Unidades" title="Mapa · AutoFlow">
    <div class="grid gap-5 xl:grid-cols-[1fr_380px]">
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-bold">Lava-rapidos no mapa</h2>
                    <p class="text-sm text-slate-500">Visualizacao inicial das unidades e volume em andamento.</p>
                </div>
                <form method="GET" class="flex gap-2">
                    <select name="status" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <option value="">Todos</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-semibold text-white">Filtrar</button>
                </form>
            </div>

            <div class="relative min-h-[620px] overflow-hidden rounded-2xl border border-slate-200 bg-slate-100">
                <div class="absolute inset-0 bg-[linear-gradient(35deg,#e5e7eb_25%,transparent_25%),linear-gradient(145deg,#e5e7eb_25%,transparent_25%),linear-gradient(35deg,transparent_75%,#d1d5db_75%),linear-gradient(145deg,transparent_75%,#d1d5db_75%)] bg-[length:42px_42px] bg-[position:0_0,0_0,21px_-21px,-21px_21px]"></div>
                <div class="absolute inset-0 opacity-70">
                    <span class="absolute left-[12%] top-0 h-full w-3 rotate-12 rounded-full bg-white"></span>
                    <span class="absolute left-[34%] top-0 h-full w-2 -rotate-6 rounded-full bg-white"></span>
                    <span class="absolute left-[58%] top-0 h-full w-3 rotate-45 rounded-full bg-white"></span>
                    <span class="absolute left-0 top-[28%] h-2 w-full -rotate-3 rounded-full bg-white"></span>
                    <span class="absolute left-0 top-[58%] h-3 w-full rotate-6 rounded-full bg-white"></span>
                    <span class="absolute left-0 top-[78%] h-2 w-full -rotate-12 rounded-full bg-white"></span>
                </div>

                @foreach ($locations as $location)
                    @php
                        $pinColor = match ($location->status) {
                            \App\Models\WashLocation::STATUS_BUSY => 'bg-orange-500',
                            \App\Models\WashLocation::STATUS_CLOSED => 'bg-slate-500',
                            default => 'bg-blue-600',
                        };
                    @endphp
                    <a href="#location-{{ $location->id }}" class="group absolute -translate-x-1/2 -translate-y-1/2" style="left: {{ $location->map_x }}%; top: {{ $location->map_y }}%">
                        <span class="relative flex h-12 w-12 items-center justify-center rounded-full {{ $pinColor }} text-sm font-bold text-white shadow-xl ring-4 ring-white/80">
                            L
                            <span class="absolute -right-1 -top-1 rounded-full bg-white px-1.5 py-0.5 text-[10px] font-bold text-slate-900">{{ $location->active_orders_count }}</span>
                        </span>
                        <span class="pointer-events-none absolute left-1/2 top-full mt-2 hidden w-48 -translate-x-1/2 rounded-lg bg-slate-950 px-3 py-2 text-xs font-semibold text-white shadow-lg group-hover:block">
                            {{ $location->name }}<br>
                            <span class="font-normal text-slate-300">{{ $location->fullAddress() }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>

        <aside class="space-y-5">
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-bold">Resumo</h2>
                <div class="mt-4 grid grid-cols-3 gap-3 text-center">
                    <div class="rounded-lg bg-blue-50 p-3">
                        <p class="text-lg font-bold text-blue-700">{{ $locations->count() }}</p>
                        <p class="text-xs text-slate-500">Unidades</p>
                    </div>
                    <div class="rounded-lg bg-emerald-50 p-3">
                        <p class="text-lg font-bold text-emerald-700">{{ $locations->where('status', \App\Models\WashLocation::STATUS_OPEN)->count() }}</p>
                        <p class="text-xs text-slate-500">Abertas</p>
                    </div>
                    <div class="rounded-lg bg-orange-50 p-3">
                        <p class="text-lg font-bold text-orange-700">{{ $locations->sum('active_orders_count') }}</p>
                        <p class="text-xs text-slate-500">Em andamento</p>
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="font-bold">Unidades</h2>
                    <span class="text-xs font-semibold text-slate-500">{{ $locations->count() }} resultado(s)</span>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse ($locations as $location)
                        @php
                            $badgeClass = match ($location->status) {
                                \App\Models\WashLocation::STATUS_BUSY => 'bg-orange-100 text-orange-700',
                                \App\Models\WashLocation::STATUS_CLOSED => 'bg-slate-100 text-slate-600',
                                default => 'bg-green-100 text-green-700',
                            };
                        @endphp
                        <article id="location-{{ $location->id }}" class="rounded-xl border border-slate-200 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="font-bold">{{ $location->name }}</h3>
                                    <p class="mt-1 text-sm text-slate-500">{{ $location->fullAddress() }}</p>
                                    <p class="mt-1 text-xs text-slate-400">{{ $location->phone }}</p>
                                </div>
                                <span class="shrink-0 rounded-full px-2 py-1 text-xs font-bold {{ $badgeClass }}">{{ $location->statusLabel() }}</span>
                            </div>
                            <div class="mt-3 flex items-center justify-between text-sm">
                                <span class="text-slate-500">Lavagens em andamento</span>
                                <strong>{{ $location->active_orders_count }}</strong>
                            </div>
                        </article>
                    @empty
                        <p class="rounded-xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500">Nenhuma unidade encontrada.</p>
                    @endforelse
                </div>
            </section>
        </aside>
    </div>
</x-app.layout>
