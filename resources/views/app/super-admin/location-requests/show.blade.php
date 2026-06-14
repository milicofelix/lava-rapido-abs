<x-app.layout heading="Detalhes da solicitação" title="Solicitação · AutoFlow">
    @php
        $badgeClass = match ($locationRequest->status) {
            \App\Models\WashLocationRequest::STATUS_APPROVED => 'bg-emerald-100 text-emerald-700',
            \App\Models\WashLocationRequest::STATUS_REJECTED => 'bg-rose-100 text-rose-700',
            default => 'bg-amber-100 text-amber-800',
        };
        $mapsSearchUrl = 'https://www.google.com/maps/search/?api=1&query='.rawurlencode(collect([
            $locationRequest->address,
            $locationRequest->district,
            $locationRequest->city,
            $locationRequest->state,
            $locationRequest->zip_code,
        ])->filter()->implode(' '));
    @endphp

    <div class="space-y-5">
        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-bold text-rose-800">
                {{ session('error') }}
            </div>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('super-admin.location-requests.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700">← Voltar para solicitações</a>
            <span class="rounded-full px-3 py-1 text-xs font-black {{ $badgeClass }}">{{ $locationRequest->statusLabel() }}</span>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 pb-5">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.22em] text-blue-600">Lava-rápido solicitado</p>
                    <h2 class="mt-2 text-3xl font-black text-slate-950">{{ $locationRequest->business_name }}</h2>
                    <p class="mt-2 text-sm text-slate-500">Solicitado em {{ $locationRequest->created_at?->format('d/m/Y H:i') }}</p>
                </div>

                @if ($locationRequest->isPending())
                    <div class="grid gap-3 sm:grid-cols-2">
                        <form method="POST" action="{{ route('super-admin.location-requests.approve', $locationRequest) }}" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4" data-location-approval-form>
                            @csrf
                            @method('PATCH')
                            <label class="text-xs font-black uppercase tracking-[0.18em] text-emerald-700">Aprovar</label>
                            <div class="mt-3 rounded-xl border border-emerald-200 bg-white/80 p-3">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div>
                                        <p class="text-sm font-black text-slate-950">Localização no mapa</p>
                                        <p class="mt-1 text-xs leading-5 text-slate-600">Carregue as coordenadas pelo endereço antes de aprovar. Isso evita marcador em local incorreto.</p>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" data-geocode-url="{{ route('super-admin.location-requests.geocode', $locationRequest) }}" class="rounded-lg border border-emerald-200 bg-white px-2.5 py-1.5 text-xs font-black text-emerald-700 hover:bg-emerald-100">Carregar latitude/longitude</button>
                                        <a href="{{ $mapsSearchUrl }}" target="_blank" rel="noopener" class="rounded-lg border border-emerald-200 px-2.5 py-1.5 text-xs font-black text-emerald-700 hover:bg-emerald-100">Abrir no Maps</a>
                                    </div>
                                </div>
                                <p class="mt-3 rounded-lg border border-emerald-100 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800" data-coordinate-feedback>As coordenadas ainda não foram carregadas.</p>
                                <label class="mt-3 hidden" data-maps-url-fallback>
                                    <span class="text-xs font-bold text-slate-600">URL do Google Maps</span>
                                    <div class="mt-1 grid gap-2 sm:grid-cols-[1fr_auto]">
                                        <input name="google_maps_url" value="{{ old('google_maps_url') }}" placeholder="Cole aqui a URL completa do Google Maps" data-maps-url class="w-full rounded-lg border border-emerald-200 px-3 py-2 text-sm">
                                        <button type="button" data-extract-maps-url class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-black text-white hover:bg-emerald-700">Extrair da URL</button>
                                    </div>
                                    <span class="mt-1 block text-xs text-slate-600">Use este campo apenas se a busca automática não encontrar o endereço.</span>
                                </label>
                                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                    <label class="block">
                                        <span class="text-xs font-bold text-slate-600">Latitude</span>
                                        <input type="hidden" name="latitude" value="{{ old('latitude') }}" data-coordinate-payload="latitude">
                                        <input value="{{ old('latitude') }}" required inputmode="decimal" placeholder="-23.5489100" data-coordinate-display="latitude" class="mt-1 w-full rounded-lg border border-emerald-200 px-3 py-2 text-sm disabled:bg-white disabled:text-slate-900">
                                    </label>
                                    <label class="block">
                                        <span class="text-xs font-bold text-slate-600">Longitude</span>
                                        <input type="hidden" name="longitude" value="{{ old('longitude') }}" data-coordinate-payload="longitude">
                                        <input value="{{ old('longitude') }}" required inputmode="decimal" placeholder="-46.6341200" data-coordinate-display="longitude" class="mt-1 w-full rounded-lg border border-emerald-200 px-3 py-2 text-sm disabled:bg-white disabled:text-slate-900">
                                    </label>
                                </div>
                            </div>
                            @if (! $locationRequest->owner_password)
                                <div class="mt-3 rounded-xl border border-emerald-200 bg-white/80 p-3">
                                    <p class="text-sm font-black text-slate-950">Senha inicial do dono</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-600">Esta solicitação ainda não possui senha de primeiro acesso. Defina uma antes de aprovar.</p>
                                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                        <label class="block">
                                            <span class="text-xs font-bold text-slate-600">Senha</span>
                                            <input type="password" name="owner_password" minlength="8" autocomplete="new-password" class="mt-1 w-full rounded-lg border border-emerald-200 px-3 py-2 text-sm">
                                        </label>
                                        <label class="block">
                                            <span class="text-xs font-bold text-slate-600">Confirmar senha</span>
                                            <input type="password" name="owner_password_confirmation" minlength="8" autocomplete="new-password" class="mt-1 w-full rounded-lg border border-emerald-200 px-3 py-2 text-sm">
                                        </label>
                                    </div>
                                </div>
                            @endif
                            <textarea name="decision_notes" rows="3" class="mt-3 w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm" placeholder="Observação opcional"></textarea>
                            <button class="mt-3 w-full rounded-xl bg-emerald-600 px-4 py-2 text-sm font-black text-white">Aprovar e iniciar trial</button>
                        </form>

                        <form method="POST" action="{{ route('super-admin.location-requests.reject', $locationRequest) }}" class="rounded-2xl border border-rose-200 bg-rose-50 p-4">
                            @csrf
                            @method('PATCH')
                            <label class="text-xs font-black uppercase tracking-[0.18em] text-rose-700">Rejeitar</label>
                            <textarea name="decision_notes" rows="3" required class="mt-3 w-full rounded-xl border border-rose-200 px-3 py-2 text-sm" placeholder="Motivo obrigatório"></textarea>
                            <button class="mt-3 w-full rounded-xl bg-rose-600 px-4 py-2 text-sm font-black text-white">Rejeitar solicitação</button>
                        </form>
                    </div>
                @else
                    <div class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">
                        <p class="font-bold text-slate-950">Solicitação analisada</p>
                        <p class="mt-1">Decisão em {{ $locationRequest->decided_at?->format('d/m/Y H:i') }}.</p>
                        @if ($locationRequest->decidedBy)
                            <p class="mt-1">Responsável: <strong>{{ $locationRequest->decidedBy->name }}</strong></p>
                        @endif
                    </div>
                @endif
            </div>

            @error('decision_notes')
                <p class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">{{ $message }}</p>
            @enderror
            @error('latitude')
                <p class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">{{ $message }}</p>
            @enderror
            @error('longitude')
                <p class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">{{ $message }}</p>
            @enderror
            @error('owner_password')
                <p class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">{{ $message }}</p>
            @enderror

            @if ($locationRequest->washLocation)
                <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-700">Unidade criada</p>
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-lg font-black text-slate-950">{{ $locationRequest->washLocation->name }}</p>
                            <p class="text-sm text-slate-600">Status: {{ $locationRequest->washLocation->accountStatusLabel() }}</p>
                            <p class="text-sm text-slate-600">Trial até {{ $locationRequest->washLocation->trial_ends_at?->format('d/m/Y') }}</p>
                            <p class="text-sm text-slate-600">Mapa: {{ $locationRequest->washLocation->hasCoordinates() ? $locationRequest->washLocation->mapLatitude().', '.$locationRequest->washLocation->mapLongitude() : 'coordenadas pendentes' }}</p>
                            @if ($locationRequest->washLocation->subscription_ends_at)
                                <p class="text-sm text-slate-600">Assinatura até {{ $locationRequest->washLocation->subscription_ends_at->format('d/m/Y') }}</p>
                            @endif
                        </div>
                        <a href="{{ route('public.locations.show', $locationRequest->washLocation) }}" target="_blank" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-black text-white">Ver página pública</a>
                    </div>
                </div>
            @endif

            <div class="mt-6 grid gap-5 lg:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 p-5">
                    <h3 class="font-black text-slate-950">Responsável</h3>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div><dt class="text-slate-500">Nome</dt><dd class="font-bold text-slate-950">{{ $locationRequest->responsible_name }}</dd></div>
                        <div><dt class="text-slate-500">E-mail</dt><dd class="font-bold text-slate-950">{{ $locationRequest->email }}</dd></div>
                        <div><dt class="text-slate-500">WhatsApp</dt><dd class="font-bold text-slate-950">{{ $locationRequest->phone }}</dd></div>
                    </dl>
                </div>

                <div class="rounded-2xl border border-slate-200 p-5">
                    <h3 class="font-black text-slate-950">Unidade</h3>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div><dt class="text-slate-500">Endereço</dt><dd class="font-bold text-slate-950">{{ $locationRequest->address }}</dd></div>
                        <div><dt class="text-slate-500">Bairro</dt><dd class="font-bold text-slate-950">{{ $locationRequest->district ?: 'Não informado' }}</dd></div>
                        <div><dt class="text-slate-500">Cidade/UF</dt><dd class="font-bold text-slate-950">{{ $locationRequest->city }}/{{ $locationRequest->state }}</dd></div>
                        <div><dt class="text-slate-500">CEP</dt><dd class="font-bold text-slate-950">{{ $locationRequest->zip_code ?: 'Não informado' }}</dd></div>
                        <div><dt class="text-slate-500">Funcionários</dt><dd class="font-bold text-slate-950">{{ $locationRequest->employees_count ?: 'Não informado' }}</dd></div>
                    </dl>
                </div>
            </div>

            <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <h3 class="font-black text-slate-950">Mensagem</h3>
                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $locationRequest->notes ?: 'Nenhuma observação enviada.' }}</p>
            </div>

            @if ($locationRequest->decision_notes)
                <div class="mt-5 rounded-2xl border border-slate-200 bg-white p-5">
                    <h3 class="font-black text-slate-950">Observação da decisão</h3>
                    <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $locationRequest->decision_notes }}</p>
                </div>
            @endif
        </section>
    </div>

    <script>
        document.querySelectorAll('[data-location-approval-form]').forEach((form) => {
            const extractCoordinatesFromMapsUrl = (url) => {
                const decodedUrl = decodeURIComponent(String(url || ''));
                const patterns = [
                    /@(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)/,
                    /!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/,
                    /[?&](?:ll|q)=(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)/
                ];

                for (const pattern of patterns) {
                    const match = decodedUrl.match(pattern);

                    if (! match) {
                        continue;
                    }

                    const latitude = Number(match[1]);
                    const longitude = Number(match[2]);

                    if (Number.isFinite(latitude) && Number.isFinite(longitude) && latitude >= -90 && latitude <= 90 && longitude >= -180 && longitude <= 180) {
                        return { latitude, longitude };
                    }
                }

                return null;
            };
            const syncCoordinates = () => {
                form.querySelectorAll('[data-coordinate-display]').forEach((displayInput) => {
                    const field = displayInput.dataset.coordinateDisplay;
                    const payloadInput = form.querySelector(`[data-coordinate-payload="${field}"]`);

                    if (payloadInput) {
                        payloadInput.value = displayInput.value;
                    }
                });
            };
            const setFeedback = (message, isError = false) => {
                const feedback = form.querySelector('[data-coordinate-feedback]');

                if (! feedback) {
                    return;
                }

                feedback.textContent = message;
                feedback.classList.toggle('border-rose-200', isError);
                feedback.classList.toggle('bg-rose-50', isError);
                feedback.classList.toggle('text-rose-700', isError);
                feedback.classList.toggle('border-emerald-100', ! isError);
                feedback.classList.toggle('bg-emerald-50', ! isError);
                feedback.classList.toggle('text-emerald-800', ! isError);
            };
            const showMapsUrlFallback = () => {
                form.querySelector('[data-maps-url-fallback]')?.classList.remove('hidden');
            };
            const applyCoordinates = (coordinates) => {
                form.querySelector('[data-coordinate-display="latitude"]').value = coordinates.latitude;
                form.querySelector('[data-coordinate-display="longitude"]').value = coordinates.longitude;
                syncCoordinates();
                setFeedback(`Coordenadas carregadas: ${coordinates.latitude}, ${coordinates.longitude}.`);
            };
            const applyMapsUrlCoordinates = () => {
                const mapsUrlInput = form.querySelector('[data-maps-url]');
                const coordinates = extractCoordinatesFromMapsUrl(mapsUrlInput?.value);

                if (coordinates) {
                    applyCoordinates(coordinates);
                    return true;
                }

                if (mapsUrlInput?.value) {
                    setFeedback('Não encontrei coordenadas nessa URL. Copie a URL completa da barra do navegador no Google Maps.', true);
                }

                return false;
            };
            const loadCoordinates = async (button) => {
                const originalText = button.textContent;
                button.disabled = true;
                button.textContent = 'Carregando...';
                setFeedback('Buscando coordenadas pelo endereço...');

                try {
                    const response = await fetch(button.dataset.geocodeUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: '{}',
                    });

                    const payload = await response.json();

                    if (! response.ok) {
                        throw new Error(payload.message || 'Não foi possível carregar coordenadas.');
                    }

                    if (payload.fallback_required) {
                        setFeedback(payload.message || 'Não encontrei coordenadas automaticamente. Use o campo de apoio.', true);
                        showMapsUrlFallback();

                        if (payload.maps_url) {
                            window.open(payload.maps_url, '_blank', 'noopener');
                        }

                        return;
                    }

                    applyCoordinates(payload);
                } catch (error) {
                    setFeedback(error.message || 'Não foi possível carregar coordenadas.', true);
                    showMapsUrlFallback();
                } finally {
                    button.disabled = false;
                    button.textContent = originalText;
                }
            };

            form.addEventListener('input', syncCoordinates);
            form.addEventListener('change', syncCoordinates);
            form.querySelector('[data-geocode-url]')?.addEventListener('click', (event) => {
                loadCoordinates(event.currentTarget);
            });
            form.querySelector('[data-extract-maps-url]')?.addEventListener('click', applyMapsUrlCoordinates);
            form.querySelector('[data-maps-url]')?.addEventListener('input', applyMapsUrlCoordinates);
            form.querySelector('[data-maps-url]')?.addEventListener('paste', () => window.setTimeout(applyMapsUrlCoordinates));
            form.addEventListener('submit', () => {
                applyMapsUrlCoordinates();
                syncCoordinates();
            });
        });
    </script>
</x-app.layout>
