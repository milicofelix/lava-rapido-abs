<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'AutoFlow' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-zinc-50 text-zinc-950 antialiased">
    <div class="min-h-screen" data-app-shell>
        <aside data-sidebar class="fixed inset-y-0 left-0 hidden w-64 border-r border-zinc-200 bg-white px-5 py-6 transition-transform duration-200 lg:block">
            <a href="{{ route('dashboard') }}" class="block rounded-md bg-white">
                <img src="{{ asset('images/autoflow-logo.png') }}" alt="AutoFlow" class="h-auto w-36">
            </a>

            <nav class="mt-8 space-y-1">
                @foreach ([
                    ['route' => 'dashboard', 'label' => 'Dashboard'],
                    ['route' => 'wash-orders.index', 'label' => 'Lavagens'],
                    ['route' => 'kanban', 'label' => 'Kanban'],
                    ['route' => 'finance.index', 'label' => 'Financeiro'],
                    ['route' => 'customers.index', 'label' => 'Clientes'],
                    ['route' => 'vehicles.index', 'label' => 'Veiculos'],
                    ['route' => 'services.index', 'label' => 'Servicos'],
                    ['route' => 'employees.index', 'label' => 'Funcionarios'],
                ] as $item)
                    <a href="{{ route($item['route']) }}" class="block rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs($item['route']) || request()->routeIs(str_replace('.index', '.*', $item['route'])) ? 'bg-zinc-950 text-white' : 'text-zinc-700 hover:bg-zinc-100' }}">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
        </aside>

        <div data-content class="transition-[padding] duration-200 lg:pl-64">
            <header class="sticky top-0 z-10 border-b border-zinc-200 bg-white/95 px-4 py-4 backdrop-blur sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <button type="button" data-sidebar-toggle aria-label="Ocultar menu" aria-expanded="true" class="hidden h-10 min-w-10 items-center justify-center rounded-md border border-zinc-300 px-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 lg:inline-flex">
                            <span data-sidebar-toggle-icon>Menu</span>
                        </button>
                        <div>
                            <p class="text-sm text-zinc-500">{{ auth()->user()->name }} · {{ ucfirst(auth()->user()->role) }}</p>
                            <h1 class="text-2xl font-semibold">{{ $heading ?? 'Dashboard' }}</h1>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('dashboard') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium lg:hidden">Inicio</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="rounded-md bg-zinc-950 px-3 py-2 text-sm font-medium text-white">Sair</button>
                        </form>
                    </div>
                </div>
                <nav class="mt-4 flex gap-2 overflow-x-auto lg:hidden">
                    <a href="{{ route('customers.index') }}" class="rounded-md border border-zinc-200 px-3 py-2 text-sm">Clientes</a>
                    <a href="{{ route('vehicles.index') }}" class="rounded-md border border-zinc-200 px-3 py-2 text-sm">Veiculos</a>
                    <a href="{{ route('services.index') }}" class="rounded-md border border-zinc-200 px-3 py-2 text-sm">Servicos</a>
                    <a href="{{ route('wash-orders.index') }}" class="rounded-md border border-zinc-200 px-3 py-2 text-sm">Lavagens</a>
                    <a href="{{ route('kanban') }}" class="rounded-md border border-zinc-200 px-3 py-2 text-sm">Kanban</a>
                    <a href="{{ route('finance.index') }}" class="rounded-md border border-zinc-200 px-3 py-2 text-sm">Financeiro</a>
                    <a href="{{ route('employees.index') }}" class="rounded-md border border-zinc-200 px-3 py-2 text-sm">Funcionarios</a>
                </nav>
            </header>

            <main class="px-4 py-6 sm:px-6 lg:px-8">
                @if (session('status'))
                    <div class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    <script>
        const shell = document.querySelector('[data-app-shell]');
        const sidebar = document.querySelector('[data-sidebar]');
        const content = document.querySelector('[data-content]');
        const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
        const sidebarToggleIcon = document.querySelector('[data-sidebar-toggle-icon]');
        const sidebarStorageKey = 'autoflow.sidebar.collapsed';

        const applySidebarState = (collapsed) => {
            sidebar.classList.toggle('-translate-x-full', collapsed);
            content.classList.toggle('lg:pl-64', !collapsed);
            content.classList.toggle('lg:pl-0', collapsed);
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
    </script>
</body>
</html>
