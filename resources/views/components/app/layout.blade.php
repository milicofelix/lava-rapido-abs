<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Lava Rapido ABS' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-zinc-50 text-zinc-950 antialiased">
    <div class="min-h-screen">
        <aside class="fixed inset-y-0 left-0 hidden w-64 border-r border-zinc-200 bg-white px-5 py-6 lg:block">
            <a href="{{ route('dashboard') }}" class="block">
                <span class="text-xs font-semibold uppercase tracking-wide text-cyan-700">Lava Rapido</span>
                <span class="mt-1 block text-2xl font-bold">ABS</span>
            </a>

            <nav class="mt-8 space-y-1">
                @foreach ([
                    ['route' => 'dashboard', 'label' => 'Dashboard'],
                    ['route' => 'customers.index', 'label' => 'Clientes'],
                    ['route' => 'vehicles.index', 'label' => 'Veiculos'],
                    ['route' => 'services.index', 'label' => 'Servicos'],
                ] as $item)
                    <a href="{{ route($item['route']) }}" class="block rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs($item['route']) || request()->routeIs(str_replace('.index', '.*', $item['route'])) ? 'bg-zinc-950 text-white' : 'text-zinc-700 hover:bg-zinc-100' }}">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
        </aside>

        <div class="lg:pl-64">
            <header class="sticky top-0 z-10 border-b border-zinc-200 bg-white/95 px-4 py-4 backdrop-blur sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm text-zinc-500">{{ auth()->user()->name }} · {{ ucfirst(auth()->user()->role) }}</p>
                        <h1 class="text-2xl font-semibold">{{ $heading ?? 'Dashboard' }}</h1>
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
</body>
</html>
