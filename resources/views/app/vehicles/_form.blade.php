@include('app.components.errors')

@php($selectedBrand = old('brand', $vehicle->brand))
@php($selectedModel = old('model', $vehicle->model))
@php($selectedType = old('type', $vehicle->type))

<div class="grid gap-4 md:grid-cols-2" data-vehicle-catalog-form data-vehicle-models='@json($vehicleModelsByBrand)' data-type-labels='@json($types)'>
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
        <span class="text-sm font-medium">Marca</span>
        <select name="brand" required data-vehicle-brand class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2">
            <option value="">Selecione a marca</option>
            @foreach ($vehicleBrands as $brand)
                <option value="{{ $brand }}" @selected($selectedBrand === $brand)>{{ $brand }}</option>
            @endforeach
        </select>
        @error('brand') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
    </label>
    <label class="block">
        <span class="text-sm font-medium">Modelo</span>
        <select name="model" required data-vehicle-model data-selected-model="{{ $selectedModel }}" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2">
            <option value="">Selecione uma marca primeiro</option>
        </select>
        @error('model') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
    </label>
    <label class="block">
        <span class="text-sm font-medium">Cor</span>
        <input name="color" value="{{ old('color', $vehicle->color) }}" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2">
        @error('color') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
    </label>
    <label class="block">
        <span class="text-sm font-medium">Tipo</span>
        <select name="type" required data-vehicle-type data-selected-type="{{ $selectedType }}" class="mt-1 w-full rounded-md border border-zinc-300 bg-slate-50 px-3 py-2 text-slate-700">
            <option value="">Selecione o modelo</option>
            @foreach ($types as $value => $label)
                <option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-zinc-500">Preenchido automaticamente conforme o modelo.</p>
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

<script>
    document.querySelectorAll('[data-vehicle-catalog-form]').forEach((form) => {
        const modelsByBrand = JSON.parse(form.dataset.vehicleModels || '{}');
        const typeLabels = JSON.parse(form.dataset.typeLabels || '{}');
        const brandSelect = form.querySelector('[data-vehicle-brand]');
        const modelSelect = form.querySelector('[data-vehicle-model]');
        const typeSelect = form.querySelector('[data-vehicle-type]');

        const setType = (type) => {
            typeSelect.value = type || '';
            typeSelect.title = type ? (typeLabels[type] || type) : '';
        };

        const fillModels = () => {
            const models = modelsByBrand[brandSelect.value] || [];
            const selectedModel = modelSelect.dataset.selectedModel || '';

            modelSelect.innerHTML = '';
            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = brandSelect.value ? 'Selecione o modelo' : 'Selecione uma marca primeiro';
            modelSelect.appendChild(placeholder);

            models.forEach((vehicle) => {
                const option = document.createElement('option');
                option.value = vehicle.model;
                option.textContent = vehicle.model;
                option.dataset.type = vehicle.type;
                option.selected = vehicle.model === selectedModel;
                modelSelect.appendChild(option);
            });

            const selectedOption = modelSelect.selectedOptions[0];
            setType(selectedOption?.dataset.type || typeSelect.dataset.selectedType || '');
        };

        brandSelect.addEventListener('change', () => {
            modelSelect.dataset.selectedModel = '';
            typeSelect.dataset.selectedType = '';
            fillModels();
        });

        modelSelect.addEventListener('change', () => {
            typeSelect.dataset.selectedType = '';
            setType(modelSelect.selectedOptions[0]?.dataset.type || '');
        });

        fillModels();
    });
</script>
