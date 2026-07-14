<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $location->name }} · AutoFlow</title>
    @include('components.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-950 text-slate-950 antialiased">
    @php
        $publicStatus = $location->publicStatus();
        $statusClass = match ($publicStatus) {
            \App\Models\WashLocation::STATUS_BUSY => 'bg-orange-100 text-orange-700 ring-orange-200',
            \App\Models\WashLocation::STATUS_CLOSED => 'bg-slate-100 text-slate-600 ring-slate-200',
            default => 'bg-green-100 text-green-700 ring-green-200',
        };
    @endphp

    <div class="min-h-screen bg-[radial-gradient(circle_at_top_left,#0ea5e9_0,transparent_32%),linear-gradient(135deg,#020617,#0f172a_55%,#082f49)] px-4 py-5 sm:px-6 lg:px-8">
        <header class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-4 rounded-3xl border border-white/10 bg-white/95 px-5 py-4 shadow-2xl shadow-black/25 backdrop-blur">
            <a href="{{ route('public.locations.index') }}" class="flex items-center gap-3">
                <img src="{{ $location->logoUrl() }}" alt="{{ $location->name }}" class="h-12 max-w-36 object-contain">
                <div class="hidden sm:block">
                    <p class="text-sm font-bold text-slate-950">{{ $location->name }}</p>
                    <p class="text-xs text-slate-500">Detalhes da unidade</p>
                </div>
            </a>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('public.locations.index') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Voltar para o mapa</a>
                <a href="{{ route('login') }}" class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-bold text-white">Entrar</a>
            </div>
        </header>

        <main class="mx-auto mt-6 grid max-w-5xl gap-5 lg:grid-cols-[1fr_320px]">
            <section class="overflow-hidden rounded-3xl border border-white/10 bg-white shadow-2xl shadow-black/25">
                <div class="border-b border-slate-200 p-6 sm:p-8">
                    <p class="text-xs font-bold uppercase tracking-[0.24em] text-blue-600">Lava-rápido</p>
                    <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h1 class="text-3xl font-black text-slate-950 sm:text-4xl">{{ $location->name }}</h1>
                            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">{{ $location->fullAddress() }}</p>
                        </div>
                        <span class="rounded-full px-3 py-1.5 text-xs font-black ring-1 {{ $statusClass }}">{{ $location->publicStatusLabel() }}</span>
                    </div>
                </div>

                <div class="grid gap-4 p-6 sm:grid-cols-3 sm:p-8">
                    <div class="rounded-2xl bg-blue-50 p-4">
                        <p class="text-xs font-semibold text-slate-500">Status</p>
                        <p class="mt-1 text-lg font-black text-blue-800">{{ $location->publicStatusLabel() }}</p>
                    </div>
                    <div class="rounded-2xl bg-emerald-50 p-4">
                        <p class="text-xs font-semibold text-slate-500">Em atendimento</p>
                        <p class="mt-1 text-lg font-black text-emerald-800">{{ $location->active_orders_count }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs font-semibold text-slate-500">Contato</p>
                        <p class="mt-1 truncate text-lg font-black text-slate-950">{{ $location->phone ?: 'Não informado' }}</p>
                    </div>
                </div>

                <div class="border-t border-slate-200 p-6 sm:p-8">
                    <h2 class="text-xl font-black text-slate-950">Serviços disponíveis</h2>
                    <p class="mt-1 text-sm text-slate-500">Lista pública dos serviços cadastrados na plataforma.</p>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        @forelse ($services as $service)
                            <article class="rounded-2xl border border-slate-200 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 class="font-black text-slate-950">{{ $service->name }}</h3>
                                        @if ($service->description)
                                            <p class="mt-1 text-sm leading-5 text-slate-500">{{ $service->description }}</p>
                                        @endif
                                    </div>
                                    <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">{{ $service->estimated_minutes }} min</span>
                                </div>
                                <p class="mt-3 text-lg font-black text-blue-700">R$ {{ number_format((float) $service->base_price, 2, ',', '.') }}</p>
                            </article>
                        @empty
                            <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500 sm:col-span-2">Nenhum serviço ativo cadastrado para exibição pública.</p>
                        @endforelse
                    </div>
                </div>
            </section>

            <aside class="space-y-4">
                <section class="rounded-3xl border border-white/10 bg-white p-5 shadow-2xl shadow-black/20">
                    <h2 class="text-lg font-black text-slate-950">Ações rápidas</h2>
                    <div class="mt-4 grid gap-2">
                        @if ($whatsappUrl)
                            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="rounded-xl bg-green-600 px-4 py-3 text-center text-sm font-black text-white shadow-lg shadow-green-900/20 hover:bg-green-700">Chamar no WhatsApp</a>
                        @endif
                        <a href="{{ $directionsUrl }}" target="_blank" rel="noopener" class="rounded-xl bg-blue-600 px-4 py-3 text-center text-sm font-black text-white shadow-lg shadow-blue-900/20 hover:bg-blue-700">Como chegar</a>
                        <a href="{{ route('public.locations.index') }}#unidade-{{ $location->id }}" class="rounded-xl border border-slate-200 px-4 py-3 text-center text-sm font-black text-slate-700 hover:bg-slate-50">Ver no mapa</a>
                    </div>
                </section>

                <section class="rounded-3xl border border-white/10 bg-white p-5 shadow-2xl shadow-black/20">
                    <h2 class="text-lg font-black text-slate-950">Funcionamento</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">{{ $location->opening_hours ?: $location->openingHoursSummary() }}</p>
                </section>

                <section class="rounded-3xl border border-white/10 bg-white p-5 shadow-2xl shadow-black/20">
                    <h2 class="text-lg font-black text-slate-950">Endereço</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">{{ $location->fullAddress() }}</p>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div>
                            <dt class="font-bold text-slate-500">Bairro</dt>
                            <dd class="font-black text-slate-950">{{ $location->district ?: 'Não informado' }}</dd>
                        </div>
                        <div>
                            <dt class="font-bold text-slate-500">Cidade</dt>
                            <dd class="font-black text-slate-950">{{ $location->city ?: 'Não informada' }}</dd>
                        </div>
                    </dl>
                </section>
            </aside>
        </main>
    </div>
</body>
</html>
