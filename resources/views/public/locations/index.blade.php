<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lava-rápidos próximos · AutoFlow</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIINfQHrcNPTwTnoX9ODj1F2lQ8fVI0O56k=" crossorigin="">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-950 text-slate-950 antialiased">
    <div class="min-h-screen bg-[radial-gradient(circle_at_top_left,#0ea5e9_0,transparent_32%),linear-gradient(135deg,#020617,#0f172a_55%,#082f49)] px-4 py-5 sm:px-6 lg:px-8">
        <header class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 rounded-3xl border border-white/10 bg-white/95 px-5 py-4 shadow-2xl shadow-black/25 backdrop-blur">
            <a href="{{ route('public.locations.index') }}" class="flex items-center gap-3">
                <img src="{{ asset('images/autoflow-logo.png') }}" alt="AutoFlow" class="h-12 w-auto">
                <div class="hidden sm:block">
                    <p class="text-sm font-bold text-slate-950">AutoFlow</p>
                    <p class="text-xs text-slate-500">Lava-rápidos próximos de você</p>
                </div>
            </a>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('login') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Entrar</a>
                <a href="#lista" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white shadow-lg shadow-blue-900/20">Ver unidades</a>
            </div>
        </header>

        <main class="mx-auto mt-6 grid max-w-7xl gap-5 xl:grid-cols-[1fr_420px]">
            <section class="overflow-hidden rounded-3xl border border-white/10 bg-white shadow-2xl shadow-black/25">
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 px-5 py-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.24em] text-blue-600">Mapa público</p>
                        <h1 class="mt-1 text-2xl font-black text-slate-950 sm:text-3xl">Encontre um lava-rápido próximo</h1>
                        <p class="mt-1 text-sm text-slate-500">Visualize unidades cadastradas, status operacional e contato direto.</p>
                    </div>

                    <form method="GET" class="flex flex-wrap gap-2">
                        <select name="status" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700">
                            <option value="">Todos os status</option>
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-bold text-white">Filtrar</button>
                    </form>
                </div>

                <div class="relative">
                    <div id="autoflow-public-map" class="h-[68vh] min-h-[520px] w-full" data-locations='@json($mapLocations)'></div>
                    <div class="pointer-events-none absolute bottom-4 left-4 max-w-xs rounded-2xl bg-white/95 p-4 text-sm shadow-xl ring-1 ring-slate-200 backdrop-blur">
                        <p class="font-bold text-slate-950">Mapa com OpenStreetMap</p>
                        <p class="mt-1 text-xs leading-5 text-slate-500">Clique nos marcadores para ver endereço, status e WhatsApp da unidade.</p>
                    </div>
                </div>
            </section>

            <aside id="lista" class="space-y-4">
                <section class="rounded-3xl border border-white/10 bg-white p-5 shadow-2xl shadow-black/20">
                    <div class="grid grid-cols-3 gap-3 text-center">
                        <div class="rounded-2xl bg-blue-50 p-3">
                            <p class="text-2xl font-black text-blue-700">{{ $locations->count() }}</p>
                            <p class="text-xs font-semibold text-slate-500">Unidades</p>
                        </div>
                        <div class="rounded-2xl bg-emerald-50 p-3">
                            <p class="text-2xl font-black text-emerald-700">{{ $locations->where('status', \App\Models\WashLocation::STATUS_OPEN)->count() }}</p>
                            <p class="text-xs font-semibold text-slate-500">Abertas</p>
                        </div>
                        <div class="rounded-2xl bg-orange-50 p-3">
                            <p class="text-2xl font-black text-orange-700">{{ $locations->sum('active_orders_count') }}</p>
                            <p class="text-xs font-semibold text-slate-500">Em atendimento</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-3xl border border-white/10 bg-white p-5 shadow-2xl shadow-black/20">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-black text-slate-950">Lava-rápidos cadastrados</h2>
                            <p class="text-sm text-slate-500">Lista pública das unidades disponíveis.</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ $locations->count() }}</span>
                    </div>

                    <div class="mt-5 max-h-[68vh] space-y-3 overflow-y-auto pr-1">
                        @forelse ($locations as $location)
                            @php
                                $badgeClass = match ($location->status) {
                                    \App\Models\WashLocation::STATUS_BUSY => 'bg-orange-100 text-orange-700',
                                    \App\Models\WashLocation::STATUS_CLOSED => 'bg-slate-100 text-slate-600',
                                    default => 'bg-green-100 text-green-700',
                                };
                                $whatsapp = $location->whatsappUrl();
                            @endphp
                            <article id="unidade-{{ $location->id }}" class="rounded-2xl border border-slate-200 p-4 transition hover:border-blue-200 hover:shadow-sm">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <h3 class="truncate font-black text-slate-950">{{ $location->name }}</h3>
                                        <p class="mt-1 text-sm leading-5 text-slate-500">{{ $location->fullAddress() }}</p>
                                    </div>
                                    <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-black {{ $badgeClass }}">{{ $location->statusLabel() }}</span>
                                </div>

                                <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
                                    <div class="rounded-xl bg-slate-50 p-3">
                                        <p class="text-xs font-semibold text-slate-500">Lavagens em andamento</p>
                                        <p class="mt-1 text-lg font-black text-slate-950">{{ $location->active_orders_count }}</p>
                                    </div>
                                    <div class="rounded-xl bg-slate-50 p-3">
                                        <p class="text-xs font-semibold text-slate-500">Contato</p>
                                        <p class="mt-1 truncate text-sm font-bold text-slate-950">{{ $location->phone ?: 'Não informado' }}</p>
                                    </div>
                                </div>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    <a href="#" data-focus-location="{{ $location->id }}" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">Ver no mapa</a>
                                    @if ($whatsapp)
                                        <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="rounded-xl bg-green-600 px-3 py-2 text-xs font-bold text-white">Chamar no WhatsApp</a>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">Nenhum lava-rápido encontrado.</p>
                        @endforelse
                    </div>
                </section>
            </aside>
        </main>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const mapElement = document.getElementById('autoflow-public-map');
            const locations = JSON.parse(mapElement?.dataset.locations || '[]');

            if (! mapElement || ! window.L) {
                return;
            }

            const defaultCenter = [-23.55052, -46.63331];
            const map = L.map(mapElement, { scrollWheelZoom: true }).setView(defaultCenter, 12);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors',
            }).addTo(map);

            const markers = new Map();
            const bounds = [];

            locations.forEach((location) => {
                if (! location.latitude || ! location.longitude) {
                    return;
                }

                const latLng = [Number(location.latitude), Number(location.longitude)];
                bounds.push(latLng);

                const statusColor = location.status === 'closed' ? '#64748b' : (location.status === 'busy' ? '#f97316' : '#2563eb');
                const icon = L.divIcon({
                    className: 'autoflow-map-marker',
                    html: `<span style="background:${statusColor}">L</span>`,
                    iconSize: [34, 34],
                    iconAnchor: [17, 17],
                });

                const marker = L.marker(latLng, { icon }).addTo(map);
                marker.bindPopup(`
                    <strong>${location.name}</strong><br>
                    <span>${location.address}</span><br>
                    <span>Status: ${location.status_label}</span><br>
                    <span>${location.active_orders_count} lavagem(ns) em andamento</span>
                `);

                markers.set(String(location.id), marker);
            });

            if (bounds.length > 0) {
                map.fitBounds(bounds, { padding: [36, 36], maxZoom: 14 });
            }

            document.querySelectorAll('[data-focus-location]').forEach((link) => {
                link.addEventListener('click', (event) => {
                    event.preventDefault();
                    const marker = markers.get(String(link.dataset.focusLocation));

                    if (! marker) {
                        return;
                    }

                    map.setView(marker.getLatLng(), 15);
                    marker.openPopup();
                });
            });
        });
    </script>
</body>
</html>
