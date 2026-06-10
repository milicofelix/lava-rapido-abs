@include('app.components.errors')

<div class="grid gap-4 md:grid-cols-2">
    <label class="block">
        <span class="text-sm font-medium">Nome</span>
        <input name="name" value="{{ old('name', $service->name) }}" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2">
        @error('name') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
    </label>
    <label class="block">
        <span class="text-sm font-medium">Categoria</span>
        <input name="category" value="{{ old('category', $service->category) }}" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2">
        @error('category') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
    </label>
    <label class="block">
        <span class="text-sm font-medium">Preco base</span>
        <input name="base_price" type="number" min="0" step="0.01" value="{{ old('base_price', $service->base_price) }}" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2">
        @error('base_price') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
    </label>
    <label class="block">
        <span class="text-sm font-medium">Tempo estimado (min)</span>
        <input name="estimated_minutes" type="number" min="1" value="{{ old('estimated_minutes', $service->estimated_minutes) }}" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2">
        @error('estimated_minutes') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
    </label>
</div>

<label class="mt-4 flex items-center gap-2 text-sm font-medium">
    <input name="active" type="checkbox" value="1" @checked(old('active', $service->exists ? $service->active : true)) class="rounded border-zinc-300">
    Servico ativo
</label>

<label class="mt-4 block">
    <span class="text-sm font-medium">Descricao</span>
    <textarea name="description" rows="4" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2">{{ old('description', $service->description) }}</textarea>
    @error('description') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
</label>

<div class="mt-6 flex gap-3">
    <button class="rounded-md bg-cyan-700 px-4 py-2 text-sm font-semibold text-white">Salvar servico</button>
    <a href="{{ route('services.index') }}" class="rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold">Cancelar</a>
</div>
