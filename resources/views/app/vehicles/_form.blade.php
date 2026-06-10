@include('app.components.errors')

<div class="grid gap-4 md:grid-cols-2">
    <label class="block">
        <span class="text-sm font-medium">Cliente</span>
        <select name="customer_id" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2">
            <option value="">Selecione</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}" @selected(old('customer_id', $vehicle->customer_id) == $customer->id)>{{ $customer->name }}</option>
            @endforeach
        </select>
        @error('customer_id') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
    </label>
    <label class="block">
        <span class="text-sm font-medium">Placa</span>
        <input name="plate" value="{{ old('plate', $vehicle->plate) }}" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 uppercase">
        @error('plate') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
    </label>
    <label class="block">
        <span class="text-sm font-medium">Modelo</span>
        <input name="model" value="{{ old('model', $vehicle->model) }}" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2">
        @error('model') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
    </label>
    <label class="block">
        <span class="text-sm font-medium">Marca</span>
        <input name="brand" value="{{ old('brand', $vehicle->brand) }}" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2">
        @error('brand') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
    </label>
    <label class="block">
        <span class="text-sm font-medium">Cor</span>
        <input name="color" value="{{ old('color', $vehicle->color) }}" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2">
        @error('color') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
    </label>
    <label class="block">
        <span class="text-sm font-medium">Tipo</span>
        <select name="type" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2">
            <option value="">Selecione</option>
            @foreach ($types as $value => $label)
                <option value="{{ $value }}" @selected(old('type', $vehicle->type) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('type') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
    </label>
</div>

<label class="mt-4 block">
    <span class="text-sm font-medium">Observacoes</span>
    <textarea name="notes" rows="4" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2">{{ old('notes', $vehicle->notes) }}</textarea>
    @error('notes') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
</label>

<div class="mt-6 flex gap-3">
    <button class="rounded-md bg-cyan-700 px-4 py-2 text-sm font-semibold text-white">Salvar veiculo</button>
    <a href="{{ route('vehicles.index') }}" class="rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold">Cancelar</a>
</div>
