<x-app.layout heading="Configurações" title="Configurações · AutoFlow">
    <div class="grid gap-5 xl:grid-cols-[1fr_360px]">
        <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" data-theme-settings-form data-tour="settings-form">
            @csrf
            @method('PUT')
            @include('app.components.errors')

            <section data-tour="settings-unit">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-700">Geral</p>
                <h2 class="mt-1 text-xl font-bold text-slate-950">Dados da unidade</h2>
                <p class="mt-1 text-sm text-slate-500">Use essas informacoes para personalizar o painel e preparar recibos/notificacoes no futuro.</p>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <label class="block md:col-span-2">
                        <span class="text-sm font-semibold text-slate-700">Logo da unidade</span>
                        <div class="mt-1 flex flex-wrap items-center gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4" data-tour="settings-logo">
                            <div class="flex h-20 w-32 items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-white">
                                @if ($currentLocation?->logo_path)
                                    <img src="{{ asset('storage/'.$currentLocation->logo_path) }}" alt="{{ $currentLocation->name }}" class="max-h-full max-w-full object-contain">
                                @else
                                    <span class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Sem logo</span>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <input name="logo" type="file" accept="image/png,image/jpeg,image/webp" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-sm file:font-bold file:text-blue-700">
                                <p class="mt-1 text-xs text-slate-500">PNG, JPG ou WEBP até 5MB.</p>
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

                    <div class="md:col-span-2 rounded-2xl border border-amber-200 bg-amber-50 p-4" data-tour="settings-map">
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

                    <div class="md:col-span-2 rounded-2xl border border-slate-200 bg-slate-50 p-4" data-tour="settings-hours">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-black text-slate-900">Horario de funcionamento</p>
                                <p class="mt-1 text-xs leading-5 text-slate-500">Defina os dias e horarios para o mapa publico exibir Aberto ou Fechado automaticamente.</p>
                            </div>
                            <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-black text-blue-700 ring-1 ring-blue-100">Afeta o mapa publico</span>
                        </div>

                        <div class="mt-4 grid gap-3">
                            @foreach ($businessHourDays as $day => $label)
                                @php
                                    $dayHours = $businessHours[$day] ?? ['is_open' => false, 'opens' => '08:00', 'closes' => '18:00'];
                                    $isOpen = (bool) old('business_hours.'.$day.'.is_open', $dayHours['is_open']);
                                @endphp
                                <div class="grid gap-3 rounded-xl border border-slate-200 bg-white p-3 md:grid-cols-[150px_1fr_1fr] md:items-end">
                                    <label class="flex items-center gap-3 md:pb-2">
                                        <input type="hidden" name="business_hours[{{ $day }}][is_open]" value="0">
                                        <input type="checkbox" name="business_hours[{{ $day }}][is_open]" value="1" @checked($isOpen) class="h-4 w-4 rounded border-slate-300 text-blue-700">
                                        <span class="text-sm font-black text-slate-900">{{ $label }}</span>
                                    </label>

                                    <label class="block">
                                        <span class="text-xs font-bold text-slate-500">Abertura</span>
                                        <input type="time" name="business_hours[{{ $day }}][opens]" value="{{ old('business_hours.'.$day.'.opens', $dayHours['opens']) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                        @error('business_hours.'.$day.'.opens') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                                    </label>

                                    <label class="block">
                                        <span class="text-xs font-bold text-slate-500">Fechamento</span>
                                        <input type="time" name="business_hours[{{ $day }}][closes]" value="{{ old('business_hours.'.$day.'.closes', $dayHours['closes']) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                        @error('business_hours.'.$day.'.closes') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            <section class="border-t border-slate-200 pt-5" data-tour="settings-theme">
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

            <section class="border-t border-slate-200 pt-5" data-tour="settings-modules">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-700">Modulos opcionais</p>
                <h2 class="mt-1 text-xl font-bold text-slate-950">Controle o que aparece no sistema</h2>
                <p class="mt-1 text-sm text-slate-500">Ideal para lava-rápidos menores que não precisam operar agenda, caixa completo ou fiado.</p>

                <div class="mt-4 grid gap-3 md:grid-cols-3">
                    <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4 hover:bg-slate-50">
                        <input type="checkbox" name="module_schedule" value="1" @checked(old('module_schedule', $settings['module_schedule'] ?? \App\Models\AppSetting::DEFAULTS['module_schedule'])) class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-700">
                        <span>
                            <span class="block font-bold text-slate-900">Habilitar Agenda</span>
                            <span class="mt-1 block text-sm text-slate-500">Mostra agenda diaria e permite agendar lavagens para data/hora futura.</span>
                        </span>
                    </label>

                    <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4 hover:bg-slate-50">
                        <input type="checkbox" name="module_cash_register" value="1" @checked(old('module_cash_register', $settings['module_cash_register'] ?? \App\Models\AppSetting::DEFAULTS['module_cash_register'])) class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-700">
                        <span>
                            <span class="block font-bold text-slate-900">Habilitar Caixa</span>
                            <span class="mt-1 block text-sm text-slate-500">Mostra abertura, sangria, suprimento e fechamento de caixa.</span>
                        </span>
                    </label>

                    <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4 hover:bg-slate-50">
                        <input type="checkbox" name="module_credit_receivables" value="1" @checked(old('module_credit_receivables', $settings['module_credit_receivables'] ?? \App\Models\AppSetting::DEFAULTS['module_credit_receivables'])) class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-700">
                        <span>
                            <span class="block font-bold text-slate-900">Habilitar Fiado</span>
                            <span class="mt-1 block text-sm text-slate-500">Mostra contas a receber e baixa de pagamentos pendentes.</span>
                        </span>
                    </label>
                </div>
            </section>

            <section class="border-t border-slate-200 pt-5" data-tour="settings-loyalty">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-700">Programa de fidelidade</p>
                <h2 class="mt-1 text-xl font-bold text-slate-950">Cupons para clientes recorrentes</h2>
                <p class="mt-1 text-sm text-slate-500">Configure quando o cliente ganha um cupom e qual benefício será oferecido.</p>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4 hover:bg-slate-50 md:col-span-2">
                        <input data-loyalty-active type="checkbox" name="loyalty_is_active" value="1" @checked(old('loyalty_is_active', $loyaltyProgram->is_active)) class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-700">
                        <span>
                            <span class="block font-bold text-slate-900">Habilitar Programa de Fidelidade</span>
                            <span class="mt-1 block text-sm text-slate-500">Ao completar a meta, o sistema gera um cupom personalizado para o cliente.</span>
                        </span>
                    </label>

                    <label class="block">
                        <span class="text-sm font-semibold text-slate-700">Meta de lavagens</span>
                        <input name="loyalty_threshold" type="number" min="2" max="99" value="{{ old('loyalty_threshold', $loyaltyProgram->threshold ?: 10) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        @error('loyalty_threshold') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-semibold text-slate-700">Validade do cupom</span>
                        <input name="loyalty_coupon_valid_days" type="number" min="1" max="365" value="{{ old('loyalty_coupon_valid_days', $loyaltyProgram->coupon_valid_days ?: 30) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <span class="mt-1 block text-xs text-slate-500">Quantidade de dias após a emissão.</span>
                        @error('loyalty_coupon_valid_days') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-semibold text-slate-700">Como contar</span>
                        <select data-loyalty-count-scope name="loyalty_count_scope" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            @foreach ($loyaltyCountScopes as $value => $label)
                                <option value="{{ $value }}" @selected(old('loyalty_count_scope', $loyaltyProgram->count_scope ?: \App\Models\LoyaltyProgram::COUNT_ANY) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('loyalty_count_scope') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="block" data-loyalty-field="category">
                        <span class="text-sm font-semibold text-slate-700">Categoria contada</span>
                        <select name="loyalty_qualifying_category" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <option value="">Selecione quando usar categoria</option>
                            @foreach ($loyaltyCategories as $category)
                                <option value="{{ $category }}" @selected(old('loyalty_qualifying_category', $loyaltyProgram->qualifying_category) === $category)>{{ $category }}</option>
                            @endforeach
                        </select>
                        @error('loyalty_qualifying_category') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="block" data-loyalty-field="service">
                        <span class="text-sm font-semibold text-slate-700">Serviço contado</span>
                        <select name="loyalty_qualifying_service_id" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <option value="">Selecione quando usar serviço específico</option>
                            @foreach ($loyaltyServices as $service)
                                <option value="{{ $service->id }}" @selected((string) old('loyalty_qualifying_service_id', $loyaltyProgram->qualifying_service_id) === (string) $service->id)>{{ $service->name }} · {{ $service->category }}</option>
                            @endforeach
                        </select>
                        @error('loyalty_qualifying_service_id') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-semibold text-slate-700">Prêmio</span>
                        <select data-loyalty-reward-type name="loyalty_reward_type" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            @foreach ($loyaltyRewardTypes as $value => $label)
                                <option value="{{ $value }}" @selected(old('loyalty_reward_type', $loyaltyProgram->reward_type ?: \App\Models\LoyaltyProgram::REWARD_FIXED_SERVICE) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('loyalty_reward_type') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="block" data-loyalty-field="reward-service">
                        <span class="text-sm font-semibold text-slate-700">Serviço do cupom</span>
                        <select name="loyalty_reward_service_id" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <option value="">Selecione quando o prêmio for serviço definido</option>
                            @foreach ($loyaltyServices as $service)
                                <option value="{{ $service->id }}" @selected((string) old('loyalty_reward_service_id', $loyaltyProgram->reward_service_id) === (string) $service->id)>{{ $service->name }} · {{ $service->category }}</option>
                            @endforeach
                        </select>
                        @error('loyalty_reward_service_id') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="block" data-loyalty-field="discount">
                        <span class="text-sm font-semibold text-slate-700">Valor do desconto</span>
                        <input name="loyalty_discount_value" inputmode="decimal" value="{{ old('loyalty_discount_value', $loyaltyProgram->discount_value) }}" placeholder="Ex: 20 ou 15" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <span class="mt-1 block text-xs text-slate-500">Use apenas quando o prêmio for desconto em reais ou percentual.</span>
                        @error('loyalty_discount_value') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>
                </div>
            </section>

            <section class="border-t border-slate-200 pt-5" data-tour="settings-permissions">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-700">Permissões da equipe</p>
                <h2 class="mt-1 text-xl font-bold text-slate-950">Matriz de acesso por perfil</h2>
                <p class="mt-1 text-sm text-slate-500">Veja o que cada perfil pode acessar hoje. Permissões padrão ficam fixas; exceções configuráveis aparecem destacadas.</p>

                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                    @foreach ($permissionMatrix as $role => $matrix)
                        @php
                            $basePermissions = collect($matrix['base'])->reject(fn ($permission) => $permission === \App\Support\Access\AccessControl::ACCESS_PRODUCT_ADMIN)->values();
                            $enabledOverrides = collect($matrix['enabled']);
                            $blockedOverrides = collect($matrix['blocked']);
                        @endphp
                        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="font-black text-slate-950">{{ $roleLabels[$role] ?? $role }}</h3>
                                    <p class="mt-1 text-sm text-slate-500">
                                        {{ $basePermissions->count() }} permissões padrão
                                        @if ($enabledOverrides->isNotEmpty())
                                            · {{ $enabledOverrides->count() }} exceção{{ $enabledOverrides->count() === 1 ? '' : 'ões' }} liberada{{ $enabledOverrides->count() === 1 ? '' : 's' }}
                                        @endif
                                    </p>
                                </div>
                                <span class="rounded-full bg-white px-3 py-1 text-xs font-black text-slate-600 ring-1 ring-slate-200">{{ $role }}</span>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-2">
                                @foreach ($basePermissions as $permission)
                                    <span title="{{ $permissionDescriptions[$permission] ?? '' }}" class="rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-700 ring-1 ring-slate-200">
                                        {{ $permissionLabels[$permission] ?? $permission }}
                                    </span>
                                @endforeach

                                @foreach ($enabledOverrides as $permission)
                                    <span title="{{ $permissionDescriptions[$permission] ?? '' }}" class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700 ring-1 ring-emerald-200">
                                        + {{ $permissionLabels[$permission] ?? $permission }}
                                    </span>
                                @endforeach

                                @if ($basePermissions->isEmpty() && $enabledOverrides->isEmpty())
                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-500 ring-1 ring-slate-200">Sem permissões operacionais</span>
                                @endif
                            </div>

                            @if ($blockedOverrides->isNotEmpty())
                                <div class="mt-4 border-t border-slate-200 pt-3">
                                    <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-400">Bloqueado por configuração</p>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @foreach ($blockedOverrides as $permission)
                                            <span title="{{ $permissionDescriptions[$permission] ?? '' }}" class="rounded-full bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700 ring-1 ring-rose-100">
                                                {{ $permissionLabels[$permission] ?? $permission }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="border-t border-slate-200 pt-5" data-tour="settings-permission-overrides">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-700">Exceções configuráveis</p>
                <h2 class="mt-1 text-xl font-bold text-slate-950">Privilégios operacionais</h2>
                <p class="mt-1 text-sm text-slate-500">Ajuste exceções por perfil sem liberar áreas sensíveis como financeiro, assinatura ou administração do produto.</p>

                <div class="mt-4 space-y-4">
                    @foreach ($rolePermissionGroups as $role => $permissions)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="font-black text-slate-950">{{ $roleLabels[$role] ?? $role }}</p>
                                    <p class="mt-1 text-sm text-slate-500">Por padrão, este perfil continua restrito ao fluxo operacional.</p>
                                </div>
                                <span class="rounded-full bg-white px-3 py-1 text-xs font-black text-slate-600">{{ count($permissions) }} opções</span>
                            </div>

                            <div class="mt-4 grid gap-3 md:grid-cols-3">
                                @foreach ($permissions as $permission)
                                    @php
                                        $submittedPermissions = old('role_permissions', []);
                                        $hasOldPermissionInput = session()->hasOldInput('role_permissions');
                                        $isChecked = $hasOldPermissionInput && is_array($submittedPermissions)
                                            ? array_key_exists($permission, $submittedPermissions[$role] ?? [])
                                            : ($rolePermissionValues[$role][$permission] ?? false);
                                    @endphp
                                    <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-white p-4 hover:border-blue-200 hover:bg-blue-50/40">
                                        <input type="checkbox" name="role_permissions[{{ $role }}][{{ $permission }}]" value="1" @checked($isChecked) class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-700">
                                        <span>
                                            <span class="block font-bold text-slate-900">{{ $permissionLabels[$permission] ?? $permission }}</span>
                                            <span class="mt-1 block text-sm leading-5 text-slate-500">{{ $permissionDescriptions[$permission] ?? 'Permissão configurável para este perfil.' }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <div class="flex justify-end border-t border-slate-200 pt-5">
                <button class="rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-800" data-tour="settings-save">Salvar configuracoes</button>
            </div>
        </form>

        <aside class="space-y-4">
            <div class="rounded-2xl border border-blue-100 bg-blue-50 p-5 text-sm text-blue-950" data-tour="settings-hints">
                <p class="font-bold">Sugestao de operacao</p>
                <p class="mt-2 leading-6">Comece com Caixa e Fiado desabilitados para clientes pequenos. Habilite apenas quando o lava-rapido tiver rotina de operador, conferencias ou contas a receber.</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 text-sm shadow-sm" data-tour="settings-theme-summary">
                <p class="font-bold text-slate-950">Quando usar tema dark?</p>
                <p class="mt-2 leading-6 text-slate-500">E uma boa opcao para recepcao com pouca luz, TVs de acompanhamento e uso noturno. Para escritorio claro, mantenha o padrao claro ou sistema.</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 text-sm shadow-sm" data-tour="settings-profile-summary">
                <p class="font-bold text-slate-950">Perfil da unidade</p>
                <dl class="mt-3 space-y-2 text-slate-600">
                    <div class="flex justify-between gap-4"><dt>Nome</dt><dd class="max-w-44 truncate font-semibold text-slate-900">{{ $currentLocation?->name ?? $settings['company_name'] }}</dd></div>
                    <div class="flex justify-between gap-4"><dt>CNPJ</dt><dd class="font-semibold text-slate-900">{{ $currentLocation?->document ?: '-' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt>Endereço</dt><dd class="max-w-44 truncate font-semibold text-slate-900">{{ $currentLocation?->fullAddress() ?: '-' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt>Cidade/UF</dt><dd class="font-semibold text-slate-900">{{ trim(($currentLocation?->city ?? '').'/'.($currentLocation?->state ?? ''), '/') ?: '-' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt>Funcionamento</dt><dd class="max-w-44 truncate font-semibold text-slate-900">{{ $currentLocation?->opening_hours ?: '-' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt>Mapa</dt><dd class="font-semibold {{ $currentLocation?->hasCoordinates() ? 'text-green-700' : 'text-amber-700' }}">{{ $currentLocation?->hasCoordinates() ? 'Com coordenadas' : 'Pendente' }}</dd></div>
                </dl>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 text-sm shadow-sm" data-tour="settings-module-summary">
                <p class="font-bold text-slate-950">Modulos e aparencia</p>
                <dl class="mt-3 space-y-2 text-slate-600">
                    @php
                        $cashRegisterEnabled = $settings['module_cash_register'] ?? \App\Models\AppSetting::DEFAULTS['module_cash_register'];
                        $creditReceivablesEnabled = $settings['module_credit_receivables'] ?? \App\Models\AppSetting::DEFAULTS['module_credit_receivables'];
                        $scheduleEnabled = $settings['module_schedule'] ?? \App\Models\AppSetting::DEFAULTS['module_schedule'];
                    @endphp
                    <div class="flex justify-between gap-4"><dt>Caixa</dt><dd class="font-semibold {{ $cashRegisterEnabled ? 'text-green-700' : 'text-slate-500' }}">{{ $cashRegisterEnabled ? 'Habilitado' : 'Desabilitado' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt>Fiado</dt><dd class="font-semibold {{ $creditReceivablesEnabled ? 'text-green-700' : 'text-slate-500' }}">{{ $creditReceivablesEnabled ? 'Habilitado' : 'Desabilitado' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt>Agenda</dt><dd class="font-semibold {{ $scheduleEnabled ? 'text-green-700' : 'text-slate-500' }}">{{ $scheduleEnabled ? 'Habilitada' : 'Desabilitada' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt>Fidelidade</dt><dd class="font-semibold {{ $loyaltyProgram->is_active ? 'text-green-700' : 'text-slate-500' }}">{{ $loyaltyProgram->is_active ? 'Habilitada' : 'Desabilitada' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt>Tema</dt><dd class="font-semibold text-slate-900">{{ $themes[$settings['theme']] ?? 'Padrao claro' }}</dd></div>
                </dl>
            </div>
        </aside>
    </div>

    @php
        $settingsTour = [
            'key' => 'settings.edit.v1',
            'title' => 'Configurações da unidade',
            'steps' => [
                [
                    'target' => '[data-tour="settings-unit"]',
                    'title' => 'Dados principais',
                    'body' => 'Aqui ficam as informações que identificam o lava-rápido no painel, no mapa público, recibos e comunicações.',
                ],
                [
                    'target' => '[data-tour="settings-logo"]',
                    'title' => 'Logo da unidade',
                    'body' => 'Envie a marca do estabelecimento para substituir o logo padrão nas telas internas da unidade.',
                ],
                [
                    'target' => '[data-tour="settings-map"]',
                    'title' => 'Localização no mapa',
                    'body' => 'Confira latitude e longitude para que o lava-rápido apareça no ponto correto no mapa público.',
                ],
                [
                    'target' => '[data-tour="settings-hours"]',
                    'title' => 'Horário de funcionamento',
                    'body' => 'Esses horários definem quando a unidade aparece como aberta e também ajudam a bloquear operação fora do expediente.',
                ],
                [
                    'target' => '[data-tour="settings-theme"]',
                    'title' => 'Tema do painel',
                    'body' => 'Escolha o visual usado pela equipe. A opção Sistema acompanha a preferência do navegador.',
                ],
                [
                    'target' => '[data-tour="settings-modules"]',
                    'title' => 'Módulos opcionais',
                    'body' => 'Ative apenas o que o estabelecimento usa: Agenda, Caixa e Fiado aparecem ou somem do sistema conforme essas chaves.',
                ],
                [
                    'target' => '[data-tour="settings-loyalty"]',
                    'title' => 'Programa de fidelidade',
                    'body' => 'Configure metas, validade e prêmio dos cupons para clientes recorrentes.',
                ],
                [
                    'target' => '[data-tour="settings-permissions"]',
                    'title' => 'Permissões por perfil',
                    'body' => 'A matriz mostra o que Dono, Administrador, Atendente e Operador podem acessar hoje.',
                ],
                [
                    'target' => '[data-tour="settings-permission-overrides"]',
                    'title' => 'Exceções operacionais',
                    'body' => 'Use esta área para liberar privilégios específicos sem abrir áreas sensíveis como financeiro ou assinatura.',
                ],
                [
                    'target' => '[data-tour="settings-module-summary"]',
                    'title' => 'Resumo lateral',
                    'body' => 'Antes de salvar, confira rapidamente quais módulos e aparência ficarão ativos.',
                ],
                [
                    'target' => '[data-tour="settings-save"]',
                    'title' => 'Salvar alterações',
                    'body' => 'Depois de revisar, salve para aplicar as mudanças na unidade.',
                ],
            ],
        ];
    @endphp
    <script type="application/json" data-onboarding-tour>
        {!! json_encode($settingsTour, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>

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

            const loyaltyActive = form.querySelector('[data-loyalty-active]');
            const countScope = form.querySelector('[data-loyalty-count-scope]');
            const rewardType = form.querySelector('[data-loyalty-reward-type]');

            const setFieldState = (field, visible) => {
                const wrapper = form.querySelector(`[data-loyalty-field="${field}"]`);

                if (!wrapper) {
                    return;
                }

                wrapper.classList.toggle('hidden', !visible);
                wrapper.querySelectorAll('input, select, textarea').forEach((input) => {
                    input.disabled = !visible;
                });
            };

            const syncLoyaltyFields = () => {
                const isActive = loyaltyActive?.checked ?? false;
                const countValue = countScope?.value || 'any';
                const rewardValue = rewardType?.value || 'fixed_service';

                if (countScope) {
                    countScope.disabled = !isActive;
                }

                if (rewardType) {
                    rewardType.disabled = !isActive;
                }

                form.querySelectorAll('input[name^="loyalty_"]').forEach((input) => {
                    if (input !== loyaltyActive) {
                        input.disabled = !isActive;
                    }
                });

                setFieldState('category', isActive && countValue === 'category');
                setFieldState('service', isActive && countValue === 'service');
                setFieldState('reward-service', isActive && rewardValue === 'fixed_service');
                setFieldState('discount', isActive && ['discount_amount', 'discount_percent'].includes(rewardValue));
            };

            loyaltyActive?.addEventListener('change', syncLoyaltyFields);
            countScope?.addEventListener('change', syncLoyaltyFields);
            rewardType?.addEventListener('change', syncLoyaltyFields);
            syncLoyaltyFields();
        });
    </script>
</x-app.layout>
