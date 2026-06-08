<x-app.layout heading="Nova lavagem" title="Nova lavagem · Lava Rapido ABS">
    <form method="POST" action="{{ route('wash-orders.store') }}" class="grid gap-5 xl:grid-cols-[1fr_360px]">
        @csrf

        <div class="space-y-5">
            <section class="rounded-lg border border-zinc-200 bg-white p-5">
                @include('app.components.errors')

                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="text-sm font-medium">Cliente</span>
                        <select name="customer_id" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2">
                            <option value="">Selecione</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>{{ $customer->name }} · {{ $customer->phone }}</option>
                            @endforeach
                        </select>
                        @error('customer_id') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium">Veiculo</span>
                        <select name="vehicle_id" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2">
                            <option value="">Selecione</option>
                            @foreach ($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" @selected(old('vehicle_id') == $vehicle->id)>{{ $vehicle->plate }} · {{ $vehicle->brand }} {{ $vehicle->model }} · {{ $vehicle->customer->name }}</option>
                            @endforeach
                        </select>
                        @error('vehicle_id') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium">Funcionario responsavel</span>
                        <select name="assigned_user_id" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2">
                            <option value="">Sem responsavel</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" @selected(old('assigned_user_id') == $user->id)>{{ $user->name }} · {{ ucfirst($user->role) }}</option>
                            @endforeach
                        </select>
                        @error('assigned_user_id') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>
                </div>

                <label class="mt-4 block">
                    <span class="text-sm font-medium">Observacoes</span>
                    <textarea name="notes" rows="4" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2">{{ old('notes') }}</textarea>
                    @error('notes') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                </label>
            </section>

            <section class="rounded-lg border border-zinc-200 bg-white">
                <div class="border-b border-zinc-200 px-5 py-4">
                    <h2 class="font-semibold">Servicos</h2>
                </div>
                <div class="grid gap-3 p-5 md:grid-cols-2">
                    @foreach ($services as $service)
                        <label class="flex min-h-28 items-start gap-3 rounded-lg border border-zinc-200 p-4">
                            <input name="service_ids[]" type="checkbox" value="{{ $service->id }}" data-price="{{ $service->base_price }}" data-minutes="{{ $service->estimated_minutes }}" @checked(in_array($service->id, old('service_ids', []))) class="mt-1 rounded border-zinc-300">
                            <span>
                                <span class="block font-medium">{{ $service->name }}</span>
                                <span class="mt-1 block text-sm text-zinc-500">{{ $service->category }} · {{ $service->estimated_minutes }} min</span>
                                <span class="mt-2 block text-sm font-semibold">R$ {{ number_format((float) $service->base_price, 2, ',', '.') }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('service_ids') <p class="px-5 pb-4 text-sm text-red-600">{{ $message }}</p> @enderror
            </section>
        </div>

        <aside class="h-fit rounded-lg border border-zinc-200 bg-white p-5">
            <h2 class="font-semibold">Resumo</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-zinc-500">Total</dt>
                    <dd class="font-semibold" id="wash-total">R$ 0,00</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-zinc-500">Tempo estimado</dt>
                    <dd class="font-semibold" id="wash-minutes">0 min</dd>
                </div>
            </dl>
            <button class="mt-5 w-full rounded-md bg-cyan-700 px-4 py-2.5 text-sm font-semibold text-white">Abrir lavagem</button>
            <a href="{{ route('wash-orders.index') }}" class="mt-3 block rounded-md border border-zinc-300 px-4 py-2.5 text-center text-sm font-semibold">Cancelar</a>
        </aside>
    </form>

    <script>
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
