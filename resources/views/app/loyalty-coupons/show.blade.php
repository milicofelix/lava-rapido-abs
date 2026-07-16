<x-app.layout heading="Cupom {{ $coupon->code }}" title="Cupom {{ $coupon->code }} · AutoFlow">
    @php
        $location = $coupon->washLocation;
        $customer = $coupon->customer;
        $benefit = $coupon->benefitLabel();
        $effectiveStatus = $coupon->effectiveStatus();
        $statusTone = match ($effectiveStatus) {
            \App\Models\LoyaltyCoupon::STATUS_ACTIVE => [
                'pill' => 'from-emerald-500 to-green-700 text-white',
                'text' => 'text-emerald-600',
                'bg' => 'bg-emerald-50',
                'border' => 'border-emerald-200',
                'icon' => '✓',
            ],
            \App\Models\LoyaltyCoupon::STATUS_USED => [
                'pill' => 'from-blue-500 to-blue-800 text-white',
                'text' => 'text-blue-700',
                'bg' => 'bg-blue-50',
                'border' => 'border-blue-200',
                'icon' => '✓',
            ],
            \App\Models\LoyaltyCoupon::STATUS_EXPIRED => [
                'pill' => 'from-amber-500 to-orange-700 text-white',
                'text' => 'text-amber-700',
                'bg' => 'bg-amber-50',
                'border' => 'border-amber-200',
                'icon' => '!',
            ],
            default => [
                'pill' => 'from-slate-500 to-slate-800 text-white',
                'text' => 'text-slate-700',
                'bg' => 'bg-slate-50',
                'border' => 'border-slate-200',
                'icon' => '×',
            ],
        };
    @endphp

    <style>
        @media print {
            body {
                background: #fff !important;
            }

            [data-sidebar],
            header,
            [data-coupon-actions] {
                display: none !important;
            }

            [data-content] {
                margin: 0 !important;
                min-height: auto !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                background: #fff !important;
            }
        }
    </style>

    <div class="mx-auto max-w-7xl space-y-5">
        <div data-coupon-actions class="flex flex-wrap items-center justify-between gap-3" data-tour="loyalty-coupon-actions">
            <a href="{{ route('customers.edit', $customer) }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm hover:bg-slate-50">Voltar ao cliente</a>

            <div class="flex flex-wrap gap-2">
                @if ($whatsappUrl)
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-emerald-800">Compartilhar via WhatsApp</a>
                @else
                    <span class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-bold text-amber-800">Cliente sem WhatsApp válido</span>
                @endif
                <button type="button" onclick="window.print()" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm hover:bg-slate-50">Imprimir cupom</button>
            </div>
        </div>

        @if (session('status'))
            <div data-coupon-actions class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @error('coupon')
            <div data-coupon-actions class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">
                {{ $message }}
            </div>
        @enderror

        <section class="overflow-hidden rounded-[2rem] border border-blue-100 bg-white shadow-xl shadow-slate-200/80" data-tour="loyalty-coupon-card">
            <div class="grid lg:grid-cols-[minmax(0,1fr)_390px]">
                <div class="relative min-h-[620px] overflow-hidden bg-slate-950" data-tour="loyalty-coupon-main">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_12%_12%,rgba(14,165,233,0.55),transparent_24%),linear-gradient(135deg,#031b4e,#063c8e_48%,#0b63ce)]"></div>
                    <div class="absolute -left-24 top-0 h-64 w-[640px] rounded-br-[80%] bg-white shadow-2xl shadow-blue-950/30"></div>
                    <div class="absolute left-0 top-0 h-48 w-64 bg-[radial-gradient(circle,rgba(255,255,255,0.2)_1px,transparent_2px)] [background-size:18px_18px] opacity-40"></div>
                    <div class="absolute right-0 top-0 h-full w-[58%] bg-[linear-gradient(114deg,transparent_0%,transparent_17%,rgba(255,255,255,0.92)_17%,rgba(255,255,255,0.92)_100%)]"></div>
                    <div class="absolute right-0 top-0 h-full w-[59%] bg-[radial-gradient(circle_at_70%_28%,rgba(14,165,233,0.22),transparent_34%),linear-gradient(135deg,rgba(255,255,255,0.24),rgba(255,255,255,0.86))]"></div>
                    <div class="absolute right-0 top-0 hidden h-full border-r border-dashed border-blue-200 lg:block"></div>
                    <div class="absolute bottom-0 right-0 h-28 w-[58%] bg-gradient-to-r from-blue-950/70 via-blue-700 to-cyan-500"></div>
                    <div class="absolute bottom-0 right-0 h-28 w-[58%] bg-[linear-gradient(90deg,transparent,rgba(255,255,255,0.14))]"></div>

                    <div class="relative z-10 flex min-h-[620px] flex-col justify-between p-6 sm:p-9">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="rounded-br-[4.5rem] rounded-tl-[1.5rem] bg-white px-10 py-7 shadow-xl shadow-blue-950/20">
                                <img src="{{ $location?->logoUrl() ?? asset('images/autoflow-logo.png') }}" alt="{{ $location?->name ?? 'AutoFlow' }}" class="h-24 w-64 object-contain">
                            </div>
                            <span class="mr-3 mt-4 inline-flex items-center gap-2 rounded-full bg-gradient-to-r {{ $statusTone['pill'] }} px-6 py-3 text-sm font-black uppercase tracking-[0.16em] shadow-lg shadow-slate-950/20">
                                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-white/20 text-lg">{{ $statusTone['icon'] }}</span>
                                {{ $coupon->statusLabel() }}
                            </span>
                        </div>

                        <div class="grid gap-10 lg:grid-cols-[0.72fr_minmax(380px,1fr)] lg:items-center">
                            <div class="text-white">
                                <div class="mb-9 flex items-center gap-4">
                                    <span class="flex h-20 w-20 items-center justify-center rounded-full border border-white/25 bg-white/10 text-4xl shadow-lg shadow-blue-950/20">%</span>
                                    <span class="h-px flex-1 border-t border-dotted border-cyan-300/70"></span>
                                </div>
                                <p class="text-sm font-black uppercase tracking-[0.28em] text-blue-100">Cupom de</p>
                                <h2 class="mt-3 text-5xl font-black uppercase leading-none tracking-wide text-white xl:text-6xl">Fidelidade</h2>
                                <div class="mt-8 h-1.5 w-20 rounded-full bg-cyan-300"></div>
                                <p class="mt-8 max-w-md text-base font-semibold leading-8 text-blue-50">
                                    Benefício exclusivo para o cliente <strong class="text-cyan-200">{{ $customer?->name }}</strong> utilizar na próxima visita à unidade <strong class="text-cyan-200">{{ $location?->name }}</strong>.
                                </p>
                            </div>

                            <div class="relative mx-auto w-full max-w-lg rounded-[2rem] bg-white p-5 shadow-2xl shadow-blue-950/25" data-tour="loyalty-coupon-code">
                                <span class="absolute -left-5 top-1/2 h-10 w-10 -translate-y-1/2 rounded-full bg-blue-900"></span>
                                <span class="absolute -right-5 top-1/2 h-10 w-10 -translate-y-1/2 rounded-full bg-white"></span>
                                <div class="rounded-[1.5rem] border border-dashed border-slate-300 px-6 py-8 text-center">
                                    <p class="text-xs font-black uppercase tracking-[0.28em] text-blue-700">Código do cupom</p>
                                    <div class="my-4 flex items-center justify-center gap-3 text-cyan-400">
                                        <span class="h-px w-20 bg-cyan-200"></span>
                                        <span>★ ★ ★</span>
                                        <span class="h-px w-20 bg-cyan-200"></span>
                                    </div>
                                    <p class="break-words text-4xl font-black uppercase leading-tight tracking-[0.12em] text-slate-950 xl:text-5xl">{{ $coupon->code }}</p>
                                    <div class="mt-5 flex items-center justify-center gap-3 text-blue-700">
                                        <span class="h-px w-20 bg-slate-300"></span>
                                        <span class="text-2xl">▣</span>
                                        <span class="h-px w-20 bg-slate-300"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 flex flex-wrap items-center justify-end gap-4 rounded-l-[3rem] bg-blue-950/30 px-5 py-4 text-white backdrop-blur-sm">
                            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-white/10 text-sm font-black uppercase tracking-[0.16em]">FID</div>
                            <div>
                                <p class="font-black text-cyan-200">Agradecemos sua preferência!</p>
                                <p class="text-sm font-semibold text-blue-50">Continue cuidando do seu carro com quem entende do assunto.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <aside class="border-t border-slate-200 bg-white p-6 lg:border-l lg:border-t-0 xl:p-8" data-tour="loyalty-coupon-details">
                    <dl class="space-y-5">
                        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:p-6">
                            <dt class="flex items-center gap-3 text-xs font-black uppercase tracking-[0.14em] text-blue-700">
                                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-700 text-xl text-white">●</span>
                                Cliente
                            </dt>
                            <dd class="mt-3 text-lg font-black text-slate-950">{{ $customer?->name }}</dd>
                            <dd class="mt-1 text-sm font-semibold text-slate-500">{{ $customer?->phone }}</dd>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:p-6">
                            <dt class="flex items-center gap-3 text-xs font-black uppercase tracking-[0.14em] text-blue-700">
                                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-700 text-xl text-white">⌖</span>
                                Unidade
                            </dt>
                            <dd class="mt-3 text-lg font-black text-slate-950">{{ $location?->name }}</dd>
                            <dd class="mt-1 text-sm font-semibold leading-6 text-slate-500">{{ $location?->fullAddress() }}</dd>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                <dt class="text-xs font-black uppercase tracking-[0.14em] text-blue-700">Emissão</dt>
                                <dd class="mt-2 font-black text-slate-950">{{ $coupon->earned_at?->format('d/m/Y') ?? '-' }}</dd>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                <dt class="text-xs font-black uppercase tracking-[0.14em] text-blue-700">Validade</dt>
                                <dd class="mt-2 font-black text-slate-950">{{ $coupon->expires_at?->format('d/m/Y') ?? '-' }}</dd>
                            </div>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:p-6">
                            <dt class="flex items-center gap-3 text-xs font-black uppercase tracking-[0.14em] text-blue-700">
                                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-50 text-xl text-blue-700">▣</span>
                                Lavagem que gerou
                            </dt>
                            <dd class="mt-3 text-lg font-black text-slate-950">{{ $coupon->sourceWashOrder?->code ?? '-' }}</dd>
                            @if ($coupon->sourceWashOrder?->entered_at)
                                <dd class="mt-1 text-sm font-semibold text-slate-500">{{ $coupon->sourceWashOrder->entered_at->format('d/m/Y H:i') }}</dd>
                            @endif
                        </div>
                        @if ($coupon->usedWashOrder)
                            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                <dt class="text-xs font-black uppercase tracking-[0.14em] text-blue-700">Usado na lavagem</dt>
                                <dd class="mt-2 text-lg font-black text-slate-950">{{ $coupon->usedWashOrder->code }}</dd>
                                <dd class="mt-1 text-sm font-semibold text-slate-500">
                                    {{ $coupon->used_at?->format('d/m/Y H:i') ?? '-' }}
                                    @if ($coupon->usedByUser)
                                        · {{ $coupon->usedByUser->name }}
                                    @endif
                                </dd>
                            </div>
                        @endif
                    </dl>
                </aside>
            </div>

            <div class="border-t border-dashed border-slate-200 bg-white px-6 py-5 sm:px-10" data-tour="loyalty-coupon-status">
                <div class="grid gap-5 lg:grid-cols-[1fr_360px] lg:items-center">
                    <div class="flex items-center gap-5 rounded-2xl border border-slate-100 bg-slate-50 px-5 py-4">
                        <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full border border-blue-100 bg-white text-3xl text-blue-700">◇</span>
                        <p class="text-sm font-semibold leading-6 text-slate-600">Cupom pessoal e vinculado ao cliente informado. A validade e o status devem ser conferidos antes de aplicar o benefício no atendimento.</p>
                    </div>
                    <div class="flex items-center justify-center gap-4 rounded-2xl border {{ $statusTone['border'] }} {{ $statusTone['bg'] }} px-5 py-4 text-center">
                        <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-white text-3xl {{ $statusTone['text'] }} shadow-sm">{{ $statusTone['icon'] }}</span>
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-blue-700">Status do cupom</p>
                            <p class="mt-1 text-4xl font-black uppercase {{ $statusTone['text'] }}">{{ $coupon->statusLabel() }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section data-coupon-actions class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" data-tour="loyalty-coupon-message">
            <p class="text-sm font-black text-slate-950">Mensagem que será enviada</p>
            <textarea readonly rows="6" class="mt-3 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm leading-6 text-slate-700">{{ $coupon->whatsappShareMessage() }}</textarea>
        </section>

        <section data-coupon-actions class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" data-tour="loyalty-coupon-internal-control">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-black text-slate-950">Controle interno</p>
                    <p class="mt-1 text-sm text-slate-500">Cancele apenas quando o cupom não deve mais ser aceito no atendimento.</p>
                </div>
                @if ($coupon->status === \App\Models\LoyaltyCoupon::STATUS_ACTIVE)
                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">Pode cancelar</span>
                @else
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">Cancelamento indisponivel</span>
                @endif
            </div>

            @if ($coupon->status === \App\Models\LoyaltyCoupon::STATUS_ACTIVE)
                <form method="POST" action="{{ route('loyalty-coupons.cancel', $coupon) }}" class="mt-4 grid gap-3 md:grid-cols-[1fr_auto] md:items-end">
                    @csrf
                    @method('PATCH')
                    <label>
                        <span class="mb-1 block text-xs font-bold text-slate-500">Motivo opcional</span>
                        <input name="reason" value="{{ old('reason') }}" maxlength="255" placeholder="Ex.: cupom emitido por engano" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                        @error('reason') <span class="mt-1 block text-xs font-bold text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <button class="rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-black text-red-700 hover:bg-red-100">
                        Cancelar cupom
                    </button>
                </form>
            @else
                <p class="mt-4 rounded-2xl bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-600">Este cupom não está ativo, por isso não pode ser cancelado manualmente.</p>
                @if (($coupon->metadata['canceled_reason'] ?? null) || ($coupon->metadata['canceled_at'] ?? null))
                    <div class="mt-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-600">
                        @if ($coupon->metadata['canceled_at'] ?? null)
                            <p><strong>Cancelado em:</strong> {{ \Illuminate\Support\Carbon::parse($coupon->metadata['canceled_at'])->format('d/m/Y H:i') }}</p>
                        @endif
                        @if ($coupon->metadata['canceled_reason'] ?? null)
                            <p class="mt-1"><strong>Motivo:</strong> {{ $coupon->metadata['canceled_reason'] }}</p>
                        @endif
                    </div>
                @endif
            @endif
        </section>
    </div>

    @php
        $loyaltyCouponTour = [
            'key' => 'loyalty-coupons.show.v1',
            'title' => 'Cupom de fidelidade',
            'steps' => [
                [
                    'target' => '[data-tour="loyalty-coupon-actions"]',
                    'title' => 'Ações rápidas',
                    'body' => 'Use estes botões para voltar ao cliente, compartilhar o cupom pelo WhatsApp ou imprimir.',
                ],
                [
                    'target' => '[data-tour="loyalty-coupon-card"]',
                    'title' => 'Cupom personalizado',
                    'body' => 'Este é o cupom em formato apresentável, com benefício, cliente, unidade, validade e status.',
                ],
                [
                    'target' => '[data-tour="loyalty-coupon-main"]',
                    'title' => 'Benefício',
                    'body' => 'Confira qual recompensa foi gerada antes de enviar ou aceitar o cupom no atendimento.',
                ],
                [
                    'target' => '[data-tour="loyalty-coupon-code"]',
                    'title' => 'Código do cupom',
                    'body' => 'O código identifica o benefício e deve ser conferido ao aplicar o cupom em uma lavagem.',
                ],
                [
                    'target' => '[data-tour="loyalty-coupon-details"]',
                    'title' => 'Dados vinculados',
                    'body' => 'Aqui ficam cliente, unidade, emissão, validade e a lavagem que gerou ou utilizou o cupom.',
                ],
                [
                    'target' => '[data-tour="loyalty-coupon-status"]',
                    'title' => 'Status do cupom',
                    'body' => 'Confira se o cupom está ativo, usado, expirado ou cancelado antes de aplicar o benefício.',
                ],
                [
                    'target' => '[data-tour="loyalty-coupon-message"]',
                    'title' => 'Mensagem de envio',
                    'body' => 'Esta é a mensagem pronta para compartilhar com o cliente, mantendo benefício e validade claros.',
                ],
                [
                    'target' => '[data-tour="loyalty-coupon-internal-control"]',
                    'title' => 'Controle interno',
                    'body' => 'Use esta área para cancelar um cupom ativo quando ele não deve mais ser aceito.',
                ],
            ],
        ];
    @endphp

    <script type="application/json" data-onboarding-tour>
        {!! json_encode($loyaltyCouponTour, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
</x-app.layout>
