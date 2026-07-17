<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $location->name }} · AutoFlow</title>
    <meta name="description" content="{{ $location->name }} em {{ $location->fullAddress() }}. Consulte status, horário de funcionamento, serviços, contato e rota.">
    <link rel="canonical" href="{{ route('public.locations.show', ['location' => $location->slug]) }}">
    <meta property="og:title" content="{{ $location->name }} · AutoFlow">
    <meta property="og:description" content="Veja status, serviços, horário e rota para {{ $location->name }}.">
    <meta property="og:type" content="business.business">
    <meta property="og:url" content="{{ route('public.locations.show', ['location' => $location->slug]) }}">
    @include('components.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'AutoWash',
            'name' => $location->name,
            'url' => route('public.locations.show', ['location' => $location->slug]),
            'telephone' => $location->phone,
            'image' => $location->logoUrl(),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => trim(collect([$location->address, $location->address_number])->filter()->implode(', ')),
                'addressLocality' => $location->city,
                'addressRegion' => $location->state,
                'addressCountry' => 'BR',
            ],
            'geo' => $location->hasCoordinates() ? [
                '@type' => 'GeoCoordinates',
                'latitude' => $location->mapLatitude(),
                'longitude' => $location->mapLongitude(),
            ] : null,
            'aggregateRating' => $testimonialsSummary['count'] > 0 ? [
                '@type' => 'AggregateRating',
                'ratingValue' => $testimonialsSummary['average'],
                'reviewCount' => $testimonialsSummary['count'],
                'bestRating' => 5,
                'worstRating' => 1,
            ] : null,
            'review' => $testimonials->map(fn ($testimonial) => [
                '@type' => 'Review',
                'reviewRating' => [
                    '@type' => 'Rating',
                    'ratingValue' => $testimonial['rating'],
                    'bestRating' => 5,
                    'worstRating' => 1,
                ],
                'author' => [
                    '@type' => 'Person',
                    'name' => $testimonial['author'],
                ],
                'reviewBody' => $testimonial['comment'],
                'datePublished' => $testimonial['reviewed_at'],
            ])->all(),
            'openingHoursSpecification' => collect($businessHours)->map(fn ($day) => [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => $day['day'],
                'description' => $day['hours'],
            ])->all(),
            'makesOffer' => $services->map(fn ($service) => [
                '@type' => 'Offer',
                'itemOffered' => [
                    '@type' => 'Service',
                    'name' => $service->name,
                    'description' => $service->description,
                ],
                'price' => (float) $service->base_price,
                'priceCurrency' => 'BRL',
            ])->all(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
</head>
<body class="bg-slate-950 text-slate-950 antialiased">
    @php
        $publicStatus = $operatingSummary['status'];
        $statusClass = match ($publicStatus) {
            \App\Models\WashLocation::STATUS_BUSY => 'bg-orange-100 text-orange-700 ring-orange-200',
            \App\Models\WashLocation::STATUS_CLOSED => 'bg-slate-100 text-slate-600 ring-slate-200',
            default => 'bg-green-100 text-green-700 ring-green-200',
        };
    @endphp

    <div class="min-h-screen bg-[radial-gradient(circle_at_top_left,#0ea5e9_0,transparent_32%),linear-gradient(135deg,#020617,#0f172a_55%,#082f49)] px-4 py-5 sm:px-6 lg:px-8">
        <header class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-4 rounded-3xl border border-white/10 bg-white/95 px-5 py-4 shadow-2xl shadow-black/25 backdrop-blur" data-tour="public-location-header">
            <a href="{{ route('public.locations.index') }}" class="flex items-center gap-3">
                <img src="{{ $location->logoUrl() }}" alt="{{ $location->name }}" class="h-12 max-w-36 object-contain">
                <div class="hidden sm:block">
                    <p class="text-sm font-bold text-slate-950">{{ $location->name }}</p>
                    <p class="text-xs text-slate-500">Detalhes da unidade</p>
                </div>
            </a>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('public.locations.index') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Voltar para o mapa</a>
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
                    <a href="{{ route('login') }}" class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-bold text-white">Entrar</a>
                @endauth
            </div>
        </header>

        <main class="mx-auto mt-6 grid max-w-5xl gap-5 lg:grid-cols-[1fr_320px]">
            <section class="overflow-hidden rounded-3xl border border-white/10 bg-white shadow-2xl shadow-black/25">
                <div class="border-b border-slate-200 p-6 sm:p-8" data-tour="public-location-hero">
                    <p class="text-xs font-bold uppercase tracking-[0.24em] text-blue-600">Lava-rápido</p>
                    <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h1 class="text-3xl font-black text-slate-950 sm:text-4xl">{{ $location->name }}</h1>
                            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">{{ $location->fullAddress() }}</p>
                            <div class="mt-4 flex flex-wrap gap-2" data-tour="public-location-primary-actions">
                                <a href="{{ $directionsUrl }}" target="_blank" rel="noopener" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-black text-white shadow-lg shadow-blue-900/20 hover:bg-blue-700">Como chegar</a>
                                @if ($whatsappUrl)
                                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="rounded-xl bg-green-600 px-4 py-2.5 text-sm font-black text-white shadow-lg shadow-green-900/20 hover:bg-green-700">Chamar no WhatsApp</a>
                                @endif
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="rounded-full px-3 py-1.5 text-xs font-black ring-1 {{ $statusClass }}">{{ $operatingSummary['status_label'] }}</span>
                            <p class="mt-2 text-sm font-black text-slate-950">{{ $operatingSummary['next_event'] }}</p>
                            <p class="mt-1 text-xs font-bold text-slate-500">{{ $operatingSummary['today_label'] }} · {{ $operatingSummary['today_hours'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 p-6 sm:grid-cols-3 sm:p-8" data-tour="public-location-summary">
                    <div class="rounded-2xl bg-blue-50 p-4">
                        <p class="text-xs font-semibold text-slate-500">Status</p>
                        <p class="mt-1 text-lg font-black text-blue-800">{{ $operatingSummary['status_label'] }}</p>
                        <p class="mt-1 text-xs font-bold text-blue-700">{{ $operatingSummary['next_event'] }}</p>
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

                <div class="border-t border-slate-200 p-6 sm:p-8" data-tour="public-location-profile">
                    <div class="grid gap-4 lg:grid-cols-[1fr_260px]">
                        <div>
                            <h2 class="text-xl font-black text-slate-950">Perfil da unidade</h2>
                            <p class="mt-1 text-sm text-slate-500">Informações públicas para decidir o melhor momento de ir até o lava-rápido.</p>
                            <dl class="mt-5 grid gap-3 sm:grid-cols-2">
                                <div class="rounded-2xl border border-slate-200 p-4">
                                    <dt class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Endereço</dt>
                                    <dd class="mt-2 text-sm font-bold leading-6 text-slate-900">{{ $location->fullAddress() }}</dd>
                                </div>
                                <div class="rounded-2xl border border-slate-200 p-4">
                                    <dt class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Contato</dt>
                                    <dd class="mt-2 text-sm font-bold leading-6 text-slate-900">{{ $location->phone ?: 'Não informado' }}</dd>
                                </div>
                            </dl>
                        </div>
                        <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4">
                            <p class="text-xs font-black uppercase tracking-[0.14em] text-blue-700">Funcionamento hoje</p>
                            <p class="mt-2 text-2xl font-black text-blue-950">{{ $operatingSummary['today_hours'] }}</p>
                            <p class="mt-1 text-sm font-bold text-blue-800">{{ $operatingSummary['next_event'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="border-t border-slate-200 p-6 sm:p-8" data-tour="public-location-hours">
                    <h2 class="text-xl font-black text-slate-950">Horários de funcionamento</h2>
                    <div class="mt-5 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($businessHours as $day)
                            <div class="rounded-2xl border {{ $day['is_today'] ? 'border-blue-200 bg-blue-50' : 'border-slate-200 bg-white' }} p-4">
                                <p class="text-xs font-black uppercase tracking-[0.14em] {{ $day['is_today'] ? 'text-blue-700' : 'text-slate-500' }}">{{ $day['day'] }}</p>
                                <p class="mt-2 font-black {{ $day['is_today'] ? 'text-blue-950' : 'text-slate-950' }}">{{ $day['hours'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="border-t border-slate-200 p-6 sm:p-8" data-tour="public-location-reviews">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-black text-slate-950">Avaliações dos clientes</h2>
                            <p class="mt-1 text-sm text-slate-500">Depoimentos enviados por clientes após a entrega do veículo.</p>
                        </div>
                        @if ($testimonialsSummary['count'] > 0)
                            <div class="rounded-2xl bg-amber-50 px-4 py-3 text-right">
                                <p class="text-xs font-black uppercase tracking-[0.14em] text-amber-700">Média</p>
                                <p class="mt-1 text-2xl font-black text-amber-900">{{ number_format((float) $testimonialsSummary['average'], 1, ',', '.') }} ★</p>
                                <p class="text-xs font-bold text-amber-800">{{ $testimonialsSummary['count'] }} depoimento{{ $testimonialsSummary['count'] === 1 ? '' : 's' }}</p>
                            </div>
                        @endif
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        @forelse ($testimonials as $testimonial)
                            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-black text-slate-950">{{ $testimonial['author'] }}</p>
                                        @if ($testimonial['service'])
                                            <p class="mt-1 text-xs font-bold text-slate-500">{{ $testimonial['service'] }}</p>
                                        @endif
                                    </div>
                                    <span class="shrink-0 rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-800">{{ str_repeat('★', $testimonial['rating']) }}</span>
                                </div>
                                <p class="mt-4 text-sm leading-6 text-slate-700">“{{ $testimonial['comment'] }}”</p>
                                @if ($testimonial['reviewed_at'])
                                    <p class="mt-3 text-xs font-bold text-slate-400">{{ $testimonial['reviewed_at'] }}</p>
                                @endif
                            </article>
                        @empty
                            <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500 sm:col-span-2">Esta unidade ainda não possui depoimentos publicados.</p>
                        @endforelse
                    </div>
                </div>

                <div class="border-t border-slate-200 p-6 sm:p-8" data-tour="public-location-services">
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
                <section class="rounded-3xl border border-white/10 bg-white p-5 shadow-2xl shadow-black/20" data-tour="public-location-sidebar-actions">
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
                    <p class="mt-2 text-sm font-black text-slate-950">{{ $operatingSummary['next_event'] }}</p>
                    <p class="mt-1 text-sm leading-6 text-slate-500">{{ $location->opening_hours ?: $location->openingHoursSummary() }}</p>
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

    @php
        $publicLocationTour = [
            'key' => 'public.locations.show.v1',
            'title' => 'Detalhes do lava-rápido',
            'steps' => [
                [
                    'target' => '[data-tour="public-location-header"]',
                    'title' => 'Página da unidade',
                    'body' => 'Esta página reúne as informações públicas do lava-rápido para o visitante decidir se quer ir até a unidade.',
                ],
                [
                    'target' => '[data-tour="public-location-hero"]',
                    'title' => 'Nome, endereço e status',
                    'body' => 'Confira rapidamente onde fica a unidade, se está aberta e qual é o próximo evento de funcionamento.',
                ],
                [
                    'target' => '[data-tour="public-location-primary-actions"]',
                    'title' => 'Contato e rota',
                    'body' => 'Os botões principais levam ao Google Maps e ao WhatsApp quando o contato está disponível.',
                ],
                [
                    'target' => '[data-tour="public-location-summary"]',
                    'title' => 'Resumo rápido',
                    'body' => 'Status, atendimentos em andamento e telefone ajudam o visitante a decidir antes de sair de casa.',
                ],
                [
                    'target' => '[data-tour="public-location-profile"]',
                    'title' => 'Perfil da unidade',
                    'body' => 'Aqui ficam endereço, contato e funcionamento de hoje em formato mais detalhado.',
                ],
                [
                    'target' => '[data-tour="public-location-hours"]',
                    'title' => 'Horários',
                    'body' => 'Veja os horários por dia da semana para evitar chegar com a unidade fechada.',
                ],
                [
                    'target' => '[data-tour="public-location-reviews"]',
                    'title' => 'Avaliações',
                    'body' => 'Depoimentos publicados ajudam novos clientes a avaliar qualidade e experiência de atendimento.',
                ],
                [
                    'target' => '[data-tour="public-location-services"]',
                    'title' => 'Serviços',
                    'body' => 'A lista mostra serviços ativos, duração estimada e preço divulgado pela unidade.',
                ],
                [
                    'target' => '[data-tour="public-location-sidebar-actions"]',
                    'title' => 'Ações rápidas',
                    'body' => 'Na lateral ficam atalhos permanentes para WhatsApp, rota e retorno ao mapa.',
                ],
            ],
        ];
    @endphp

    <script type="application/json" data-onboarding-tour>
        {!! json_encode($publicLocationTour, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
</body>
</html>
