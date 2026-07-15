<x-app.layout heading="Cupom {{ $coupon->code }}" title="Cupom {{ $coupon->code }} · AutoFlow">
    @php
        $location = $coupon->washLocation;
        $customer = $coupon->customer;
        $benefit = $coupon->benefitLabel();
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

    <div class="mx-auto max-w-5xl space-y-5">
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
            <div class="grid gap-0 lg:grid-cols-[1fr_320px]">
                <div class="relative overflow-hidden bg-gradient-to-br from-blue-700 via-cyan-600 to-emerald-500 p-8 text-white sm:p-10" data-tour="loyalty-coupon-main">
                    <div class="absolute -right-16 -top-16 h-48 w-48 rounded-full bg-white/15"></div>
                    <div class="absolute -bottom-20 -left-16 h-56 w-56 rounded-full bg-white/10"></div>

                    <div class="relative">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div class="rounded-2xl bg-white/95 p-3 shadow-lg shadow-blue-950/20">
                                <img src="{{ $location?->logoUrl() ?? asset('images/autoflow-logo.png') }}" alt="{{ $location?->name ?? 'AutoFlow' }}" class="h-16 w-32 object-contain">
                            </div>
                            <span class="rounded-full bg-white/20 px-4 py-2 text-sm font-black uppercase tracking-[0.16em] ring-1 ring-white/30">{{ $coupon->statusLabel() }}</span>
                        </div>

                        <p class="mt-10 text-sm font-black uppercase tracking-[0.22em] text-blue-50">Cupom de fidelidade</p>
                        <h2 class="mt-3 text-4xl font-black tracking-tight sm:text-5xl">{{ $benefit }}</h2>
                        <p class="mt-4 max-w-2xl text-base font-semibold leading-7 text-blue-50">Benefício exclusivo para {{ $customer?->name }} utilizar na próxima visita à unidade {{ $location?->name }}.</p>

                        <div class="mt-8 inline-flex rounded-2xl bg-white px-5 py-4 shadow-lg shadow-blue-950/20" data-tour="loyalty-coupon-code">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.22em] text-blue-700">Código</p>
                                <p class="mt-1 text-3xl font-black tracking-widest text-slate-950">{{ $coupon->code }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <aside class="border-t border-slate-200 bg-slate-50 p-6 lg:border-l lg:border-t-0" data-tour="loyalty-coupon-details">
                    <dl class="space-y-4">
                        <div class="rounded-2xl bg-white p-4 shadow-sm">
                            <dt class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Cliente</dt>
                            <dd class="mt-1 font-black text-slate-950">{{ $customer?->name }}</dd>
                            <dd class="mt-1 text-sm font-semibold text-slate-500">{{ $customer?->phone }}</dd>
                        </div>
                        <div class="rounded-2xl bg-white p-4 shadow-sm">
                            <dt class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Unidade</dt>
                            <dd class="mt-1 font-black text-slate-950">{{ $location?->name }}</dd>
                            <dd class="mt-1 text-sm font-semibold text-slate-500">{{ $location?->fullAddress() }}</dd>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-2xl bg-white p-4 shadow-sm">
                                <dt class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Emissão</dt>
                                <dd class="mt-1 font-black text-slate-950">{{ $coupon->earned_at?->format('d/m/Y') ?? '-' }}</dd>
                            </div>
                            <div class="rounded-2xl bg-white p-4 shadow-sm">
                                <dt class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Validade</dt>
                                <dd class="mt-1 font-black text-slate-950">{{ $coupon->expires_at?->format('d/m/Y') ?? '-' }}</dd>
                            </div>
                        </div>
                        <div class="rounded-2xl bg-white p-4 shadow-sm">
                            <dt class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Lavagem que gerou</dt>
                            <dd class="mt-1 font-black text-slate-950">{{ $coupon->sourceWashOrder?->code ?? '-' }}</dd>
                            @if ($coupon->sourceWashOrder?->entered_at)
                                <dd class="mt-1 text-sm font-semibold text-slate-500">{{ $coupon->sourceWashOrder->entered_at->format('d/m/Y H:i') }}</dd>
                            @endif
                        </div>
                        @if ($coupon->usedWashOrder)
                            <div class="rounded-2xl bg-white p-4 shadow-sm">
                                <dt class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Usado na lavagem</dt>
                                <dd class="mt-1 font-black text-slate-950">{{ $coupon->usedWashOrder->code }}</dd>
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

            <div class="border-t border-dashed border-slate-200 bg-white px-6 py-5 sm:px-10">
                <div class="grid gap-4 lg:grid-cols-[1fr_auto] lg:items-center">
                    <p class="text-sm font-semibold leading-6 text-slate-500">Cupom pessoal e vinculado ao cliente informado. A validade e o status devem ser conferidos antes de aplicar o benefício no atendimento.</p>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-right">
                        <p class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Status</p>
                        <p class="mt-1 text-lg font-black text-slate-950">{{ $coupon->statusLabel() }}</p>
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
