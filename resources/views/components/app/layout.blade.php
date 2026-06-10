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
<body class="{{ $appTheme === 'dark' ? 'bg-slate-950' : 'bg-[#061832]' }} text-slate-950 antialiased" data-theme="{{ $appTheme }}" data-theme-effective="{{ $appTheme === 'system' ? 'light' : $appTheme }}">
    <div class="min-h-screen p-2 lg:p-3" data-app-shell>
        <aside data-sidebar class="fixed inset-y-3 left-3 z-30 hidden w-72 flex-col rounded-2xl {{ $appTheme === 'dark' ? 'bg-slate-950' : 'bg-[#061b36]' }} px-3 py-3 text-white shadow-2xl shadow-black/30 transition-transform duration-200 lg:flex">
            <a href="{{ route('dashboard') }}" class="block shrink-0 rounded-2xl bg-white px-6 py-6 shadow-inner shadow-slate-200">
                <img src="{{ asset('images/autoflow-logo.png') }}" alt="AutoFlow" class="mx-auto h-auto w-52">
            </a>

            <nav class="mt-4 min-h-0 flex-1 space-y-1.5 overflow-y-auto pr-1">
                @foreach ([
                    ['route' => 'dashboard', 'label' => 'Painel Principal', 'icon' => 'P', 'roles' => null],
                    ['route' => 'wash-orders.index', 'label' => 'Lavagens', 'icon' => 'L', 'roles' => null],
                    ['route' => 'kanban', 'label' => 'Kanban de Lavagens', 'icon' => 'K', 'roles' => null],
                    ['route' => 'history.index', 'label' => 'Historico', 'icon' => 'H', 'roles' => null],
                    ['route' => 'customers.index', 'label' => 'Clientes', 'icon' => 'C', 'roles' => ['admin', 'attendant']],
                    ['route' => 'vehicles.index', 'label' => 'Veiculos', 'icon' => 'V', 'roles' => ['admin', 'attendant']],
                    ['route' => 'services.index', 'label' => 'Servicos', 'icon' => 'S', 'roles' => ['admin']],
                    ['route' => 'employees.index', 'label' => 'Funcionarios', 'icon' => 'F', 'roles' => ['admin']],
                    ['route' => 'finance.index', 'label' => 'Financeiro', 'icon' => '$', 'roles' => ['admin']],
                    ['route' => 'finance.cash-registers.index', 'label' => 'Caixa', 'icon' => 'CX', 'roles' => ['admin'], 'module' => 'module_cash_register'],
                    ['route' => 'finance.credit-receivables.index', 'label' => 'Fiado', 'icon' => 'F$', 'roles' => ['admin'], 'module' => 'module_credit_receivables'],
                ] as $item)
                    @continue($item['roles'] && ! auth()->user()->hasAnyRole($item['roles']))
                    @continue(($item['module'] ?? null) && empty($appSettings[$item['module']]))
                    <a href="{{ route($item['route']) }}" class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium transition {{ request()->routeIs($item['route']) || request()->routeIs(str_replace('.index', '.*', $item['route'])) ? 'bg-blue-600 text-white shadow-lg shadow-blue-950/30' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border border-white/15 bg-white/10 text-xs font-bold">{{ $item['icon'] }}</span>
                        {{ $item['label'] }}
                    </a>
                @endforeach

                @foreach ([
                    ['label' => 'Relatorios', 'icon' => 'R', 'href' => auth()->user()->isAdmin() ? route('finance.index') : route('dashboard'), 'roles' => ['admin']],
                    ['label' => 'Unidades', 'icon' => 'U', 'href' => route('locations.map')],
                    ['label' => 'Mapa', 'icon' => 'M', 'href' => route('locations.map')],
                    ['label' => 'Configuracoes', 'icon' => 'G', 'href' => route('settings.edit'), 'roles' => ['admin']],
                ] as $item)
                    @continue(($item['roles'] ?? null) && ! auth()->user()->hasAnyRole($item['roles']))
                    <a href="{{ $item['href'] }}" class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium text-slate-200 transition hover:bg-white/10 hover:text-white">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border border-white/15 bg-white/10 text-xs font-bold">{{ $item['icon'] }}</span>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="mt-4 shrink-0 rounded-2xl border border-white/10 bg-[#082646] p-3 text-center shadow-inner shadow-white/5">
                <div class="mx-auto flex h-20 w-full items-end justify-center overflow-hidden rounded-xl bg-gradient-to-t from-blue-950 to-blue-700">
                    <img src="{{ asset('images/autoflow-logo.png') }}" alt="AutoFlow" class="mb-2 w-24 opacity-90">
                </div>
                <p class="mt-3 text-sm font-semibold">Organize. Acompanhe.</p>
                <p class="text-sm font-semibold text-cyan-300">Fidelize.</p>
                <p class="mt-2 text-[11px] leading-4 text-slate-300">Mais controle, eficiencia e satisfacao para seus clientes.</p>
            </div>
        </aside>

        <div data-content class="min-h-[calc(100vh-16px)] overflow-hidden rounded-2xl {{ $appTheme === 'dark' ? 'bg-slate-900 text-slate-100' : 'bg-slate-50' }} shadow-2xl shadow-black/30 transition-[margin] duration-200 lg:ml-80">
            <header class="sticky top-0 z-20 border-b {{ $appTheme === 'dark' ? 'border-slate-800 bg-slate-950/95' : 'border-slate-200 bg-white/95' }} px-4 py-4 backdrop-blur sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <button type="button" data-sidebar-toggle aria-label="Ocultar menu" aria-expanded="true" class="hidden h-10 min-w-10 items-center justify-center rounded-lg border border-slate-200 px-3 text-sm font-semibold text-slate-700 hover:bg-slate-100 lg:inline-flex">
                            <span data-sidebar-toggle-icon>Fechar</span>
                        </button>
                        <div>
                            <h1 class="text-xl font-bold {{ $appTheme === 'dark' ? 'text-white' : 'text-slate-950' }} sm:text-2xl">{{ $heading ?? 'Painel Principal' }}</h1>
                            <p class="text-sm text-slate-500">Aqui esta o resumo do que acontece hoje.</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="hidden items-center gap-3 rounded-lg border border-slate-200 bg-white px-4 py-2 shadow-sm md:flex">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-sm font-bold text-blue-700">U</span>
                            <div>
                                <p class="text-xs text-slate-500">Unidade atual</p>
                                <p class="text-sm font-semibold">{{ $appSettings['company_name'] ?? 'Lava Rapido Central' }}</p>
                            </div>
                        </div>
                        <div class="hidden items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 shadow-sm md:flex" data-theme-status>
                            <span class="h-2.5 w-2.5 rounded-full bg-cyan-400"></span>
                            <span>Tema {{ $appTheme === 'dark' ? 'dark ativo' : ($appTheme === 'system' ? 'sistema' : 'claro') }}</span>
                        </div>
                        <div class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-cyan-400 text-sm font-bold text-white">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                            <div class="hidden sm:block">
                                <p class="text-sm font-semibold">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-slate-500">{{ ucfirst(auth()->user()->role) }}</p>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="rounded-lg bg-slate-950 px-3 py-2 text-sm font-semibold text-white">Sair</button>
                        </form>
                    </div>
                </div>

                <nav class="mt-4 flex gap-2 overflow-x-auto lg:hidden">
                    <a href="{{ route('dashboard') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">Painel</a>
                    <a href="{{ route('wash-orders.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">Lavagens</a>
                    <a href="{{ route('kanban') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">Kanban</a>
                    <a href="{{ route('history.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">Historico</a>
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('finance.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">Financeiro</a>
                        @if (! empty($appSettings['module_cash_register']))
                            <a href="{{ route('finance.cash-registers.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">Caixa</a>
                        @endif
                        @if (! empty($appSettings['module_credit_receivables']))
                            <a href="{{ route('finance.credit-receivables.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">Fiado</a>
                        @endif
                        <a href="{{ route('employees.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">Funcionarios</a>
                        <a href="{{ route('settings.edit') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">Configuracoes</a>
                    @endif
                    <a href="{{ route('locations.map') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">Mapa</a>
                </nav>
            </header>

            <main class="px-4 py-5 sm:px-6 lg:px-8">
                @if (session('status'))
                    <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
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
        const sidebarToggleIcon = document.querySelector('[data-sidebar-toggle-icon]');
        const sidebarStorageKey = 'autoflow.sidebar.collapsed';

        const applySidebarState = (collapsed) => {
            sidebar.classList.toggle('-translate-x-[calc(100%+1rem)]', collapsed);
            content.classList.toggle('lg:ml-80', !collapsed);
            content.classList.toggle('lg:ml-0', collapsed);
            sidebarToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            sidebarToggle.setAttribute('aria-label', collapsed ? 'Mostrar menu' : 'Ocultar menu');
            sidebarToggleIcon.textContent = collapsed ? 'Menu' : 'Fechar';
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
