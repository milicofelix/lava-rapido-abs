@include('app.components.errors')

<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="border-b border-slate-200 pb-4">
        <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Dados do servico</p>
        <h2 class="mt-1 text-xl font-black text-slate-950">{{ $service->exists ? 'Editar servico' : 'Novo servico' }}</h2>
        <p class="mt-1 text-sm text-slate-500">Defina nome, categoria, preco base e tempo usado no fluxo operacional.</p>
    </div>

    <div class="mt-5 grid gap-4 md:grid-cols-2">
        <label class="block">
            <span class="text-sm font-bold text-slate-700">Nome</span>
            <input name="name" value="{{ old('name', $service->name) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            @error('name') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
        <label class="block">
            <span class="text-sm font-bold text-slate-700">Categoria</span>
            <input name="category" value="{{ old('category', $service->category) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            @error('category') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
        <label class="block">
            <span class="text-sm font-bold text-slate-700">Preco base</span>
            <input name="base_price" type="number" min="0" step="0.01" value="{{ old('base_price', $service->base_price) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            @error('base_price') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
        <label class="block">
            <span class="text-sm font-bold text-slate-700">Tempo estimado (min)</span>
            <input name="estimated_minutes" type="number" min="1" value="{{ old('estimated_minutes', $service->estimated_minutes) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            @error('estimated_minutes') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
    </div>

    <label class="mt-4 flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4 hover:bg-slate-50">
        <input name="active" type="checkbox" value="1" @checked(old('active', $service->exists ? $service->active : true)) class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-700">
        <span>
            <span class="block font-bold text-slate-900">Servico ativo</span>
            <span class="mt-1 block text-sm text-slate-500">Servicos ativos aparecem para selecao ao abrir uma nova lavagem.</span>
        </span>
    </label>

    <label class="mt-4 block">
        <span class="text-sm font-bold text-slate-700">Descricao</span>
        <textarea name="description" rows="4" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">{{ old('description', $service->description) }}</textarea>
        @error('description') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
    </label>
</section>

<div class="mt-5 flex flex-wrap justify-end gap-3">
    <a href="{{ route('services.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancelar</a>
    <button class="rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-800">Salvar servico</button>
</div>
