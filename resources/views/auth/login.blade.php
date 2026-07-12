<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar · AutoFlow</title>
    @include('components.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white text-zinc-950 antialiased">
    <main class="grid min-h-screen lg:grid-cols-[minmax(0,1fr)_440px]">
        <section class="flex items-center justify-center border-b border-zinc-200 bg-white p-8 sm:p-12 lg:border-b-0 lg:border-r">
            <div class="w-full max-w-2xl">
                <img src="{{ asset('images/autoflow-logo.png') }}" alt="AutoFlow" class="w-full max-w-2xl">
                <h1 class="mt-8 text-3xl font-semibold text-zinc-950 sm:text-4xl">Operacao clara da entrada ao pagamento.</h1>
                <p class="mt-4 max-w-xl text-zinc-600">Controle clientes, veículos, serviços, lavagens e acompanhamento em uma rotina simples para lava-rápidos.</p>
            </div>
        </section>

        <section class="flex items-center justify-center bg-zinc-50 p-6 text-zinc-950">
            <form method="POST" action="{{ route('login') }}" class="w-full max-w-sm space-y-5 rounded-lg border border-zinc-200 bg-white p-6">
                @csrf
                <div>
                    <h2 class="text-2xl font-semibold">Entrar</h2>
                    <p class="mt-1 text-sm text-zinc-500">Use admin@lavaabs.test / password após rodar o seed.</p>
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
