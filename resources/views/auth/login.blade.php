<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar · Lava Rapido ABS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-950 text-white antialiased">
    <main class="grid min-h-screen lg:grid-cols-[1fr_440px]">
        <section class="flex items-end bg-[radial-gradient(circle_at_30%_20%,#0e7490_0,#18181b_38%,#09090b_100%)] p-8 sm:p-12">
            <div class="max-w-2xl">
                <p class="text-sm font-semibold uppercase tracking-wide text-cyan-200">Lava Rapido ABS</p>
                <h1 class="mt-3 text-4xl font-bold sm:text-5xl">Operacao clara da entrada ao pagamento.</h1>
                <p class="mt-4 max-w-xl text-zinc-300">Controle clientes, veiculos e servicos em uma base pronta para evoluir para ordens de lavagem, kanban e acompanhamento publico.</p>
            </div>
        </section>

        <section class="flex items-center justify-center bg-white p-6 text-zinc-950">
            <form method="POST" action="{{ route('login') }}" class="w-full max-w-sm space-y-5">
                @csrf
                <div>
                    <h2 class="text-2xl font-semibold">Entrar</h2>
                    <p class="mt-1 text-sm text-zinc-500">Use admin@lavaabs.test / password apos rodar o seed.</p>
                </div>

                <label class="block">
                    <span class="text-sm font-medium">E-mail</span>
                    <input name="email" type="email" value="{{ old('email') }}" required autofocus class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 outline-none focus:border-cyan-600">
                    @error('email') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium">Senha</span>
                    <input name="password" type="password" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 outline-none focus:border-cyan-600">
                    @error('password') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                </label>

                <label class="flex items-center gap-2 text-sm text-zinc-600">
                    <input name="remember" type="checkbox" value="1" class="rounded border-zinc-300">
                    Manter conectado
                </label>

                <button class="w-full rounded-md bg-zinc-950 px-4 py-2.5 text-sm font-semibold text-white">Acessar painel</button>
            </form>
        </section>
    </main>
</body>
</html>
