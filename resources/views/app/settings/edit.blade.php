<x-app.layout heading="Configuracoes" title="Configuracoes · AutoFlow">
    <div class="grid gap-5 xl:grid-cols-[1fr_360px]">
        <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" data-theme-settings-form>
            @csrf
            @method('PUT')
            @include('app.components.errors')

            <section>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-700">Geral</p>
                <h2 class="mt-1 text-xl font-bold text-slate-950">Dados da unidade</h2>
                <p class="mt-1 text-sm text-slate-500">Use essas informacoes para personalizar o painel e preparar recibos/notificacoes no futuro.</p>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <label class="block md:col-span-2">
                        <span class="text-sm font-semibold text-slate-700">Logo da unidade</span>
                        <div class="mt-1 flex flex-wrap items-center gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex h-20 w-32 items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-white">
                                @if ($currentLocation?->logo_path)
                                    <img src="{{ asset('storage/'.$currentLocation->logo_path) }}" alt="{{ $currentLocation->name }}" class="max-h-full max-w-full object-contain">
                                @else
                                    <span class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Sem logo</span>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <input name="logo" type="file" accept="image/*" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-sm file:font-bold file:text-blue-700">
                                <p class="mt-1 text-xs text-slate-500">PNG, JPG ou WEBP ate 5MB.</p>
                                @error('logo') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </label>

                    <label class="block">
                        <span class="text-sm font-semibold text-slate-700">Nome exibido da unidade</span>
                        <input name="company_name" value="{{ old('company_name', $currentLocation?->name ?? $settings['company_name']) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        @error('company_name') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-semibold text-slate-700">WhatsApp da unidade</span>
                        <input name="company_whatsapp" value="{{ old('company_whatsapp', $currentLocation?->phone ?? $settings['company_whatsapp']) }}" placeholder="(11) 99999-9999" data-mask="phone" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        @error('company_whatsapp') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-semibold text-slate-700">Razao social</span>
                        <input name="legal_name" value="{{ old('legal_name', $currentLocation?->legal_name) }}" placeholder="AutoFlow Lavagens Ltda" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        @error('legal_name') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-semibold text-slate-700">CNPJ</span>
                        <input name="document" value="{{ old('document', $currentLocation?->document) }}" placeholder="00.000.000/0001-00" data-mask="document" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        @error('document') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-semibold text-slate-700">Logradouro</span>
                        <input name="address" value="{{ old('address', $currentLocation?->address) }}" placeholder="Rua, avenida ou estrada" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        @error('address') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-semibold text-slate-700">Número</span>
                        <input name="address_number" value="{{ old('address_number', $currentLocation?->address_number) }}" placeholder="123" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        @error('address_number') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-semibold text-slate-700">Bairro</span>
                        <input name="district" value="{{ old('district', $currentLocation?->district) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        @error('district') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <div class="grid gap-4 sm:grid-cols-[1fr_90px]">
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Cidade</span>
                            <input name="city" value="{{ old('city', $currentLocation?->city) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            @error('city') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">UF</span>
                            <input name="state" value="{{ old('state', $currentLocation?->state) }}" maxlength="2" placeholder="SP" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm uppercase shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            @error('state') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </label>
                    </div>

                    <div class="md:col-span-2 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-black text-amber-900">Localização exata no mapa</p>
                                <p class="mt-1 text-xs leading-5 text-amber-800">Preencha latitude e longitude reais para que a unidade apareça no ponto correto do mapa público.</p>
                            </div>
                            @if ($currentLocation?->fullAddress())
                                <a href="https://www.google.com/maps/search/?api=1&query={{ rawurlencode($currentLocation->fullAddress()) }}" target="_blank" rel="noopener" class="rounded-lg border border-amber-300 bg-white px-3 py-2 text-xs font-black text-amber-800 hover:bg-amber-100">Abrir no Maps</a>
                            @endif
                        </div>
                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <label class="block">
                                <span class="text-sm font-semibold text-slate-700">Latitude</span>
                                <input type="hidden" name="latitude" value="{{ old('latitude', $currentLocation?->mapLatitude()) }}" data-coordinate-payload="latitude">
                                <input value="{{ old('latitude', $currentLocation?->mapLatitude()) }}" inputmode="decimal" placeholder="-23.5489100" data-coordinate-display="latitude" class="mt-1 w-full rounded-xl border border-amber-200 px-3 py-2.5 text-sm shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-100 disabled:bg-white disabled:text-slate-900">
                                @error('latitude') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                            </label>

                            <label class="block">
                                <span class="text-sm font-semibold text-slate-700">Longitude</span>
                                <input type="hidden" name="longitude" value="{{ old('longitude', $currentLocation?->mapLongitude()) }}" data-coordinate-payload="longitude">
                                <input value="{{ old('longitude', $currentLocation?->mapLongitude()) }}" inputmode="decimal" placeholder="-46.6341200" data-coordinate-display="longitude" class="mt-1 w-full rounded-xl border border-amber-200 px-3 py-2.5 text-sm shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-100 disabled:bg-white disabled:text-slate-900">
                                @error('longitude') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                            </label>
                        </div>
                    </div>

                    <label class="block md:col-span-2">
                        <span class="text-sm font-semibold text-slate-700">Horario de funcionamento</span>
                        <textarea name="opening_hours" rows="4" placeholder="Seg a sex: 08:00 as 18:00&#10;Sab: 08:00 as 14:00" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">{{ old('opening_hours', $currentLocation?->opening_hours) }}</textarea>
                        @error('opening_hours') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>
                </div>
            </section>

            <section class="border-t border-slate-200 pt-5">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-700">Aparencia</p>
                <h2 class="mt-1 text-xl font-bold text-slate-950">Tema do painel</h2>
                <div class="mt-4 grid gap-3 md:grid-cols-3">
                    @foreach ($themes as $value => $label)
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 p-4 text-sm font-semibold hover:bg-slate-50" data-theme-choice="{{ $value }}">
                            <input type="radio" name="theme" value="{{ $value }}" @checked(old('theme', $settings['theme']) === $value) class="h-4 w-4 border-slate-300 text-blue-700" data-theme-radio>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>

                <div class="mt-4 grid gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-[1fr_220px]">
                    <div>
                        <p class="text-sm font-bold text-slate-900">Pre-visualizacao rapida</p>
                        <p class="mt-1 text-sm text-slate-500">Ao salvar, o tema passa a valer para todo o painel. A opcao Sistema acompanha a preferencia do navegador/dispositivo.</p>
                    </div>
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 bg-slate-100 px-3 py-2 text-xs font-bold text-slate-600">AutoFlow</div>
                        <div class="space-y-2 p-3">
                            <div class="h-3 w-24 rounded-full bg-blue-600"></div>
                            <div class="h-2 w-full rounded-full bg-slate-200"></div>
                            <div class="h-2 w-2/3 rounded-full bg-slate-200"></div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="border-t border-slate-200 pt-5">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-700">Modulos opcionais</p>
                <h2 class="mt-1 text-xl font-bold text-slate-950">Controle o que aparece no sistema</h2>
                <p class="mt-1 text-sm text-slate-500">Ideal para lava-rapidos menores que nao precisam operar caixa completo ou fiado.</p>

                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4 hover:bg-slate-50">
                        <input type="checkbox" name="module_cash_register" value="1" @checked(old('module_cash_register', $settings['module_cash_register'])) class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-700">
                        <span>
                            <span class="block font-bold text-slate-900">Habilitar Caixa</span>
                            <span class="mt-1 block text-sm text-slate-500">Mostra abertura, sangria, suprimento e fechamento de caixa.</span>
                        </span>
                    </label>

                    <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4 hover:bg-slate-50">
                        <input type="checkbox" name="module_credit_receivables" value="1" @checked(old('module_credit_receivables', $settings['module_credit_receivables'])) class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-700">
                        <span>
                            <span class="block font-bold text-slate-900">Habilitar Fiado</span>
                            <span class="mt-1 block text-sm text-slate-500">Mostra contas a receber e baixa de pagamentos pendentes.</span>
                        </span>
                    </label>
                </div>
            </section>

            <div class="flex justify-end border-t border-slate-200 pt-5">
                <button class="rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-800">Salvar configuracoes</button>
            </div>
        </form>

        <aside class="space-y-4">
            <div class="rounded-2xl border border-blue-100 bg-blue-50 p-5 text-sm text-blue-950">
                <p class="font-bold">Sugestao de operacao</p>
                <p class="mt-2 leading-6">Comece com Caixa e Fiado desabilitados para clientes pequenos. Habilite apenas quando o lava-rapido tiver rotina de operador, conferencias ou contas a receber.</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 text-sm shadow-sm">
                <p class="font-bold text-slate-950">Quando usar tema dark?</p>
                <p class="mt-2 leading-6 text-slate-500">E uma boa opcao para recepcao com pouca luz, TVs de acompanhamento e uso noturno. Para escritorio claro, mantenha o padrao claro ou sistema.</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 text-sm shadow-sm">
                <p class="font-bold text-slate-950">Perfil da unidade</p>
                <dl class="mt-3 space-y-2 text-slate-600">
                    <div class="flex justify-between gap-4"><dt>Nome</dt><dd class="max-w-44 truncate font-semibold text-slate-900">{{ $currentLocation?->name ?? $settings['company_name'] }}</dd></div>
                    <div class="flex justify-between gap-4"><dt>CNPJ</dt><dd class="font-semibold text-slate-900">{{ $currentLocation?->document ?: '-' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt>Endereço</dt><dd class="max-w-44 truncate font-semibold text-slate-900">{{ $currentLocation?->fullAddress() ?: '-' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt>Cidade/UF</dt><dd class="font-semibold text-slate-900">{{ trim(($currentLocation?->city ?? '').'/'.($currentLocation?->state ?? ''), '/') ?: '-' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt>Mapa</dt><dd class="font-semibold {{ $currentLocation?->hasCoordinates() ? 'text-green-700' : 'text-amber-700' }}">{{ $currentLocation?->hasCoordinates() ? 'Com coordenadas' : 'Pendente' }}</dd></div>
                </dl>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 text-sm shadow-sm">
                <p class="font-bold text-slate-950">Modulos e aparencia</p>
                <dl class="mt-3 space-y-2 text-slate-600">
                    <div class="flex justify-between gap-4"><dt>Caixa</dt><dd class="font-semibold {{ $settings['module_cash_register'] ? 'text-green-700' : 'text-slate-500' }}">{{ $settings['module_cash_register'] ? 'Habilitado' : 'Desabilitado' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt>Fiado</dt><dd class="font-semibold {{ $settings['module_credit_receivables'] ? 'text-green-700' : 'text-slate-500' }}">{{ $settings['module_credit_receivables'] ? 'Habilitado' : 'Desabilitado' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt>Tema</dt><dd class="font-semibold text-slate-900">{{ $themes[$settings['theme']] ?? 'Padrao claro' }}</dd></div>
                </dl>
            </div>
        </aside>
    </div>

    <script>
        document.querySelectorAll('[data-theme-settings-form]').forEach((form) => {
            const syncCoordinates = () => {
                form.querySelectorAll('[data-coordinate-display]').forEach((displayInput) => {
                    const field = displayInput.dataset.coordinateDisplay;
                    const payloadInput = form.querySelector(`[data-coordinate-payload="${field}"]`);

                    if (payloadInput) {
                        payloadInput.value = displayInput.value;
                    }
                });
            };

            form.addEventListener('input', syncCoordinates);
            form.addEventListener('change', syncCoordinates);
            form.addEventListener('submit', syncCoordinates);
        });
    </script>
</x-app.layout>
