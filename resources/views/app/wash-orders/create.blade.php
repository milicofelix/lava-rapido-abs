<x-app.layout heading="Nova lavagem" title="Nova lavagem · AutoFlow">
    <form method="POST" action="{{ route('wash-orders.store') }}" class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_320px] 2xl:grid-cols-[minmax(0,1fr)_360px]">
        @csrf

        <div class="space-y-5">
            @unless ($canOpenWashOrderNow)
                <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-amber-900 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-amber-700">Unidade fechada</p>
                    <h2 class="mt-1 text-lg font-black">Abertura imediata indisponível</h2>
                    <p class="mt-1 text-sm font-semibold">
                        {{ $currentLocation?->name ?? 'A unidade' }} está fora do horário de funcionamento. Abra novas lavagens apenas quando a unidade estiver aberta
                        @if ($scheduleEnabled)
                            ou informe um horário futuro dentro do expediente.
                        @else
                            .
                        @endif
                    </p>
                    @if ($currentLocation)
                        <p class="mt-3 text-xs font-bold">Horários: {{ $currentLocation->openingHoursSummary() }}</p>
                    @endif
                </section>
            @endunless

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                @include('app.components.errors')

                <div class="border-b border-slate-200 pb-4">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Abertura da ordem</p>
                    <h2 class="mt-1 text-xl font-black text-slate-950">Dados da lavagem</h2>
                    <p class="mt-1 text-sm text-slate-500">Escolha o cliente para carregar somente os veiculos vinculados a ele.</p>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="text-sm font-bold text-slate-700">Cliente</span>
                        <select name="customer_id" required data-customer-select class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <option value="">Selecione</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>{{ $customer->name }} · {{ $customer->phone }}</option>
                            @endforeach
                        </select>
                        @error('customer_id') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-bold text-slate-700">Veículo</span>
                        <select name="vehicle_id" required data-vehicle-select data-old-vehicle="{{ old('vehicle_id') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <option value="">Selecione um cliente primeiro</option>
                        </select>
                        <p data-vehicle-help class="mt-1 text-xs text-slate-500">Ao escolher o cliente, os veiculos vinculados aparecem aqui.</p>
                        @error('vehicle_id') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>

                    @if ($scheduleEnabled)
                        <label class="block md:col-span-2">
                            <span class="text-sm font-bold text-slate-700">Agendar para</span>
                            <input name="scheduled_at" type="datetime-local" value="{{ old('scheduled_at', $suggestedScheduledAt) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <p class="mt-1 text-xs text-slate-500">Deixe em branco para abrir a lavagem agora. Informe uma data futura para aparecer na Agenda desse dia.</p>
                            @error('scheduled_at') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </label>
                    @endif
                </div>

                <label class="mt-4 block">
                    <span class="text-sm font-bold text-slate-700">Observacoes</span>
                    <textarea name="notes" rows="4" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">{{ old('notes') }}</textarea>
                    @error('notes') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                </label>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="border-b border-slate-200 pb-4">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Equipe da lavagem</p>
                    <h2 class="mt-1 text-xl font-black text-slate-950">Responsaveis pela execucao</h2>
                    <p class="mt-1 text-sm text-slate-500">Selecione todos que participam. O primeiro selecionado será o responsável principal.</p>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($users as $user)
                        <label class="flex min-h-20 cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4 hover:bg-slate-50">
                            <input name="assigned_user_ids[]" type="checkbox" value="{{ $user->id }}" @checked(in_array($user->id, old('assigned_user_ids', []))) class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-700">
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-black text-slate-950">{{ $user->name }}</span>
                                <span class="mt-1 block text-xs font-bold text-slate-500">{{ $user->roleLabel() }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('assigned_user_ids') <span class="mt-3 block text-sm text-red-600">{{ $message }}</span> @enderror
                @error('assigned_user_ids.*') <span class="mt-3 block text-sm text-red-600">{{ $message }}</span> @enderror
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Catálogo</p>
                    <h2 class="mt-1 text-xl font-black text-slate-950">Serviços</h2>
                </div>
                <div class="grid gap-3 p-5 md:grid-cols-2">
                    @foreach ($services as $service)
                        <label class="flex min-h-28 cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4 hover:bg-slate-50">
                            <input name="service_ids[]" type="checkbox" value="{{ $service->id }}" data-price="{{ $service->base_price }}" data-minutes="{{ $service->estimated_minutes }}" @checked(in_array($service->id, old('service_ids', []))) class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-700">
                            <span class="min-w-0">
                                <span class="block truncate font-black text-slate-950">{{ $service->name }}</span>
                                <span class="mt-1 block text-sm text-slate-500">{{ $service->category }} · {{ $service->estimated_minutes }} min</span>
                                <span class="mt-3 inline-flex rounded-full bg-blue-50 px-3 py-1 text-sm font-black text-blue-700">R$ {{ number_format((float) $service->base_price, 2, ',', '.') }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('service_ids') <p class="px-5 pb-4 text-sm text-red-600">{{ $message }}</p> @enderror
            </section>
        </div>

        <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:sticky lg:top-24">
            <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Resumo</p>
            <h2 class="mt-1 text-xl font-black text-slate-950">Ordem de lavagem</h2>
            <dl class="mt-5 space-y-3 text-sm">
                <div class="flex justify-between gap-4 rounded-2xl bg-slate-50 px-4 py-3">
                    <dt class="font-bold text-slate-500">Total</dt>
                    <dd class="font-black text-slate-950" id="wash-total">R$ 0,00</dd>
                </div>
                <div class="flex justify-between gap-4 rounded-2xl bg-slate-50 px-4 py-3">
                    <dt class="font-bold text-slate-500">Tempo estimado</dt>
                    <dd class="font-black text-slate-950" id="wash-minutes">0 min</dd>
                </div>
            </dl>
            <button @disabled(! $canOpenWashOrderNow && ! $scheduleEnabled) class="mt-5 w-full rounded-xl px-4 py-2.5 text-sm font-bold shadow-sm {{ ! $canOpenWashOrderNow && ! $scheduleEnabled ? 'cursor-not-allowed bg-slate-200 text-slate-500' : 'bg-blue-700 text-white hover:bg-blue-800' }}">Abrir lavagem</button>
            <a href="{{ route('wash-orders.index') }}" class="mt-3 block rounded-xl border border-slate-300 px-4 py-2.5 text-center text-sm font-bold text-slate-700 hover:bg-slate-50">Cancelar</a>
        </aside>
    </form>

    <script type="application/json" data-customer-vehicles>
        @json($customerVehicles)
    </script>
    <script>
        const customerSelect = document.querySelector('[data-customer-select]');
        const vehicleSelect = document.querySelector('[data-vehicle-select]');
        const vehicleHelp = document.querySelector('[data-vehicle-help]');
        const customerVehicles = JSON.parse(document.querySelector('[data-customer-vehicles]').textContent);

        const setVehicleOptions = () => {
            const customerId = customerSelect.value;
            const selectedVehicle = vehicleSelect.dataset.oldVehicle || vehicleSelect.value;
            const vehicles = customerVehicles[customerId] || [];

            vehicleSelect.innerHTML = '';

            if (!customerId) {
                vehicleSelect.append(new Option('Selecione um cliente primeiro', ''));
                vehicleHelp.textContent = 'Ao escolher o cliente, os veiculos vinculados aparecem aqui.';
                vehicleSelect.dataset.oldVehicle = '';
                return;
            }

            if (vehicles.length === 0) {
                vehicleSelect.append(new Option('Cliente sem veiculo cadastrado', ''));
                vehicleHelp.textContent = 'Cadastre um veiculo para este cliente antes de abrir a lavagem.';
                vehicleSelect.dataset.oldVehicle = '';
                return;
            }

            if (vehicles.length > 1) {
                vehicleSelect.append(new Option('Selecione o veiculo', ''));
            }

            vehicles.forEach((vehicle) => {
                vehicleSelect.append(new Option(vehicle.label, vehicle.id));
            });

            if (vehicles.length === 1) {
                vehicleSelect.value = vehicles[0].id;
                vehicleHelp.textContent = 'Veículo único do cliente selecionado automaticamente.';
            } else if (vehicles.some((vehicle) => String(vehicle.id) === String(selectedVehicle))) {
                vehicleSelect.value = selectedVehicle;
                vehicleHelp.textContent = 'Escolha um dos veiculos vinculados a este cliente.';
            } else {
                vehicleSelect.value = '';
                vehicleHelp.textContent = 'Escolha um dos veiculos vinculados a este cliente.';
            }

            vehicleSelect.dataset.oldVehicle = '';
        };

        customerSelect.addEventListener('change', setVehicleOptions);
        setVehicleOptions();

        const formatter = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
        const updateSummary = () => {
            const checked = [...document.querySelectorAll('input[name="service_ids[]"]:checked')];
            const total = checked.reduce((sum, input) => sum + Number(input.dataset.price || 0), 0);
            const minutes = checked.reduce((sum, input) => sum + Number(input.dataset.minutes || 0), 0);
            document.getElementById('wash-total').textContent = formatter.format(total);
            document.getElementById('wash-minutes').textContent = `${minutes} min`;
        };

        document.querySelectorAll('input[name="service_ids[]"]').forEach((input) => input.addEventListener('change', updateSummary));
        updateSummary();
    </script>
</x-app.layout>
