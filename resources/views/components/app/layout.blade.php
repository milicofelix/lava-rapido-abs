<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'AutoFlow' }}</title>
    <script>
        (() => {
            const preferredTheme = @json(\App\Models\AppSetting::theme());
            const resolvedTheme = preferredTheme === 'system'
                ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                : preferredTheme;

            document.documentElement.dataset.theme = preferredTheme;
            document.documentElement.dataset.themeEffective = resolvedTheme;
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php($appSettings = \App\Models\AppSetting::allSettings())
@php($appTheme = \App\Models\AppSetting::theme())
@php($currentLocation = \App\Support\TenantContext::currentLocation())
@php($isSuperAdmin = auth()->user()->isSuperAdmin())
@php($unitDisplayName = $currentLocation?->name ?? ($isSuperAdmin ? 'AutoFlow Admin' : ($appSettings['company_name'] ?? 'AutoFlow')))
@php($unitStatusLabel = $currentLocation?->accountStatusLabel())
@php($trialDaysRemaining = $currentLocation?->trialDaysRemaining())
@php($homeRoute = $isSuperAdmin ? route('super-admin.location-requests.index') : route('dashboard'))
<body class="{{ $appTheme === 'dark' ? 'bg-slate-950' : 'bg-[#061832]' }} text-slate-950 antialiased" data-theme="{{ $appTheme }}" data-theme-effective="{{ $appTheme === 'system' ? 'light' : $appTheme }}">
    <div class="min-h-screen p-2 lg:p-3" data-app-shell>
        <aside data-sidebar class="fixed inset-y-3 left-3 z-30 hidden w-[17rem] flex-col rounded-2xl {{ $appTheme === 'dark' ? 'bg-slate-950' : 'bg-[#061b36]' }} px-3 py-3 text-white shadow-2xl shadow-black/30 transition-transform duration-200 lg:flex">
            <a href="{{ $homeRoute }}" class="block shrink-0 rounded-xl bg-white px-4 py-4 shadow-inner shadow-slate-200">
                <img src="{{ asset('images/autoflow-logo.png') }}" alt="AutoFlow" class="mx-auto h-auto w-40">
            </a>

            <nav class="mt-3 min-h-0 flex-1 space-y-1 overflow-y-auto pr-1">
                @if ($isSuperAdmin)
                    <a href="{{ route('super-admin.location-requests.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('super-admin.location-requests.*') ? 'bg-blue-600 text-white shadow-lg shadow-blue-950/30' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md border border-white/15 bg-white/10 text-[11px] font-bold">S</span>
                        Solicitações de cadastros
                    </a>
                    <a href="{{ route('super-admin.locations.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('super-admin.locations.*') ? 'bg-blue-600 text-white shadow-lg shadow-blue-950/30' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md border border-white/15 bg-white/10 text-[11px] font-bold">U</span>
                        Unidades
                    </a>
                    <a href="{{ route('super-admin.plans.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('super-admin.plans.*') ? 'bg-blue-600 text-white shadow-lg shadow-blue-950/30' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md border border-white/15 bg-white/10 text-[11px] font-bold">P</span>
                        Planos
                    </a>
                @else
                    @foreach ([
                        ['route' => 'dashboard', 'label' => 'Painel Principal', 'icon' => 'P', 'roles' => null],
                        ['route' => 'wash-orders.index', 'label' => 'Lavagens', 'icon' => 'L', 'roles' => null],
                        ['route' => 'kanban', 'label' => 'Kanban de Lavagens', 'icon' => 'K', 'roles' => null],
                        ['route' => 'history.index', 'label' => 'Historico', 'icon' => 'H', 'roles' => null],
                        ['route' => 'customers.index', 'label' => 'Clientes', 'icon' => 'C', 'roles' => ['owner', 'admin', 'attendant']],
                        ['route' => 'vehicles.index', 'label' => 'Veiculos', 'icon' => 'V', 'roles' => ['owner', 'admin', 'attendant']],
                        ['route' => 'services.index', 'label' => 'Servicos', 'icon' => 'S', 'roles' => ['owner', 'admin']],
                        ['route' => 'employees.index', 'label' => 'Equipe', 'icon' => 'E', 'roles' => ['owner', 'admin']],
                        ['route' => 'finance.index', 'label' => 'Financeiro', 'icon' => '$', 'roles' => ['owner', 'admin']],
                        ['route' => 'finance.cash-registers.index', 'label' => 'Caixa', 'icon' => 'CX', 'roles' => ['owner', 'admin'], 'module' => 'module_cash_register'],
                        ['route' => 'finance.credit-receivables.index', 'label' => 'Fiado', 'icon' => 'F$', 'roles' => ['owner', 'admin'], 'module' => 'module_credit_receivables'],
                    ] as $item)
                        @continue($item['roles'] && ! auth()->user()->hasAnyRole($item['roles']))
                        @continue(($item['module'] ?? null) && empty($appSettings[$item['module']]))
                        <a href="{{ route($item['route']) }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs($item['route']) || request()->routeIs(str_replace('.index', '.*', $item['route'])) ? 'bg-blue-600 text-white shadow-lg shadow-blue-950/30' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md border border-white/15 bg-white/10 text-[11px] font-bold">{{ $item['icon'] }}</span>
                            {{ $item['label'] }}
                        </a>
                    @endforeach

                    @foreach ([
                        ['label' => 'Relatorios', 'icon' => 'R', 'href' => auth()->user()->isTeamManager() ? route('finance.index') : route('dashboard'), 'roles' => ['owner', 'admin']],
                        ['label' => 'Assinatura', 'icon' => 'A', 'href' => route('subscriptions.show'), 'roles' => ['owner']],
                        ['label' => 'Configuracoes', 'icon' => 'G', 'href' => route('settings.edit'), 'roles' => ['owner', 'admin']],
                    ] as $item)
                        @continue(($item['roles'] ?? null) && ! auth()->user()->hasAnyRole($item['roles']))
                        <a href="{{ $item['href'] }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-200 transition hover:bg-white/10 hover:text-white">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md border border-white/15 bg-white/10 text-[11px] font-bold">{{ $item['icon'] }}</span>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                @endif

            </nav>

            <div class="mt-3 shrink-0 rounded-xl border border-white/10 bg-white/[0.06] px-3 py-3 shadow-inner shadow-white/5">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-sm font-black text-white shadow-lg shadow-blue-950/30">A</span>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold leading-5">Organize. Acompanhe.</p>
                        <p class="truncate text-xs font-semibold text-cyan-300">Fidelize seus clientes.</p>
                    </div>
                </div>
            </div>
        </aside>

        <div data-content class="min-h-[calc(100vh-16px)] overflow-hidden rounded-2xl {{ $appTheme === 'dark' ? 'bg-slate-900 text-slate-100' : 'bg-slate-50' }} shadow-2xl shadow-black/30 transition-[margin] duration-200 lg:ml-[18rem]">
            <header class="sticky top-0 z-20 border-b {{ $appTheme === 'dark' ? 'border-slate-800 bg-slate-950/95' : 'border-slate-200 bg-white/95' }} px-4 py-3 backdrop-blur sm:px-6 lg:px-7">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <button type="button" data-sidebar-toggle aria-label="Ocultar menu" title="Ocultar menu" aria-expanded="true" class="hidden h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-700 shadow-sm transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-100 lg:inline-flex">
                            <svg data-sidebar-icon-open xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="4" width="18" height="16" rx="2"></rect>
                                <path d="M9 4v16"></path>
                                <path d="M14 10l-2 2 2 2"></path>
                            </svg>
                            <svg data-sidebar-icon-closed xmlns="http://www.w3.org/2000/svg" class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M4 6h16"></path>
                                <path d="M4 12h16"></path>
                                <path d="M4 18h16"></path>
                            </svg>
                        </button>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="truncate text-lg font-bold {{ $appTheme === 'dark' ? 'text-white' : 'text-slate-950' }} sm:text-xl">{{ $heading ?? 'Painel Principal' }}</h1>
                                @if ($currentLocation)
                                    <span class="rounded-full {{ $currentLocation->account_status === \App\Models\WashLocation::ACCOUNT_STATUS_ACTIVE ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-amber-200' }} px-2 py-0.5 text-[11px] font-bold ring-1">
                                        {{ $unitStatusLabel }}
                                    </span>
                                @endif
                            </div>
                            <p class="truncate text-xs text-slate-500">{{ $isSuperAdmin ? 'Gerencie aprovações, unidades e planos.' : $unitDisplayName }}</p>
                        </div>
                    </div>

                    <div class="flex min-w-0 items-center gap-2">
                        <div class="hidden max-w-[18rem] items-center gap-3 rounded-xl border {{ $appTheme === 'dark' ? 'border-slate-700 bg-slate-900' : 'border-slate-200 bg-white' }} px-3 py-2 shadow-sm md:flex">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $currentLocation ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-700' }} text-xs font-black">{{ $currentLocation ? strtoupper(substr($currentLocation->name, 0, 1)) : 'A' }}</span>
                            <div class="min-w-0">
                                <p class="text-xs text-slate-500">{{ $isSuperAdmin ? 'Administração do produto' : 'Unidade atual' }}</p>
                                <p class="truncate text-sm font-semibold {{ $appTheme === 'dark' ? 'text-white' : 'text-slate-950' }}">{{ $unitDisplayName }}</p>
                                @if ($currentLocation)
                                    <p class="truncate text-[11px] font-semibold text-slate-500">
                                        @if ($trialDaysRemaining !== null && $currentLocation->account_status === \App\Models\WashLocation::ACCOUNT_STATUS_TRIAL)
                                            Trial: {{ $trialDaysRemaining }} dia{{ $trialDaysRemaining === 1 ? '' : 's' }}
                                        @else
                                            {{ $unitStatusLabel }}
                                        @endif
                                    </p>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2 rounded-xl border {{ $appTheme === 'dark' ? 'border-slate-700 bg-slate-900' : 'border-slate-200 bg-white' }} px-2.5 py-2 shadow-sm">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-cyan-400 text-xs font-bold text-white">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                            <div class="hidden sm:block">
                                <p class="max-w-32 truncate text-sm font-semibold">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-slate-500">{{ auth()->user()->roleLabel() }}</p>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-slate-950 text-white transition hover:bg-slate-800" aria-label="Sair" title="Sair">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                    <path d="M16 17l5-5-5-5"></path>
                                    <path d="M21 12H9"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>

                <nav class="mt-3 flex gap-2 overflow-x-auto pb-0.5 lg:hidden">
                    @if ($isSuperAdmin)
                        <a href="{{ route('super-admin.location-requests.index') }}" class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium">Solicitações</a>
                        <a href="{{ route('super-admin.locations.index') }}" class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium">Unidades</a>
                        <a href="{{ route('super-admin.plans.index') }}" class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium">Planos</a>
                    @else
                        <a href="{{ route('dashboard') }}" class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium">Painel</a>
                        <a href="{{ route('wash-orders.index') }}" class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium">Lavagens</a>
                        <a href="{{ route('kanban') }}" class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium">Kanban</a>
                        <a href="{{ route('history.index') }}" class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium">Historico</a>
                        @if (auth()->user()->hasAnyRole([\App\Models\User::ROLE_OWNER, \App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_ATTENDANT]))
                            <a href="{{ route('customers.index') }}" class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium">Clientes</a>
                            <a href="{{ route('vehicles.index') }}" class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium">Veiculos</a>
                        @endif
                        @if (auth()->user()->isTeamManager())
                            <a href="{{ route('finance.index') }}" class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium">Financeiro</a>
                            @if (! empty($appSettings['module_cash_register']))
                                <a href="{{ route('finance.cash-registers.index') }}" class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium">Caixa</a>
                            @endif
                            @if (! empty($appSettings['module_credit_receivables']))
                                <a href="{{ route('finance.credit-receivables.index') }}" class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium">Fiado</a>
                            @endif
                            <a href="{{ route('employees.index') }}" class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium">Equipe</a>
                            @if (auth()->user()->isOwner())
                                <a href="{{ route('subscriptions.show') }}" class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium">Assinatura</a>
                            @endif
                            <a href="{{ route('settings.edit') }}" class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium">Configuracoes</a>
                        @endif
                    @endif
                </nav>
            </header>

            <main class="px-4 py-5 sm:px-6 lg:px-8">
                @if (session('status'))
                    <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
                @endif

                @if ($currentLocation && $currentLocation->subscriptionStatus() === \App\Models\WashLocation::ACCOUNT_STATUS_TRIAL && $trialDaysRemaining !== null && $trialDaysRemaining <= 5)
                    <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        <strong>Trial em andamento:</strong>
                        restam {{ $trialDaysRemaining }} dia{{ $trialDaysRemaining === 1 ? '' : 's' }} para ativar a assinatura da unidade.
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    <script>
        const preferredTheme = document.body.dataset.theme || 'light';
        const themeMedia = window.matchMedia('(prefers-color-scheme: dark)');
        const applyResolvedTheme = () => {
            const resolvedTheme = preferredTheme === 'system' && themeMedia.matches ? 'dark' : (preferredTheme === 'dark' ? 'dark' : 'light');

            document.documentElement.dataset.theme = preferredTheme;
            document.documentElement.dataset.themeEffective = resolvedTheme;
            document.body.dataset.themeEffective = resolvedTheme;
        };

        applyResolvedTheme();
        themeMedia.addEventListener?.('change', applyResolvedTheme);

        const shell = document.querySelector('[data-app-shell]');
        const sidebar = document.querySelector('[data-sidebar]');
        const content = document.querySelector('[data-content]');
        const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
        const sidebarIconOpen = document.querySelector('[data-sidebar-icon-open]');
        const sidebarIconClosed = document.querySelector('[data-sidebar-icon-closed]');
        const sidebarStorageKey = 'autoflow.sidebar.collapsed';

        const applySidebarState = (collapsed) => {
            sidebar.classList.toggle('-translate-x-[calc(100%+1rem)]', collapsed);
            content.classList.toggle('lg:ml-[18rem]', !collapsed);
            content.classList.toggle('lg:ml-0', collapsed);
            sidebarToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            sidebarToggle.setAttribute('aria-label', collapsed ? 'Mostrar menu' : 'Ocultar menu');
            sidebarToggle.setAttribute('title', collapsed ? 'Mostrar menu' : 'Ocultar menu');
            sidebarIconOpen.classList.toggle('hidden', collapsed);
            sidebarIconClosed.classList.toggle('hidden', !collapsed);
            shell.dataset.sidebarCollapsed = collapsed ? 'true' : 'false';
        };

        const initialSidebarCollapsed = localStorage.getItem(sidebarStorageKey) === 'true';
        applySidebarState(initialSidebarCollapsed);

        sidebarToggle.addEventListener('click', () => {
            const collapsed = shell.dataset.sidebarCollapsed !== 'true';

            localStorage.setItem(sidebarStorageKey, collapsed ? 'true' : 'false');
            applySidebarState(collapsed);
        });

        document.querySelectorAll('[data-copy-message-button]').forEach((button) => {
            button.addEventListener('click', async () => {
                const textarea = button.closest('section')?.querySelector('[data-copy-message]');

                if (! textarea) {
                    return;
                }

                try {
                    await navigator.clipboard.writeText(textarea.value);
                    button.textContent = 'Mensagem copiada';
                } catch (error) {
                    textarea.select();
                    document.execCommand('copy');
                    button.textContent = 'Mensagem copiada';
                }
            });
        });
    </script>
</body>
</html>
