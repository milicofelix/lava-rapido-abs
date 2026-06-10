@include('app.components.errors')

<div class="grid gap-4 md:grid-cols-2">
    <label class="block">
        <span class="text-sm font-medium">Nome</span>
        <input name="name" value="{{ old('name', $customer->name) }}" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2">
        @error('name') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
    </label>
    <label class="block">
        <span class="text-sm font-medium">Telefone / WhatsApp</span>
        <input name="phone" value="{{ old('phone', $customer->phone) }}" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2">
        @error('phone') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
    </label>
    <label class="block">
        <span class="text-sm font-medium">E-mail</span>
        <input name="email" type="email" value="{{ old('email', $customer->email) }}" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2">
        @error('email') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
    </label>
    <label class="block">
        <span class="text-sm font-medium">CPF</span>
        <input name="cpf" value="{{ old('cpf', $customer->cpf) }}" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2">
        @error('cpf') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
    </label>
</div>

<label class="mt-4 block">
    <span class="text-sm font-medium">Observacoes</span>
    <textarea name="notes" rows="4" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2">{{ old('notes', $customer->notes) }}</textarea>
    @error('notes') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
</label>

<div class="mt-6 flex gap-3">
    <button class="rounded-md bg-cyan-700 px-4 py-2 text-sm font-semibold text-white">Salvar cliente</button>
    <a href="{{ route('customers.index') }}" class="rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold">Cancelar</a>
</div>
