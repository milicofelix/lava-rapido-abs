<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cadastre seu lava-rápido · AutoFlow</title>
    @include('components.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-950 text-slate-950 antialiased">
    <div class="min-h-screen bg-[radial-gradient(circle_at_top_left,#0ea5e9_0,transparent_32%),linear-gradient(135deg,#020617,#0f172a_55%,#082f49)] px-4 py-5 sm:px-6 lg:px-8">
        <header class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-4 rounded-3xl border border-white/10 bg-white/95 px-5 py-4 shadow-2xl shadow-black/25 backdrop-blur" data-tour="location-request-header">
            <a href="{{ route('public.locations.index') }}" class="flex items-center gap-3">
                <img src="{{ asset('images/autoflow-logo.png') }}" alt="AutoFlow" class="h-12 w-auto">
                <div class="hidden sm:block">
                    <p class="text-sm font-bold text-slate-950">AutoFlow</p>
                    <p class="text-xs text-slate-500">Gestão inteligente para lava-rápidos</p>
                </div>
            </a>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('public.locations.index') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Ver mapa</a>
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

        <main class="mx-auto mt-6 grid max-w-5xl gap-5 lg:grid-cols-[0.9fr_1.1fr]">
            <section class="rounded-3xl border border-white/10 bg-white/95 p-6 shadow-2xl shadow-black/25" data-tour="location-request-flow">
                <p class="text-xs font-black uppercase tracking-[0.24em] text-blue-600">Período gratuito sob aprovação</p>
                <h1 class="mt-3 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">Cadastre seu lava-rápido no AutoFlow</h1>
                <p class="mt-4 text-base leading-7 text-slate-600">Envie os dados do estabelecimento para análise. Depois da aprovação, sua unidade poderá iniciar um período gratuito de 15 dias e aparecer no mapa público.</p>

                <div class="mt-6 space-y-3 rounded-2xl bg-slate-50 p-4 text-sm text-slate-600 ring-1 ring-slate-200">
                    <div class="flex gap-3">
                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-blue-600 text-xs font-black text-white">1</span>
                        <p><strong class="text-slate-950">Solicitação pendente:</strong> os dados entram para revisão manual.</p>
                    </div>
                    <div class="flex gap-3">
                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-blue-600 text-xs font-black text-white">2</span>
                        <p><strong class="text-slate-950">Validação:</strong> verificamos contato, endereço e duplicidades antes de publicar.</p>
                    </div>
                    <div class="flex gap-3">
                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-blue-600 text-xs font-black text-white">3</span>
                        <p><strong class="text-slate-950">Período gratuito:</strong> após aprovação, o acesso inicial começa com controle pelo painel.</p>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-white/10 bg-white p-6 shadow-2xl shadow-black/25" data-tour="location-request-form">
                <h2 class="text-xl font-black text-slate-950">Solicitação de cadastro</h2>
                <p class="mt-1 text-sm text-slate-500">Preencha com dados reais para evitar reprovação.</p>

                @if ($errors->any())
                    <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700">
                        Revise os campos destacados antes de enviar.
                    </div>
                @endif

                <form method="POST" action="{{ route('public.location-requests.store') }}" class="mt-6 space-y-5">
                    @csrf

                    <div class="grid gap-4 sm:grid-cols-2" data-tour="location-request-owner">
                        <label class="block">
                            <span class="text-sm font-bold text-slate-700">Responsável *</span>
                            <input name="responsible_name" value="{{ old('responsible_name') }}" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            @error('responsible_name')<span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-bold text-slate-700">WhatsApp *</span>
                            <input name="phone" value="{{ old('phone') }}" required placeholder="(11) 99999-9999" data-mask="phone" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            @error('phone')<span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
                        </label>
                    </div>

                    <label class="block">
                        <span class="text-sm font-bold text-slate-700">E-mail *</span>
                        <input type="email" name="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        @error('email')<span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
                    </label>

                    <div class="grid gap-4 sm:grid-cols-2" data-tour="location-request-password">
                        <label class="block">
                            <span class="text-sm font-bold text-slate-700">Senha de primeiro acesso *</span>
                            <input type="password" name="password" required minlength="8" autocomplete="new-password" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            @error('password')<span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-bold text-slate-700">Confirmar senha *</span>
                            <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        </label>
                    </div>

                    <label class="block" data-tour="location-request-business">
                        <span class="text-sm font-bold text-slate-700">Nome do lava-rápido *</span>
                        <input name="business_name" value="{{ old('business_name') }}" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        @error('business_name')<span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
                    </label>

                    <div class="grid gap-4 sm:grid-cols-[0.8fr_1.2fr_0.6fr]" data-tour="location-request-address">
                        <label class="block">
                            <span class="text-sm font-bold text-slate-700">CEP</span>
                            <input name="zip_code" value="{{ old('zip_code') }}" data-mask="cep" data-viacep-trigger class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            @error('zip_code')<span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-bold text-slate-700">Logradouro *</span>
                            <input name="address" value="{{ old('address') }}" required data-address-field="address" placeholder="Rua, avenida ou estrada" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            @error('address')<span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-bold text-slate-700">Número *</span>
                            <input name="address_number" value="{{ old('address_number') }}" required placeholder="123" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            @error('address_number')<span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
                        </label>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-[1fr_1fr_90px]">
                        <label class="block">
                            <span class="text-sm font-bold text-slate-700">Bairro</span>
                            <input name="district" value="{{ old('district') }}" data-address-field="district" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            @error('district')<span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-bold text-slate-700">Cidade *</span>
                            <input name="city" value="{{ old('city') }}" required data-address-field="city" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            @error('city')<span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-bold text-slate-700">UF *</span>
                            <input name="state" value="{{ old('state') }}" required maxlength="2" data-address-field="state" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm uppercase focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            @error('state')<span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
                        </label>
                    </div>

                    <label class="block">
                        <span class="text-sm font-bold text-slate-700">Quantidade de funcionários</span>
                        <input type="number" min="1" max="500" name="employees_count" value="{{ old('employees_count') }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        @error('employees_count')<span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-bold text-slate-700">Mensagem opcional</span>
                        <textarea name="notes" rows="4" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">{{ old('notes') }}</textarea>
                        @error('notes')<span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
                    </label>

                    <label class="flex gap-3 rounded-2xl bg-slate-50 p-4 text-sm font-semibold text-slate-600 ring-1 ring-slate-200" data-tour="location-request-terms">
                        <input type="checkbox" name="accept_terms" value="1" @checked(old('accept_terms')) class="mt-1 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span>Confirmo que os dados informados são verdadeiros e entendo que o cadastro passará por análise antes de aparecer no mapa.</span>
                    </label>
                    @error('accept_terms')<span class="-mt-3 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror

                    <button class="w-full rounded-2xl bg-blue-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-blue-900/20 hover:bg-blue-700" data-tour="location-request-submit">Enviar solicitação</button>
                </form>
            </section>
        </main>
    </div>

    @php
        $locationRequestTour = [
            'key' => 'public.location-requests.create.v1',
            'title' => 'Cadastro do lava-rápido',
            'steps' => [
                [
                    'target' => '[data-tour="location-request-header"]',
                    'title' => 'Cadastro público',
                    'body' => 'Esta tela recebe solicitações de novos lava-rápidos para análise antes da publicação no mapa.',
                ],
                [
                    'target' => '[data-tour="location-request-flow"]',
                    'title' => 'Como funciona',
                    'body' => 'O cadastro passa por revisão manual, validação de dados e, após aprovação, libera o período gratuito da unidade.',
                ],
                [
                    'target' => '[data-tour="location-request-form"]',
                    'title' => 'Solicitação',
                    'body' => 'Preencha dados reais. Eles serão usados pelo Super Admin para aprovar ou rejeitar o cadastro.',
                ],
                [
                    'target' => '[data-tour="location-request-owner"]',
                    'title' => 'Responsável',
                    'body' => 'Informe quem será o dono do acesso inicial e o WhatsApp para contato sobre a aprovação.',
                ],
                [
                    'target' => '[data-tour="location-request-password"]',
                    'title' => 'Primeiro acesso',
                    'body' => 'A senha informada aqui será usada pelo responsável quando a unidade for aprovada.',
                ],
                [
                    'target' => '[data-tour="location-request-business"]',
                    'title' => 'Nome da unidade',
                    'body' => 'Use o nome comercial do lava-rápido, pois ele aparecerá no painel e no mapa público.',
                ],
                [
                    'target' => '[data-tour="location-request-address"]',
                    'title' => 'Endereço',
                    'body' => 'Informe CEP e endereço completo. O CEP pode preencher logradouro, bairro, cidade e UF automaticamente.',
                ],
                [
                    'target' => '[data-tour="location-request-terms"]',
                    'title' => 'Confirmação',
                    'body' => 'A solicitação só deve ser enviada quando os dados forem verdadeiros e estiverem prontos para análise.',
                ],
                [
                    'target' => '[data-tour="location-request-submit"]',
                    'title' => 'Enviar para análise',
                    'body' => 'Depois do envio, a solicitação fica pendente até o Super Admin revisar e aprovar.',
                ],
            ],
        ];
    @endphp

    <script type="application/json" data-onboarding-tour>
        {!! json_encode($locationRequestTour, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
</body>
</html>
