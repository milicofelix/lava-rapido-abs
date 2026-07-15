@include('app.components.errors')

<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" data-tour="customer-form-card">
    <div class="border-b border-slate-200 pb-4">
        <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Dados do cliente</p>
        <h2 class="mt-1 text-xl font-black text-slate-950">{{ $customer->exists ? 'Editar cadastro' : 'Novo cadastro' }}</h2>
    </div>

    <div class="mt-5 grid gap-4 md:grid-cols-2">
        <label class="block" data-tour="customer-form-name">
            <span class="text-sm font-bold text-slate-700">Nome</span>
            <input name="name" value="{{ old('name', $customer->name) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            @error('name') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
        <label class="block" data-tour="customer-form-phone">
            <span class="text-sm font-bold text-slate-700">Telefone / WhatsApp</span>
            <input name="phone" value="{{ old('phone', $customer->phone) }}" required data-mask="phone" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            @error('phone') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
        <label class="block md:col-span-2" data-tour="customer-form-email">
            <span class="text-sm font-bold text-slate-700">E-mail</span>
            <input name="email" type="email" value="{{ old('email', $customer->email) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            @error('email') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
    </div>

    <label class="mt-4 block" data-tour="customer-form-notes">
        <span class="text-sm font-bold text-slate-700">Observacoes</span>
        <textarea name="notes" rows="4" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">{{ old('notes', $customer->notes) }}</textarea>
        @error('notes') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
    </label>
</section>

<div class="mt-5 flex flex-wrap justify-end gap-3" data-tour="customer-form-actions">
    <a href="{{ route('customers.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancelar</a>
    <button class="rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-800">Salvar cliente</button>
</div>

@php
    $customerFormTour = [
        'key' => $customer->exists ? 'customers.edit.v1' : 'customers.create.v1',
        'title' => $customer->exists ? 'Editando cliente' : 'Cadastrando cliente',
        'steps' => [
            [
                'target' => '[data-tour="customer-form-card"]',
                'title' => $customer->exists ? 'Editar cliente' : 'Novo cliente',
                'body' => 'Este cadastro guarda os dados essenciais para contato, abertura de lavagem e histórico do cliente.',
            ],
            [
                'target' => '[data-tour="customer-form-name"]',
                'title' => 'Nome do cliente',
                'body' => 'Informe o nome usado pela equipe para localizar o cliente rapidamente no balcão ou na busca.',
            ],
            [
                'target' => '[data-tour="customer-form-phone"]',
                'title' => 'Telefone e WhatsApp',
                'body' => 'O telefone é obrigatório e também é usado para montar links de compartilhamento pelo WhatsApp.',
            ],
            [
                'target' => '[data-tour="customer-form-email"]',
                'title' => 'E-mail opcional',
                'body' => 'Preencha o e-mail apenas quando o cliente informar. O fluxo principal funciona bem somente com nome e telefone.',
            ],
            [
                'target' => '[data-tour="customer-form-notes"]',
                'title' => 'Observações internas',
                'body' => 'Use este campo para preferências de atendimento, cuidados com o veículo ou informações úteis para a equipe.',
            ],
            [
                'target' => '[data-tour="customer-form-actions"]',
                'title' => 'Salvar cadastro',
                'body' => 'Revise os dados e salve. Depois disso, o cliente fica disponível para vincular veículos e abrir lavagens.',
            ],
        ],
    ];
@endphp
<script type="application/json" data-onboarding-tour>
    {!! json_encode($customerFormTour, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
