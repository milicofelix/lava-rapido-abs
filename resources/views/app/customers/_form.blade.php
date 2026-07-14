@include('app.components.errors')

<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="border-b border-slate-200 pb-4">
        <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Dados do cliente</p>
        <h2 class="mt-1 text-xl font-black text-slate-950">{{ $customer->exists ? 'Editar cadastro' : 'Novo cadastro' }}</h2>
    </div>

    <div class="mt-5 grid gap-4 md:grid-cols-2">
        <label class="block">
            <span class="text-sm font-bold text-slate-700">Nome</span>
            <input name="name" value="{{ old('name', $customer->name) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            @error('name') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
        <label class="block">
            <span class="text-sm font-bold text-slate-700">Telefone / WhatsApp</span>
            <input name="phone" value="{{ old('phone', $customer->phone) }}" required data-mask="phone" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            @error('phone') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
        <label class="block">
            <span class="text-sm font-bold text-slate-700">E-mail</span>
            <input name="email" type="email" value="{{ old('email', $customer->email) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            @error('email') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
        <label class="block">
            <span class="text-sm font-bold text-slate-700">CPF</span>
            <input name="cpf" value="{{ old('cpf', $customer->cpf) }}" data-mask="cpf" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            @error('cpf') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
    </div>

    <label class="mt-4 block">
        <span class="text-sm font-bold text-slate-700">Observacoes</span>
        <textarea name="notes" rows="4" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">{{ old('notes', $customer->notes) }}</textarea>
        @error('notes') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
    </label>
</section>

<div class="mt-5 flex flex-wrap justify-end gap-3">
    <a href="{{ route('customers.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancelar</a>
    <button class="rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-800">Salvar cliente</button>
</div>
