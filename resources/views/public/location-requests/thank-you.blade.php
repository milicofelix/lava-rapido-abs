<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Solicitação enviada · AutoFlow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-950 text-slate-950 antialiased">
    <div class="grid min-h-screen place-items-center bg-[radial-gradient(circle_at_top_left,#0ea5e9_0,transparent_32%),linear-gradient(135deg,#020617,#0f172a_55%,#082f49)] px-4 py-8">
        <main class="w-full max-w-xl rounded-3xl border border-white/10 bg-white p-8 text-center shadow-2xl shadow-black/30">
            <img src="{{ asset('images/autoflow-logo.png') }}" alt="AutoFlow" class="mx-auto h-14 w-auto">
            <div class="mx-auto mt-7 grid h-16 w-16 place-items-center rounded-full bg-green-100 text-3xl">✓</div>
            <p class="mt-6 text-xs font-black uppercase tracking-[0.24em] text-blue-600">Solicitação recebida</p>
            <h1 class="mt-3 text-3xl font-black text-slate-950">Cadastro enviado para análise</h1>
            <p class="mt-4 text-sm leading-6 text-slate-600">Recebemos os dados do seu lava-rápido. Agora a solicitação ficará como <strong>pendente de análise</strong>. Em breve entraremos em contato para validar as informações e liberar o trial.</p>

            @if (session('status'))
                <p class="mt-5 rounded-2xl bg-green-50 px-4 py-3 text-sm font-bold text-green-700 ring-1 ring-green-100">{{ session('status') }}</p>
            @endif

            <div class="mt-7 flex flex-col gap-3 sm:flex-row sm:justify-center">
                <a href="{{ route('public.locations.index') }}" class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-black text-slate-700 hover:bg-slate-50">Voltar ao mapa</a>
                <a href="{{ route('login') }}" class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white">Entrar</a>
            </div>
        </main>
    </div>
</body>
</html>
