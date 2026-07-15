@include('app.components.errors')

@php
    $selectedBrand = old('brand', $vehicle->brand);
    $selectedModel = old('model', $vehicle->model);
    $selectedType = old('type', $vehicle->type);
@endphp

<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" data-tour="vehicle-form-card">
    <div class="border-b border-slate-200 pb-4">
        <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Dados do veiculo</p>
        <h2 class="mt-1 text-xl font-black text-slate-950">{{ $vehicle->exists ? 'Editar veiculo' : 'Novo veiculo' }}</h2>
        <p class="mt-1 text-sm text-slate-500">Selecione a marca para carregar apenas os modelos oficiais vinculados.</p>
    </div>

    <div class="mt-5 grid gap-4 md:grid-cols-2" data-vehicle-catalog-form data-vehicle-models='@json($vehicleModelsByBrand)' data-type-labels='@json($types)'>
        <label class="block md:col-span-2" data-tour="vehicle-form-customer">
            <span class="text-sm font-bold text-slate-700">Cliente</span>
            <select name="customer_id" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                <option value="">Selecione</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}" @selected(old('customer_id', $vehicle->customer_id) == $customer->id)>{{ $customer->name }}</option>
                @endforeach
            </select>
            @error('customer_id') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
        <label class="block" data-tour="vehicle-form-plate">
            <span class="text-sm font-bold text-slate-700">Placa</span>
            <input name="plate" value="{{ old('plate', $vehicle->plate) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm uppercase tracking-wide shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            @error('plate') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
        <label class="block" data-tour="vehicle-form-color">
            <span class="text-sm font-bold text-slate-700">Cor</span>
            <input name="color" value="{{ old('color', $vehicle->color) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            @error('color') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
        <label class="block" data-tour="vehicle-form-brand">
            <span class="text-sm font-bold text-slate-700">Marca</span>
            <select name="brand" required data-vehicle-brand class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                <option value="">Selecione a marca</option>
                @foreach ($vehicleBrands as $brand)
                    <option value="{{ $brand }}" @selected($selectedBrand === $brand)>{{ $brand }}</option>
                @endforeach
            </select>
            @error('brand') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
        <label class="block" data-tour="vehicle-form-model">
            <span class="text-sm font-bold text-slate-700">Modelo</span>
            <select name="model" required data-vehicle-model data-selected-model="{{ $selectedModel }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                <option value="">Selecione uma marca primeiro</option>
            </select>
            @error('model') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
        <label class="block md:col-span-2" data-tour="vehicle-form-type">
            <span class="text-sm font-bold text-slate-700">Tipo</span>
            <select name="type" required data-vehicle-type data-selected-type="{{ $selectedType }}" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm text-slate-700 shadow-sm">
                <option value="">Selecione o modelo</option>
                @foreach ($types as $value => $label)
                    <option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-slate-500">Preenchido automaticamente conforme o modelo.</p>
            @error('type') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
    </div>

    <label class="mt-4 block" data-tour="vehicle-form-notes">
        <span class="text-sm font-bold text-slate-700">Observacoes</span>
        <textarea name="notes" rows="4" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">{{ old('notes', $vehicle->notes) }}</textarea>
        @error('notes') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
    </label>
</section>

<div class="mt-5 flex flex-wrap justify-end gap-3" data-tour="vehicle-form-actions">
    <a href="{{ route('vehicles.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancelar</a>
    <button class="rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-800">Salvar veiculo</button>
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

@php
    $vehicleFormTour = [
        'key' => $vehicle->exists ? 'vehicles.edit.v1' : 'vehicles.create.v1',
        'title' => $vehicle->exists ? 'Editando veículo' : 'Cadastrando veículo',
        'steps' => [
            [
                'target' => '[data-tour="vehicle-form-card"]',
                'title' => $vehicle->exists ? 'Editar veículo' : 'Novo veículo',
                'body' => 'Este formulário vincula o veículo ao cliente e padroniza marca/modelo para reduzir erro de digitação.',
            ],
            [
                'target' => '[data-tour="vehicle-form-customer"]',
                'title' => 'Cliente responsável',
                'body' => 'Selecione o cliente dono deste veículo. Na abertura da lavagem, o sistema mostrará apenas os veículos desse cliente.',
            ],
            [
                'target' => '[data-tour="vehicle-form-plate"]',
                'title' => 'Placa',
                'body' => 'Informe a placa com atenção. Ela é uma das principais formas de localizar veículo e histórico de lavagens.',
            ],
            [
                'target' => '[data-tour="vehicle-form-brand"]',
                'title' => 'Marca oficial',
                'body' => 'Escolha a marca primeiro. Isso filtra os modelos disponíveis e evita combinações inválidas.',
            ],
            [
                'target' => '[data-tour="vehicle-form-model"]',
                'title' => 'Modelo vinculado à marca',
                'body' => 'Depois da marca, selecione o modelo correto. A lista muda conforme a marca escolhida.',
            ],
            [
                'target' => '[data-tour="vehicle-form-type"]',
                'title' => 'Tipo automático',
                'body' => 'O tipo é preenchido conforme o modelo selecionado, ajudando a manter relatórios e serviços coerentes.',
            ],
            [
                'target' => '[data-tour="vehicle-form-actions"]',
                'title' => 'Salvar cadastro',
                'body' => 'Revise cliente, placa, marca e modelo antes de salvar. Depois disso, o veículo já fica disponível para novas lavagens.',
            ],
        ],
    ];
@endphp
<script type="application/json" data-onboarding-tour>
    {!! json_encode($vehicleFormTour, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
