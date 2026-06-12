<x-app.layout heading="Detalhes da solicitação" title="Solicitação · AutoFlow">
    @php
        $badgeClass = match ($locationRequest->status) {
            \App\Models\WashLocationRequest::STATUS_APPROVED => 'bg-emerald-100 text-emerald-700',
            \App\Models\WashLocationRequest::STATUS_REJECTED => 'bg-rose-100 text-rose-700',
            default => 'bg-amber-100 text-amber-800',
        };
    @endphp

    <div class="space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('super-admin.location-requests.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700">← Voltar para solicitações</a>
            <span class="rounded-full px-3 py-1 text-xs font-black {{ $badgeClass }}">{{ $locationRequest->statusLabel() }}</span>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 pb-5">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.22em] text-blue-600">Lava-rápido solicitado</p>
                    <h2 class="mt-2 text-3xl font-black text-slate-950">{{ $locationRequest->business_name }}</h2>
                    <p class="mt-2 text-sm text-slate-500">Solicitado em {{ $locationRequest->created_at?->format('d/m/Y H:i') }}</p>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">
                    <p class="font-bold text-slate-950">Próxima fase</p>
                    <p class="mt-1">Aprovar, rejeitar e iniciar trial de 15 dias.</p>
                </div>
            </div>

            <div class="mt-6 grid gap-5 lg:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 p-5">
                    <h3 class="font-black text-slate-950">Responsável</h3>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div><dt class="text-slate-500">Nome</dt><dd class="font-bold text-slate-950">{{ $locationRequest->responsible_name }}</dd></div>
                        <div><dt class="text-slate-500">E-mail</dt><dd class="font-bold text-slate-950">{{ $locationRequest->email }}</dd></div>
                        <div><dt class="text-slate-500">WhatsApp</dt><dd class="font-bold text-slate-950">{{ $locationRequest->phone }}</dd></div>
                    </dl>
                </div>

                <div class="rounded-2xl border border-slate-200 p-5">
                    <h3 class="font-black text-slate-950">Unidade</h3>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div><dt class="text-slate-500">Endereço</dt><dd class="font-bold text-slate-950">{{ $locationRequest->address }}</dd></div>
                        <div><dt class="text-slate-500">Bairro</dt><dd class="font-bold text-slate-950">{{ $locationRequest->district ?: 'Não informado' }}</dd></div>
                        <div><dt class="text-slate-500">Cidade/UF</dt><dd class="font-bold text-slate-950">{{ $locationRequest->city }}/{{ $locationRequest->state }}</dd></div>
                        <div><dt class="text-slate-500">CEP</dt><dd class="font-bold text-slate-950">{{ $locationRequest->zip_code ?: 'Não informado' }}</dd></div>
                        <div><dt class="text-slate-500">Funcionários</dt><dd class="font-bold text-slate-950">{{ $locationRequest->employees_count ?: 'Não informado' }}</dd></div>
                    </dl>
                </div>
            </div>

            <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <h3 class="font-black text-slate-950">Mensagem</h3>
                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $locationRequest->notes ?: 'Nenhuma observação enviada.' }}</p>
            </div>
        </section>
    </div>
</x-app.layout>
