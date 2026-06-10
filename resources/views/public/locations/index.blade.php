<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lava-rápidos próximos · AutoFlow</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIINfQHrcNPTwTnoX9ODj1F2lQ8fVI0O56k=" crossorigin="">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        #autoflow-public-map {
            position: relative;
            z-index: 1;
            display: block;
            width: 100%;
            height: clamp(520px, 68vh, 760px);
            min-height: 520px;
            overflow: hidden;
            background: #e2e8f0;
        }

        /*
         * Defesa local para o Leaflet.
         * Em alguns builds o CSS externo do Leaflet pode não carregar ou pode ser
         * sobrescrito pelo CSS compilado da aplicação. Quando isso acontece, os
         * tiles deixam de ficar posicionados de forma absoluta e aparecem
         * quebrados dentro do container.
         */
        #autoflow-public-map.leaflet-container {
            overflow: hidden;
        }

        #autoflow-public-map .leaflet-pane,
        #autoflow-public-map .leaflet-tile,
        #autoflow-public-map .leaflet-marker-icon,
        #autoflow-public-map .leaflet-marker-shadow,
        #autoflow-public-map .leaflet-tile-container,
        #autoflow-public-map .leaflet-pane > svg,
        #autoflow-public-map .leaflet-pane > canvas,
        #autoflow-public-map .leaflet-zoom-box,
        #autoflow-public-map .leaflet-image-layer,
        #autoflow-public-map .leaflet-layer {
            position: absolute;
            left: 0;
            top: 0;
        }

        #autoflow-public-map .leaflet-tile,
        #autoflow-public-map .leaflet-marker-icon,
        #autoflow-public-map .leaflet-marker-shadow {
            max-width: none !important;
            max-height: none !important;
            padding: 0 !important;
        }

        #autoflow-public-map .leaflet-tile {
            width: 256px !important;
            height: 256px !important;
        }

        #autoflow-public-map .leaflet-map-pane {
            z-index: 400;
        }

        #autoflow-public-map .leaflet-tile-pane {
            z-index: 200;
        }

        #autoflow-public-map .leaflet-overlay-pane {
            z-index: 400;
        }

        #autoflow-public-map .leaflet-shadow-pane {
            z-index: 500;
        }

        #autoflow-public-map .leaflet-marker-pane {
            z-index: 600;
        }

        #autoflow-public-map .leaflet-popup-pane {
            z-index: 700;
        }

        #autoflow-public-map .leaflet-top,
        #autoflow-public-map .leaflet-bottom {
            position: absolute;
            z-index: 1000;
            pointer-events: none;
        }

        #autoflow-public-map .leaflet-top {
            top: 0;
        }

        #autoflow-public-map .leaflet-bottom {
            bottom: 0;
        }

        #autoflow-public-map .leaflet-left {
            left: 0;
        }

        #autoflow-public-map .leaflet-right {
            right: 0;
        }

        #autoflow-public-map .leaflet-control {
            position: relative;
            z-index: 1000;
            pointer-events: auto;
        }

        #autoflow-public-map .leaflet-left .leaflet-control {
            float: left;
            clear: both;
        }

        #autoflow-public-map .leaflet-right .leaflet-control {
            float: right;
            clear: both;
        }

        #autoflow-public-map .leaflet-top .leaflet-control {
            margin-top: 10px;
        }

        #autoflow-public-map .leaflet-left .leaflet-control {
            margin-left: 10px;
        }

        #autoflow-public-map .leaflet-right .leaflet-control {
            margin-right: 10px;
        }

        #autoflow-public-map .leaflet-bottom .leaflet-control {
            margin-bottom: 10px;
        }



        #autoflow-public-map .leaflet-popup {
            position: absolute;
            text-align: left;
            margin-bottom: 20px;
        }

        #autoflow-public-map .leaflet-popup-content-wrapper {
            overflow: hidden;
            border-radius: 18px;
            background: #ffffff;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.28);
        }

        #autoflow-public-map .leaflet-popup-content {
            width: 260px !important;
            min-width: 260px;
            margin: 0;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            line-height: 1.35;
        }

        #autoflow-public-map .leaflet-popup-tip-container {
            position: absolute;
            left: 50%;
            width: 40px;
            height: 20px;
            margin-left: -20px;
            overflow: hidden;
            pointer-events: none;
        }

        #autoflow-public-map .leaflet-popup-tip {
            width: 17px;
            height: 17px;
            margin: -10px auto 0;
            transform: rotate(45deg);
            background: #ffffff;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.20);
        }

        #autoflow-public-map .leaflet-popup-close-button {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 20;
            display: grid;
            width: 26px;
            height: 26px;
            place-items: center;
            border-radius: 999px;
            color: #64748b;
            font-size: 18px;
            font-weight: 900;
            text-decoration: none;
            background: #f8fafc;
        }

        #autoflow-public-map .leaflet-popup-close-button:hover {
            color: #0f172a;
            background: #e2e8f0;
        }

        #autoflow-public-map .autoflow-popup-card {
            width: 260px;
            overflow: hidden;
            background: #ffffff;
            color: #0f172a;
        }

        #autoflow-public-map .autoflow-popup-header {
            padding: 14px 16px 12px;
            background: linear-gradient(135deg, #eff6ff, #ffffff);
            border-bottom: 1px solid #e2e8f0;
        }

        #autoflow-public-map .autoflow-popup-title {
            max-width: 210px;
            margin: 0;
            color: #0f172a;
            font-size: 15px;
            font-weight: 900;
        }

        #autoflow-public-map .autoflow-popup-address {
            margin-top: 5px;
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
        }

        #autoflow-public-map .autoflow-popup-body {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            padding: 12px;
        }

        #autoflow-public-map .autoflow-popup-info {
            border-radius: 14px;
            background: #f8fafc;
            padding: 10px;
        }

        #autoflow-public-map .autoflow-popup-label {
            display: block;
            color: #64748b;
            font-size: 11px;
            font-weight: 800;
        }

        #autoflow-public-map .autoflow-popup-value {
            display: block;
            margin-top: 3px;
            color: #0f172a;
            font-size: 15px;
            font-weight: 900;
        }

        #autoflow-public-map .autoflow-popup-status {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 5px 10px;
            font-size: 12px;
            font-weight: 900;
        }

        #autoflow-public-map .autoflow-map-marker span {
            display: grid;
            width: 34px;
            height: 34px;
            place-items: center;
            border: 3px solid #fff;
            border-radius: 999px;
            color: #fff;
            font-size: 0.75rem;
            font-weight: 900;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.28);
        }

        @media (max-width: 640px) {
            #autoflow-public-map {
                height: 520px;
            }
        }
    </style>
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
                    <div id="autoflow-public-map" class="w-full" data-locations='@json($mapLocations)'></div>
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
            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

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
                const statusBadgeStyle = location.status === 'closed'
                    ? 'background:#f1f5f9;color:#475569;'
                    : (location.status === 'busy' ? 'background:#ffedd5;color:#c2410c;' : 'background:#dcfce7;color:#15803d;');
                const popupAddress = [location.address, location.city].filter(Boolean).join(' - ');
                const popupPhone = location.phone || 'Não informado';

                marker.bindPopup(`
                    <div class="autoflow-popup-card">
                        <div class="autoflow-popup-header">
                            <p class="autoflow-popup-title">${escapeHtml(location.name)}</p>
                            <p class="autoflow-popup-address">${escapeHtml(popupAddress)}</p>
                        </div>
                        <div class="autoflow-popup-body">
                            <div class="autoflow-popup-info">
                                <span class="autoflow-popup-label">Status</span>
                                <span class="autoflow-popup-status" style="${statusBadgeStyle}">${escapeHtml(location.status_label)}</span>
                            </div>
                            <div class="autoflow-popup-info">
                                <span class="autoflow-popup-label">Em atendimento</span>
                                <span class="autoflow-popup-value">${Number(location.active_orders_count || 0)}</span>
                            </div>
                            <div class="autoflow-popup-info" style="grid-column:1 / -1">
                                <span class="autoflow-popup-label">Contato</span>
                                <span class="autoflow-popup-value">${escapeHtml(popupPhone)}</span>
                            </div>
                        </div>
                    </div>
                `, { minWidth: 260, maxWidth: 260, className: 'autoflow-location-popup' });

                markers.set(String(location.id), marker);
            });

            const fitMapToLocations = () => {
                map.invalidateSize({ animate: false });

                if (bounds.length > 0) {
                    map.fitBounds(bounds, { padding: [36, 36], maxZoom: 14 });
                    return;
                }

                map.setView(defaultCenter, 12);
            };

            requestAnimationFrame(fitMapToLocations);
            window.addEventListener('load', fitMapToLocations);
            window.addEventListener('resize', () => map.invalidateSize({ animate: false }));

            [100, 300, 700].forEach((delay) => {
                window.setTimeout(fitMapToLocations, delay);
            });

            if ('ResizeObserver' in window) {
                new ResizeObserver(() => map.invalidateSize({ animate: false })).observe(mapElement);
            }

            document.querySelectorAll('[data-focus-location]').forEach((link) => {
                link.addEventListener('click', (event) => {
                    event.preventDefault();
                    const marker = markers.get(String(link.dataset.focusLocation));

                    if (! marker) {
                        return;
                    }

                    map.invalidateSize({ animate: false });
                    map.setView(marker.getLatLng(), 15);
                    marker.openPopup();
                });
            });
        });
    </script>
</body>
</html>
