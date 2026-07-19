<x-app.layout heading="Assinatura pendente" title="Assinatura pendente · AutoFlow">
    <section class="mx-auto max-w-3xl rounded-2xl border border-amber-200 bg-amber-50 p-6 shadow-sm">
        <p class="text-xs font-black uppercase tracking-[0.22em] text-amber-700">Acesso operacional bloqueado</p>
        <h2 class="mt-3 text-2xl font-black text-slate-950">O período gratuito desta unidade expirou.</h2>
        <p class="mt-3 text-sm leading-6 text-amber-950">
            Para proteger o ciclo comercial do AutoFlow, a operação fica bloqueada quando o período gratuito termina e ainda não existe assinatura ativa.
        </p>

        @if ($currentLocation)
            <div class="mt-5 rounded-2xl border border-amber-200 bg-white p-5 text-sm text-slate-700">
                <p><strong>Unidade:</strong> {{ $currentLocation->name }}</p>
                <p class="mt-1"><strong>Status:</strong> {{ $currentLocation->accountStatusLabel() }}</p>
                @if ($currentLocation->trial_ends_at)
                    <p class="mt-1"><strong>Período gratuito terminou em:</strong> {{ $currentLocation->trial_ends_at->format('d/m/Y') }}</p>
                @endif
            </div>
        @endif

        <p class="mt-5 text-sm text-slate-600">
            Fale com o dono do produto para ativar a assinatura da unidade e liberar novamente o painel, lavagens, kanban, clientes, equipe e financeiro.
        </p>

        @if (auth()->user()->isOwner())
            <div class="mt-5">
                <a href="{{ route('subscriptions.show') }}" class="inline-flex rounded-xl bg-blue-700 px-5 py-3 text-sm font-black text-white hover:bg-blue-800">Escolher plano</a>
            </div>
        @endif
    </section>
</x-app.layout>
