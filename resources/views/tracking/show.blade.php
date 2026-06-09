<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="30">
    <title>Acompanhamento {{ $washOrder->code }} · Lava Rapido ABS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-zinc-950 text-white antialiased">
    <main class="mx-auto min-h-screen max-w-5xl px-4 py-6 sm:px-6 lg:px-8">
        <header class="flex flex-wrap items-start justify-between gap-4 border-b border-white/10 pb-6">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-cyan-200">Lava Rapido ABS</p>
                <h1 class="mt-2 text-3xl font-bold sm:text-4xl">{{ $washOrder->vehicle->model }} {{ $washOrder->vehicle->color }} - {{ $washOrder->vehicle->plate }}</h1>
                <p class="mt-2 text-sm text-zinc-400">Codigo {{ $washOrder->code }}</p>
            </div>
            <div class="rounded-lg border border-white/10 bg-white/10 px-4 py-3 text-right">
                <p class="text-sm text-zinc-300">Status atual</p>
                <p class="mt-1 text-xl font-semibold text-cyan-100">{{ $washOrder->statusLabel() }}</p>
            </div>
        </header>

        <section class="grid gap-4 py-6 md:grid-cols-3">
            <div class="rounded-lg border border-white/10 bg-white/10 p-5">
                <p class="text-sm text-zinc-300">Previsao</p>
                <p class="mt-2 text-2xl font-semibold">{{ $washOrder->estimated_completion_at?->format('H:i') ?? '-' }}</p>
            </div>
            <div class="rounded-lg border border-white/10 bg-white/10 p-5">
                <p class="text-sm text-zinc-300">Entrada</p>
                <p class="mt-2 text-2xl font-semibold">{{ $washOrder->entered_at->format('H:i') }}</p>
            </div>
            <div class="rounded-lg border border-white/10 bg-white/10 p-5">
                <p class="text-sm text-zinc-300">Servicos</p>
                <p class="mt-2 text-2xl font-semibold">{{ $washOrder->services->count() }}</p>
            </div>
        </section>

        <section class="rounded-lg border border-white/10 bg-white p-5 text-zinc-950">
            <h2 class="text-lg font-semibold">Andamento</h2>
            <div class="mt-5 grid gap-3 md:grid-cols-7">
                @foreach ($progressStatuses as $progressStatus)
                    @php
                        $currentIndex = array_search($washOrder->status, $progressStatuses, true);
                        $stepIndex = array_search($progressStatus, $progressStatuses, true);
                        $isDone = $currentIndex !== false && $stepIndex <= $currentIndex;
                        $isCurrent = $washOrder->status === $progressStatus;
                    @endphp
                    <div class="rounded-md border px-3 py-3 {{ $isCurrent ? 'border-cyan-700 bg-cyan-50' : ($isDone ? 'border-emerald-200 bg-emerald-50' : 'border-zinc-200 bg-zinc-50') }}">
                        <div class="h-2 w-full rounded-full {{ $isDone ? 'bg-cyan-700' : 'bg-zinc-200' }}"></div>
                        <p class="mt-3 text-xs font-semibold">{{ $statuses[$progressStatus] }}</p>
                    </div>
                @endforeach
            </div>

            @if ($washOrder->status === \App\Models\WashOrder::STATUS_CANCELED)
                <div class="mt-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">Lavagem cancelada.</div>
            @elseif ($washOrder->status === \App\Models\WashOrder::STATUS_READY)
                <div class="mt-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">Veiculo pronto para retirada.</div>
            @elseif ($washOrder->status === \App\Models\WashOrder::STATUS_DELIVERED)
                <div class="mt-5 rounded-md border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-700">Veiculo entregue.</div>
            @endif
        </section>

        <div class="mt-5 grid gap-5 lg:grid-cols-[1fr_380px]">
            <section class="rounded-lg border border-white/10 bg-white p-5 text-zinc-950">
                <h2 class="text-lg font-semibold">Servicos</h2>
                <div class="mt-4 divide-y divide-zinc-100">
                    @foreach ($washOrder->services as $service)
                        <div class="flex items-center justify-between gap-4 py-3">
                            <p class="font-medium">{{ $service->pivot->service_name }}</p>
                            <p class="text-sm text-zinc-500">{{ $service->pivot->estimated_minutes }} min</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="rounded-lg border border-white/10 bg-white p-5 text-zinc-950">
                <h2 class="text-lg font-semibold">Historico</h2>
                <div class="mt-4 space-y-4">
                    @foreach ($washOrder->statusHistories->sortByDesc('created_at') as $history)
                        <div class="border-l-2 border-cyan-700 pl-3">
                            <p class="font-medium">{{ $statuses[$history->to_status] ?? $history->to_status }}</p>
                            <p class="text-sm text-zinc-500">{{ $history->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </main>
</body>
</html>
