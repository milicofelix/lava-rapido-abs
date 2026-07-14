<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lava-rápidos próximos · AutoFlow</title>
    <meta name="description" content="Encontre lava-rápidos próximos, veja status em tempo real, endereço, rota, WhatsApp e serviços disponíveis no AutoFlow.">
    <link rel="canonical" href="{{ route('public.locations.index') }}">
    <meta property="og:title" content="Lava-rápidos próximos · AutoFlow">
    <meta property="og:description" content="Mapa público com lava-rápidos, status em tempo real, endereço, rota e contato direto.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ route('public.locations.index') }}">
    @include('components.favicon')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIINfQHrcNPTwTnoX9ODj1F2lQ8fVI0O56k=" crossorigin="">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => 'Lava-rápidos próximos',
            'url' => route('public.locations.index'),
            'numberOfItems' => $locations->count(),
            'itemListElement' => $locations->values()->map(fn ($location, $index) => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'url' => route('public.locations.show', ['location' => $location->slug]),
                'name' => $location->name,
            ])->all(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
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



        #autoflow-public-map .autoflow-popup-actions {
            display: grid;
            gap: 8px;
            padding: 0 12px 12px;
        }

        #autoflow-public-map .autoflow-popup-action-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        #autoflow-public-map .autoflow-popup-button {
            display: inline-flex;
            min-height: 38px;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            padding: 8px 10px;
            color: #334155;
            font-size: 12px;
            font-weight: 900;
            text-align: center;
            text-decoration: none;
            transition: background 150ms ease, border-color 150ms ease, transform 150ms ease;
        }

        #autoflow-public-map .autoflow-popup-button:hover {
            border-color: #bfdbfe;
            background: #eff6ff;
            color: #1d4ed8;
            transform: translateY(-1px);
        }

        #autoflow-public-map .autoflow-popup-button.is-whatsapp {
            border-color: #bbf7d0;
            background: #16a34a;
            color: #ffffff;
        }

        #autoflow-public-map .autoflow-popup-button.is-whatsapp:hover {
            background: #15803d;
            color: #ffffff;
        }

        #autoflow-public-map .autoflow-popup-button.is-full {
            grid-column: 1 / -1;
        }

        .autoflow-location-card.is-active {
            border-color: #2563eb;
            background: linear-gradient(135deg, #dbeafe, #ffffff 64%);
            box-shadow: 0 22px 44px rgba(37, 99, 235, 0.18);
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

        .autoflow-favorites-toggle {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            border-radius: 999px;
            border: 1px solid #fde68a;
            background: #fffbeb;
            padding: 0.45rem 0.75rem;
            color: #92400e;
            font-size: 0.72rem;
            font-weight: 900;
            transition: transform 150ms ease, box-shadow 150ms ease, background 150ms ease;
        }

        .autoflow-favorites-toggle:hover,
        .autoflow-favorites-toggle.is-active {
            background: #f59e0b;
            color: #ffffff;
            box-shadow: 0 12px 24px rgba(245, 158, 11, 0.24);
            transform: translateY(-1px);
        }

        .autoflow-favorite-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            border-radius: 999px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            padding: 0.5rem 0.75rem;
            color: #475569;
            font-size: 0.75rem;
            font-weight: 900;
            transition: transform 150ms ease, border-color 150ms ease, background 150ms ease, color 150ms ease;
        }

        .autoflow-favorite-button:hover,
        .autoflow-favorite-button.is-favorite {
            border-color: #fbbf24;
            background: #fffbeb;
            color: #92400e;
            transform: translateY(-1px);
        }

        .autoflow-favorite-badge {
            display: none;
            align-items: center;
            gap: 0.3rem;
            border-radius: 999px;
            background: #fef3c7;
            padding: 0.35rem 0.65rem;
            color: #92400e;
            font-size: 0.7rem;
            font-weight: 900;
        }

        .autoflow-location-card.is-favorite .autoflow-favorite-badge {
            display: inline-flex;
        }

        .autoflow-location-card.is-hidden-by-favorites {
            display: none;
        }

        .autoflow-empty-favorites-message {
            display: none;
            border-radius: 1rem;
            border: 1px dashed #fbbf24;
            background: #fffbeb;
            padding: 1rem;
            color: #92400e;
            font-size: 0.85rem;
            font-weight: 800;
            text-align: center;
        }

        .autoflow-empty-favorites-message.is-visible {
            display: block;
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
                @auth
                    @php
                        $publicHeaderUser = auth()->user();
                        $publicHeaderPanelUrl = match (true) {
                            $publicHeaderUser?->hasRole(\App\Models\User::ROLE_SUPER_ADMIN) => route('super-admin.location-requests.index'),
                            \App\Support\Access\AccessControl::allows($publicHeaderUser, \App\Support\Access\AccessControl::VIEW_DASHBOARD) => route('dashboard'),
                            \App\Support\Access\AccessControl::allows($publicHeaderUser, \App\Support\Access\AccessControl::VIEW_KANBAN) => route('kanban'),
                            default => route('public.locations.index'),
                        };
                    @endphp
                    <span class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-black text-slate-700">{{ $publicHeaderUser->name }}</span>
                    <a href="{{ $publicHeaderPanelUrl }}" class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-bold text-white">Painel</a>
                @else
                    <a href="{{ route('login') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Entrar</a>
                @endauth
                <a href="{{ route('public.location-requests.create') }}" class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-bold text-blue-700 hover:bg-blue-100">Cadastrar lava-rápido</a>
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
                        <p class="mt-2 text-xs font-semibold text-slate-500">Tem um lava-rápido? <a href="{{ route('public.location-requests.create') }}" class="font-black text-blue-600 hover:text-blue-700">Solicite seu cadastro no AutoFlow</a>.</p>
                    </div>

                    <form method="GET" class="grid w-full gap-2 sm:w-auto sm:min-w-[440px] sm:grid-cols-[1fr_auto] xl:min-w-[520px]" data-public-map-filters>
                        <label class="sr-only" for="public-location-search">Buscar lava-rápido, bairro ou endereço</label>
                        <input
                            id="public-location-search"
                            name="q"
                            value="{{ $search ?? '' }}"
                            placeholder="Buscar por nome, bairro ou endereço"
                            class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 placeholder:text-slate-400 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        >

                        <button class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-bold text-white">Filtrar</button>

                        <div class="flex flex-wrap items-center gap-2 sm:col-span-2">
                            <select name="status" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700" @disabled($onlyOpen ?? false)>
                                <option value="">Todos os status</option>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>

                            <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">
                                <input type="checkbox" name="only_open" value="1" @checked($onlyOpen ?? false) class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                Somente abertos
                            </label>

                            @if (($search ?? '') !== '' || ($status ?? '') !== '' || ($onlyOpen ?? false))
                                <a href="{{ route('public.locations.index') }}" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-bold text-slate-600 hover:bg-slate-50">Limpar filtros</a>
                            @endif
                        </div>
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
                            <p class="text-xs font-semibold text-slate-500">Encontradas</p>
                        </div>
                        <div class="rounded-2xl bg-emerald-50 p-3">
                            <p class="text-2xl font-black text-emerald-700">{{ $locations->filter(fn ($location) => in_array($location->publicStatus(), [\App\Models\WashLocation::STATUS_OPEN, \App\Models\WashLocation::STATUS_BUSY], true))->count() }}</p>
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
                            <p class="text-sm text-slate-500">
                                @if (($search ?? '') !== '' || ($status ?? '') !== '' || ($onlyOpen ?? false))
                                    Resultado filtrado do mapa público.
                                @else
                                    Lista pública das unidades disponíveis.
                                @endif
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" data-favorites-filter class="autoflow-favorites-toggle" aria-pressed="false">
                                ⭐ Meus favoritos
                                <span data-favorites-count>0</span>
                            </button>
                            <span data-proximity-summary class="autoflow-proximity-summary">📍 Use sua localização para ordenar por proximidade</span>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ $locations->count() }}</span>
                        </div>
                    </div>

                    <div data-locations-list class="mt-5 max-h-[68vh] space-y-3 overflow-y-auto pr-1">
                        @forelse ($locations as $location)
                            @php
                                $publicStatus = $location->publicStatus();
                                $badgeClass = match ($publicStatus) {
                                    \App\Models\WashLocation::STATUS_BUSY => 'bg-orange-100 text-orange-700',
                                    \App\Models\WashLocation::STATUS_CLOSED => 'bg-slate-100 text-slate-600',
                                    default => 'bg-green-100 text-green-700',
                                };
                                $whatsapp = $location->whatsappUrl();
                                $reviewSummary = $reviewSummaries[$location->id] ?? null;
                            @endphp
                            <article id="unidade-{{ $location->id }}" data-location-card data-location-id="{{ $location->id }}" data-latitude="{{ $location->mapLatitude() }}" data-longitude="{{ $location->mapLongitude() }}" class="autoflow-location-card rounded-2xl border border-slate-200 p-4 transition hover:border-blue-200 hover:shadow-sm">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <h3 class="truncate font-black text-slate-950">{{ $location->name }}</h3>
                                        <p class="mt-1 text-sm leading-5 text-slate-500">{{ $location->fullAddress() }}</p>
                                        <div class="mt-3 flex flex-wrap items-center gap-2">
                                            <span data-distance-label class="autoflow-distance-pill">📍 Distância após localização</span>
                                            @if ($reviewSummary)
                                                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-800">
                                                    ★ {{ number_format((float) $reviewSummary['average'], 1, ',', '.') }} · {{ $reviewSummary['count'] }} {{ $reviewSummary['count'] === 1 ? 'avaliação' : 'avaliações' }}
                                                </span>
                                            @else
                                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500">Sem avaliações</span>
                                            @endif
                                            <span data-closest-badge class="autoflow-closest-badge hidden">Mais próximo</span>
                                            <span class="autoflow-favorite-badge">⭐ Favorito</span>
                                        </div>
                                    </div>
                                    <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-black {{ $badgeClass }}">{{ $location->publicStatusLabel() }}</span>
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
                                <p class="mt-3 rounded-xl bg-blue-50 px-3 py-2 text-xs font-bold leading-5 text-blue-900">{{ $location->opening_hours ?: $location->openingHoursSummary() }}</p>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    <button type="button" data-favorite-toggle data-location-id="{{ $location->id }}" class="autoflow-favorite-button" aria-pressed="false">☆ Favoritar</button>
                                    @if ($location->hasCoordinates())
                                        <a href="#" data-focus-location="{{ $location->id }}" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">Ver no mapa</a>
                                    @else
                                        <span class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-bold text-amber-800">Mapa pendente</span>
                                    @endif
                                    <a href="{{ route('public.locations.show', ['location' => $location->slug]) }}" class="rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700 hover:bg-blue-100">Ver detalhes</a>
                                    @if ($whatsapp)
                                        <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="rounded-xl bg-green-600 px-3 py-2 text-xs font-bold text-white">Chamar no WhatsApp</a>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">Nenhum lava-rápido encontrado com os filtros atuais.</p>
                        @endforelse
                        <div data-empty-favorites class="autoflow-empty-favorites-message">
                            Nenhum lava-rápido favorito ainda. Clique em “Favoritar” em uma unidade para vê-la aqui.
                        </div>
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
            const favoritesFilterButton = document.querySelector('[data-favorites-filter]');
            const favoritesCountElement = document.querySelector('[data-favorites-count]');
            const emptyFavoritesElement = document.querySelector('[data-empty-favorites]');
            const favoriteStorageKey = 'autoflow.favoriteLocations';
            let showOnlyFavorites = false;

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


            const buildWhatsappUrl = (phone, locationName) => {
                const digits = String(phone || '').replace(/\D/g, '');

                if (! digits) {
                    return null;
                }

                const normalized = digits.startsWith('55') ? digits : `55${digits}`;
                const message = `Olá! Vim pelo AutoFlow e gostaria de informações sobre ${locationName || 'a unidade'}.`;

                return `https://wa.me/${normalized}?text=${encodeURIComponent(message)}`;
            };

            const hasValidCoordinates = (location) => {
                if (location.latitude === null || location.longitude === null || location.latitude === '' || location.longitude === '') {
                    return false;
                }

                return Number.isFinite(Number(location.latitude)) && Number.isFinite(Number(location.longitude));
            };

            const buildDirectionsUrl = (location) => {
                const latitude = Number(location.latitude);
                const longitude = Number(location.longitude);

                if (hasValidCoordinates(location)) {
                    return `https://www.google.com/maps/dir/?api=1&destination=${latitude},${longitude}`;
                }

                return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent([location.address, location.city].filter(Boolean).join(' '))}`;
            };

            const clearActiveLocationCards = () => {
                document.querySelectorAll('[data-location-card].is-active').forEach((card) => {
                    card.classList.remove('is-active');
                });
            };

            const highlightLocationCard = (locationId, shouldScroll = true) => {
                const card = document.querySelector(`[data-location-card][data-location-id="${locationId}"]`);

                if (! card) {
                    return;
                }

                clearActiveLocationCards();
                card.classList.add('is-active');

                if (shouldScroll) {
                    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
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

            const readFavoriteLocations = () => {
                try {
                    return new Set(JSON.parse(window.localStorage.getItem(favoriteStorageKey) || '[]').map(String));
                } catch (error) {
                    return new Set();
                }
            };

            const writeFavoriteLocations = (favorites) => {
                window.localStorage.setItem(favoriteStorageKey, JSON.stringify([...favorites]));
            };

            const refreshFavoriteUi = () => {
                const favorites = readFavoriteLocations();
                let visibleFavoriteCards = 0;

                document.querySelectorAll('[data-location-card]').forEach((card) => {
                    const isFavorite = favorites.has(String(card.dataset.locationId));
                    card.classList.toggle('is-favorite', isFavorite);
                    card.classList.toggle('is-hidden-by-favorites', showOnlyFavorites && ! isFavorite);

                    if (showOnlyFavorites && isFavorite) {
                        visibleFavoriteCards += 1;
                    }
                });

                document.querySelectorAll('[data-favorite-toggle]').forEach((button) => {
                    const isFavorite = favorites.has(String(button.dataset.locationId));
                    button.classList.toggle('is-favorite', isFavorite);
                    button.setAttribute('aria-pressed', isFavorite ? 'true' : 'false');
                    button.textContent = isFavorite ? '★ Favorito' : '☆ Favoritar';
                });

                if (favoritesCountElement) {
                    favoritesCountElement.textContent = String(favorites.size);
                }

                if (favoritesFilterButton) {
                    favoritesFilterButton.classList.toggle('is-active', showOnlyFavorites);
                    favoritesFilterButton.setAttribute('aria-pressed', showOnlyFavorites ? 'true' : 'false');
                }

                emptyFavoritesElement?.classList.toggle('is-visible', showOnlyFavorites && visibleFavoriteCards === 0);
            };

            const toggleFavoriteLocation = (locationId) => {
                const favorites = readFavoriteLocations();
                const normalizedLocationId = String(locationId);

                if (favorites.has(normalizedLocationId)) {
                    favorites.delete(normalizedLocationId);
                    showMapToast('Lava-rápido removido dos favoritos.');
                } else {
                    favorites.add(normalizedLocationId);
                    showMapToast('Lava-rápido salvo nos favoritos.');
                }

                writeFavoriteLocations(favorites);
                refreshFavoriteUi();
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
                const ratingLabel = Number(location.rating_count || 0) > 0
                    ? `★ ${Number(location.rating_average || 0).toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 })} · ${Number(location.rating_count || 0)} ${Number(location.rating_count || 0) === 1 ? 'avaliação' : 'avaliações'}`
                    : 'Sem avaliações';
                const whatsappUrl = buildWhatsappUrl(location.phone, location.name);
                const directionsUrl = buildDirectionsUrl(location);
                const whatsappButton = whatsappUrl
                    ? `<a class="autoflow-popup-button is-whatsapp" href="${escapeHtml(whatsappUrl)}" target="_blank" rel="noopener">WhatsApp</a>`
                    : `<span class="autoflow-popup-button" aria-disabled="true">Sem WhatsApp</span>`;

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
                                <span class="autoflow-popup-label">Avaliação</span>
                                <span class="autoflow-popup-value">${escapeHtml(ratingLabel)}</span>
                            </div>
                            <div class="autoflow-popup-info">
                                <span class="autoflow-popup-label">Contato</span>
                                <span class="autoflow-popup-value">${escapeHtml(popupPhone)}</span>
                            </div>
                            <div class="autoflow-popup-info" style="grid-column:1 / -1;">
                                <span class="autoflow-popup-label">Funcionamento</span>
                                        <span class="autoflow-popup-value" style="font-size:12px;line-height:1.45;">${escapeHtml(location.opening_hours || 'Não informado')}</span>
                            </div>
                        </div>
                        <div class="autoflow-popup-actions">
                            <div class="autoflow-popup-action-row">
                                <a class="autoflow-popup-button is-full" href="#" data-popup-favorite-location="${escapeHtml(location.id)}">Favoritar unidade</a>
                            </div>
                            <div class="autoflow-popup-action-row">
                                ${whatsappButton}
                                <a class="autoflow-popup-button" href="${escapeHtml(directionsUrl)}" target="_blank" rel="noopener">Como chegar</a>
                            </div>
                            <div class="autoflow-popup-action-row">
                                <a class="autoflow-popup-button" href="#unidade-${escapeHtml(location.id)}" data-popup-focus-location="${escapeHtml(location.id)}">Ver na lista</a>
                                <a class="autoflow-popup-button" href="${escapeHtml(location.detail_url || '#')}">Ver detalhes</a>
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

                marker.off('popupopen');
                marker.on('popupopen', () => highlightLocationCard(location.id, false));
            };

            locations.forEach((location) => {
                if (! hasValidCoordinates(location)) {
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

            mapElement.addEventListener('click', (event) => {
                const favoriteLink = event.target.closest('[data-popup-favorite-location]');

                if (favoriteLink) {
                    event.preventDefault();
                    toggleFavoriteLocation(favoriteLink.dataset.popupFavoriteLocation);
                    return;
                }

                const focusLink = event.target.closest('[data-popup-focus-location]');

                if (! focusLink) {
                    return;
                }

                event.preventDefault();
                highlightLocationCard(focusLink.dataset.popupFocusLocation, true);
            });

            const applyUserLocationDistances = (latitude, longitude) => {
                const rankedLocations = locations
                    .map((location) => {
                        const destinationLatitude = Number(location.latitude);
                        const destinationLongitude = Number(location.longitude);

                        if (! hasValidCoordinates(location)) {
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

            document.querySelectorAll('[data-favorite-toggle]').forEach((button) => {
                button.addEventListener('click', () => toggleFavoriteLocation(button.dataset.locationId));
            });

            favoritesFilterButton?.addEventListener('click', () => {
                showOnlyFavorites = ! showOnlyFavorites;
                refreshFavoriteUi();
            });

            refreshFavoriteUi();

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
                    highlightLocationCard(link.dataset.focusLocation, true);
                });
            });
        });
    </script>
</body>
</html>
