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


        #autoflow-public-map .leaflet-control-zoom {
            border: 0 !important;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.22);
        }

        #autoflow-public-map .leaflet-control-zoom a {
            display: grid;
            width: 46px;
            height: 46px;
            place-items: center;
            border: 0 !important;
            color: #0f172a;
            font-size: 1.35rem;
            font-weight: 900;
            line-height: 1;
        }

        #autoflow-public-map .leaflet-control-zoom a:first-child {
            border-bottom: 1px solid #e2e8f0 !important;
        }

        #autoflow-public-map .leaflet-control-attribution {
            border-radius: 999px 0 0 0;
            background: rgba(255, 255, 255, 0.86);
            padding: 4px 8px;
            font-size: 0.68rem;
            backdrop-filter: blur(10px);
        }

        .autoflow-map-actions {
            position: absolute;
            top: 1rem;
            right: 1rem;
            z-index: 900;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            pointer-events: none;
        }

        .autoflow-map-action-button {
            display: inline-flex;
            min-height: 44px;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            border-radius: 999px;
            border: 1px solid rgba(226, 232, 240, 0.92);
            background: rgba(255, 255, 255, 0.94);
            padding: 0.72rem 0.95rem;
            color: #0f172a;
            font-size: 0.78rem;
            font-weight: 900;
            box-shadow: 0 18px 38px rgba(15, 23, 42, 0.20);
            backdrop-filter: blur(12px);
            pointer-events: auto;
            transition: transform 150ms ease, box-shadow 150ms ease, background 150ms ease;
        }

        .autoflow-map-action-button:hover {
            transform: translateY(-1px);
            background: #ffffff;
            box-shadow: 0 22px 44px rgba(15, 23, 42, 0.24);
        }

        .autoflow-map-action-button:disabled {
            cursor: not-allowed;
            opacity: 0.7;
            transform: none;
        }

        .autoflow-map-action-button.is-primary {
            border-color: rgba(37, 99, 235, 0.2);
            background: #2563eb;
            color: #ffffff;
        }

        .autoflow-map-toast {
            position: absolute;
            right: 1rem;
            bottom: 1rem;
            z-index: 910;
            display: none;
            max-width: min(360px, calc(100% - 2rem));
            border-radius: 18px;
            border: 1px solid rgba(226, 232, 240, 0.95);
            background: rgba(15, 23, 42, 0.94);
            padding: 0.85rem 1rem;
            color: #ffffff;
            font-size: 0.82rem;
            font-weight: 700;
            box-shadow: 0 24px 52px rgba(15, 23, 42, 0.34);
            backdrop-filter: blur(12px);
        }

        .autoflow-map-toast.is-visible {
            display: block;
        }

        #autoflow-public-map .autoflow-user-marker span {
            display: grid;
            width: 22px;
            height: 22px;
            place-items: center;
            border: 4px solid #fff;
            border-radius: 999px;
            background: #0ea5e9;
            box-shadow: 0 0 0 8px rgba(14, 165, 233, 0.20), 0 12px 24px rgba(15, 23, 42, 0.28);
        }


        .autoflow-proximity-summary {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            border-radius: 999px;
            background: #eff6ff;
            padding: 0.45rem 0.75rem;
            color: #1d4ed8;
            font-size: 0.72rem;
            font-weight: 900;
        }

        .autoflow-location-card.is-closest {
            border-color: #93c5fd;
            background: linear-gradient(135deg, #eff6ff, #ffffff 62%);
            box-shadow: 0 18px 36px rgba(37, 99, 235, 0.14);
        }

        .autoflow-distance-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            border-radius: 999px;
            background: #f1f5f9;
            padding: 0.35rem 0.65rem;
            color: #475569;
            font-size: 0.72rem;
            font-weight: 900;
        }

        .autoflow-distance-pill.has-distance {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .autoflow-closest-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            border-radius: 999px;
            background: #2563eb;
            padding: 0.35rem 0.65rem;
            color: #ffffff;
            font-size: 0.7rem;
            font-weight: 900;
            box-shadow: 0 12px 24px rgba(37, 99, 235, 0.24);
        }

        .autoflow-closest-badge.hidden {
            display: none;
        }

        @media (max-width: 640px) {
            #autoflow-public-map {
                height: 520px;
            }

            .autoflow-map-actions {
                left: 0.75rem;
                right: 0.75rem;
                top: 0.75rem;
                flex-direction: row;
                flex-wrap: wrap;
            }

            .autoflow-map-action-button {
                min-height: 46px;
                flex: 1 1 auto;
                padding-inline: 0.8rem;
            }

            #autoflow-public-map .leaflet-control-zoom a {
                width: 48px;
                height: 48px;
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
                    <div class="autoflow-map-actions" aria-label="Controles do mapa">
                        <button type="button" class="autoflow-map-action-button is-primary" data-map-geolocation>
                            <span aria-hidden="true">📍</span>
                            <span>Minha localização</span>
                        </button>
                        <button type="button" class="autoflow-map-action-button" data-map-reset>
                            <span aria-hidden="true">🎯</span>
                            <span>Centralizar</span>
                        </button>
                    </div>
                    <div class="autoflow-map-toast" data-map-toast role="status" aria-live="polite"></div>
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
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-black text-slate-950">Lava-rápidos cadastrados</h2>
                            <p class="text-sm text-slate-500">Lista pública das unidades disponíveis.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span data-proximity-summary class="autoflow-proximity-summary">📍 Use sua localização para ordenar por proximidade</span>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ $locations->count() }}</span>
                        </div>
                    </div>

                    <div data-locations-list class="mt-5 max-h-[68vh] space-y-3 overflow-y-auto pr-1">
                        @forelse ($locations as $location)
                            @php
                                $badgeClass = match ($location->status) {
                                    \App\Models\WashLocation::STATUS_BUSY => 'bg-orange-100 text-orange-700',
                                    \App\Models\WashLocation::STATUS_CLOSED => 'bg-slate-100 text-slate-600',
                                    default => 'bg-green-100 text-green-700',
                                };
                                $whatsapp = $location->whatsappUrl();
                            @endphp
                            <article id="unidade-{{ $location->id }}" data-location-card data-location-id="{{ $location->id }}" data-latitude="{{ $location->mapLatitude() }}" data-longitude="{{ $location->mapLongitude() }}" class="autoflow-location-card rounded-2xl border border-slate-200 p-4 transition hover:border-blue-200 hover:shadow-sm">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <h3 class="truncate font-black text-slate-950">{{ $location->name }}</h3>
                                        <p class="mt-1 text-sm leading-5 text-slate-500">{{ $location->fullAddress() }}</p>
                                        <div class="mt-3 flex flex-wrap items-center gap-2">
                                            <span data-distance-label class="autoflow-distance-pill">📍 Distância após localização</span>
                                            <span data-closest-badge class="autoflow-closest-badge hidden">Mais próximo</span>
                                        </div>
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
            const geolocationButton = document.querySelector('[data-map-geolocation]');
            const resetButton = document.querySelector('[data-map-reset]');
            const toastElement = document.querySelector('[data-map-toast]');
            const locationsListElement = document.querySelector('[data-locations-list]');
            const proximitySummaryElement = document.querySelector('[data-proximity-summary]');

            if (! mapElement || ! window.L) {
                return;
            }

            const defaultCenter = [-23.55052, -46.63331];
            const map = L.map(mapElement, { scrollWheelZoom: true, zoomControl: true }).setView(defaultCenter, 12);
            let userMarker = null;
            let toastTimeout = null;
            const locationDistances = new Map();

            const showMapToast = (message) => {
                if (! toastElement) {
                    return;
                }

                toastElement.textContent = message;
                toastElement.classList.add('is-visible');

                window.clearTimeout(toastTimeout);
                toastTimeout = window.setTimeout(() => {
                    toastElement.classList.remove('is-visible');
                }, 4200);
            };

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

            const calculateDistanceInKm = (originLatitude, originLongitude, destinationLatitude, destinationLongitude) => {
                const earthRadiusInKm = 6371;
                const toRadians = (degrees) => degrees * Math.PI / 180;
                const latitudeDelta = toRadians(destinationLatitude - originLatitude);
                const longitudeDelta = toRadians(destinationLongitude - originLongitude);
                const originLatitudeInRadians = toRadians(originLatitude);
                const destinationLatitudeInRadians = toRadians(destinationLatitude);

                const a = Math.sin(latitudeDelta / 2) ** 2
                    + Math.cos(originLatitudeInRadians) * Math.cos(destinationLatitudeInRadians)
                    * Math.sin(longitudeDelta / 2) ** 2;

                return earthRadiusInKm * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            };

            const formatDistance = (distanceInKm) => {
                if (! Number.isFinite(distanceInKm)) {
                    return 'Distância indisponível';
                }

                if (distanceInKm < 1) {
                    return `${Math.max(1, Math.round(distanceInKm * 1000))} m de você`;
                }

                return `${distanceInKm.toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 })} km de você`;
            };

            const buildPopupContent = (location) => {
                const distanceLabel = locationDistances.has(String(location.id))
                    ? formatDistance(locationDistances.get(String(location.id)))
                    : 'Use sua localização para calcular distância';
                const statusBadgeStyle = location.status === 'closed'
                    ? 'background:#f1f5f9;color:#475569;'
                    : (location.status === 'busy' ? 'background:#ffedd5;color:#c2410c;' : 'background:#dcfce7;color:#15803d;');
                const popupAddress = [location.address, location.city].filter(Boolean).join(' - ');
                const popupPhone = location.phone || 'Não informado';

                return `
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
                                <span class="autoflow-popup-label">Distância</span>
                                <span class="autoflow-popup-value">${escapeHtml(distanceLabel)}</span>
                            </div>
                            <div class="autoflow-popup-info">
                                <span class="autoflow-popup-label">Em atendimento</span>
                                <span class="autoflow-popup-value">${Number(location.active_orders_count || 0)}</span>
                            </div>
                            <div class="autoflow-popup-info">
                                <span class="autoflow-popup-label">Contato</span>
                                <span class="autoflow-popup-value">${escapeHtml(popupPhone)}</span>
                            </div>
                        </div>
                    </div>
                `;
            };

            const bindLocationPopup = (marker, location) => {
                marker.bindPopup(buildPopupContent(location), {
                    minWidth: 260,
                    maxWidth: 260,
                    className: 'autoflow-location-popup',
                });
            };

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
                bindLocationPopup(marker, location);

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

            const applyUserLocationDistances = (latitude, longitude) => {
                const rankedLocations = locations
                    .map((location) => {
                        const destinationLatitude = Number(location.latitude);
                        const destinationLongitude = Number(location.longitude);

                        if (! Number.isFinite(destinationLatitude) || ! Number.isFinite(destinationLongitude)) {
                            return { location, distance: Number.POSITIVE_INFINITY };
                        }

                        const distance = calculateDistanceInKm(latitude, longitude, destinationLatitude, destinationLongitude);
                        locationDistances.set(String(location.id), distance);

                        return { location, distance };
                    })
                    .sort((first, second) => first.distance - second.distance);

                rankedLocations.forEach(({ location, distance }, index) => {
                    const card = document.querySelector(`[data-location-card][data-location-id="${location.id}"]`);
                    const distanceLabel = card?.querySelector('[data-distance-label]');
                    const closestBadge = card?.querySelector('[data-closest-badge]');
                    const marker = markers.get(String(location.id));

                    if (distanceLabel) {
                        distanceLabel.textContent = `📍 ${formatDistance(distance)}`;
                        distanceLabel.classList.add('has-distance');
                    }

                    if (closestBadge) {
                        closestBadge.classList.toggle('hidden', index !== 0 || ! Number.isFinite(distance));
                    }

                    card?.classList.toggle('is-closest', index === 0 && Number.isFinite(distance));

                    if (locationsListElement && card) {
                        locationsListElement.appendChild(card);
                    }

                    if (marker) {
                        bindLocationPopup(marker, location);
                    }
                });

                if (proximitySummaryElement) {
                    proximitySummaryElement.textContent = '📍 Lista ordenada por proximidade';
                }
            };

            resetButton?.addEventListener('click', (event) => {
                event.preventDefault();
                fitMapToLocations();
            });

            geolocationButton?.addEventListener('click', (event) => {
                event.preventDefault();

                if (! navigator.geolocation) {
                    showMapToast('Seu navegador não oferece suporte à localização.');
                    return;
                }

                geolocationButton.disabled = true;
                geolocationButton.querySelector('span:last-child').textContent = 'Localizando...';

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const latLng = [position.coords.latitude, position.coords.longitude];

                        if (! userMarker) {
                            const userIcon = L.divIcon({
                                className: 'autoflow-user-marker',
                                html: '<span></span>',
                                iconSize: [22, 22],
                                iconAnchor: [11, 11],
                            });

                            userMarker = L.marker(latLng, { icon: userIcon }).addTo(map);
                            userMarker.bindPopup('<strong>Você está aqui</strong>');
                        } else {
                            userMarker.setLatLng(latLng);
                        }

                        map.invalidateSize({ animate: false });
                        map.setView(latLng, 15);
                        userMarker.openPopup();
                        applyUserLocationDistances(latLng[0], latLng[1]);
                        showMapToast('Localização encontrada. Lista ordenada por proximidade.');
                    },
                    () => {
                        showMapToast('Não foi possível acessar sua localização. Verifique as permissões do navegador.');
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 60000,
                    },
                );

                window.setTimeout(() => {
                    geolocationButton.disabled = false;
                    geolocationButton.querySelector('span:last-child').textContent = 'Minha localização';
                }, 1200);
            });

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
