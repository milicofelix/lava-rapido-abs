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
        .loyalty-ticket {
            -webkit-mask-image:
                radial-gradient(circle 24px at left center, transparent 98%, #000 100%),
                radial-gradient(circle 24px at right center, transparent 98%, #000 100%);
            -webkit-mask-composite: source-in;
            mask-image:
                radial-gradient(circle 24px at left center, transparent 98%, #000 100%),
                radial-gradient(circle 24px at right center, transparent 98%, #000 100%);
            mask-composite: intersect;
        }

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
                <button type="button" data-coupon-download class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-800">Baixar imagem</button>
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

        <section
            class="overflow-hidden rounded-[2rem] border border-blue-100 bg-white shadow-xl shadow-slate-200/80"
            data-coupon-card
            data-coupon-logo="{{ $location?->logoUrl() ?? asset('images/autoflow-logo.png') }}"
            data-coupon-code="{{ $coupon->code }}"
            data-coupon-benefit="{{ $benefit }}"
            data-coupon-status="{{ $coupon->statusLabel() }}"
            data-coupon-customer="{{ $customer?->name }}"
            data-coupon-phone="{{ $customer?->phone }}"
            data-coupon-location="{{ $location?->name }}"
            data-coupon-address="{{ $location?->fullAddress() }}"
            data-coupon-earned="{{ $coupon->earned_at?->format('d/m/Y') ?? '-' }}"
            data-coupon-expires="{{ $coupon->expires_at?->format('d/m/Y') ?? '-' }}"
            data-coupon-source="{{ $coupon->sourceWashOrder?->code ?? '-' }}"
            data-coupon-source-date="{{ $coupon->sourceWashOrder?->entered_at?->format('d/m/Y H:i') ?? '' }}"
            data-tour="loyalty-coupon-card"
        >
            <div class="grid lg:grid-cols-[minmax(0,1fr)_390px]">
                <div class="relative min-h-[620px] overflow-hidden bg-slate-950" data-tour="loyalty-coupon-main">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_12%_12%,rgba(14,165,233,0.55),transparent_24%),linear-gradient(135deg,#031b4e,#063c8e_48%,#0b63ce)]"></div>
                    <div class="absolute inset-0 opacity-35 bg-[radial-gradient(circle_at_10%_8%,rgba(255,255,255,0.45)_0_5px,transparent_6px),radial-gradient(circle_at_18%_4%,rgba(255,255,255,0.28)_0_8px,transparent_9px),radial-gradient(circle_at_22%_15%,rgba(255,255,255,0.22)_0_4px,transparent_5px),radial-gradient(circle_at_6%_20%,rgba(255,255,255,0.18)_0_7px,transparent_8px)]"></div>
                    <div class="absolute -left-24 top-0 h-64 w-[640px] rounded-br-[80%] bg-white shadow-2xl shadow-blue-950/30"></div>
                    <div class="absolute left-0 top-0 h-48 w-64 bg-[radial-gradient(circle,rgba(255,255,255,0.2)_1px,transparent_2px)] [background-size:18px_18px] opacity-40"></div>
                    <div class="absolute right-0 top-0 h-full w-[54%] bg-[linear-gradient(114deg,transparent_0%,transparent_26%,rgba(255,255,255,0.92)_26%,rgba(255,255,255,0.92)_100%)]"></div>
                    <div class="absolute right-0 top-0 h-full w-[55%] bg-[radial-gradient(circle_at_72%_28%,rgba(14,165,233,0.24),transparent_32%),radial-gradient(circle_at_55%_58%,rgba(2,132,199,0.16),transparent_30%),linear-gradient(135deg,rgba(255,255,255,0.18),rgba(255,255,255,0.86))]"></div>
                    <div class="absolute right-0 top-0 h-full w-[55%] opacity-45 bg-[linear-gradient(115deg,transparent_0%,transparent_28%,rgba(148,163,184,0.16)_28%,rgba(148,163,184,0.16)_29%,transparent_29%),radial-gradient(circle_at_46%_22%,rgba(255,255,255,0.55)_0_2px,transparent_3px),radial-gradient(circle_at_62%_38%,rgba(255,255,255,0.55)_0_2px,transparent_3px)]"></div>
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
                                    <span class="flex h-20 w-20 items-center justify-center rounded-full border border-white/25 bg-white/10 text-white shadow-lg shadow-blue-950/20">
                                        <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M20.5 13.5 13.5 20.5a2.1 2.1 0 0 1-3 0L3 13V4h9l8.5 8.5a2.1 2.1 0 0 1 0 3Z" />
                                            <path d="M7.5 7.5h.01" />
                                        </svg>
                                    </span>
                                    <span class="h-px flex-1 border-t border-dotted border-cyan-300/70"></span>
                                </div>
                                <p class="text-sm font-black uppercase tracking-[0.28em] text-blue-100">Cupom de</p>
                                <h2 class="mt-3 text-5xl font-black uppercase leading-none tracking-wide text-white drop-shadow-[0_3px_8px_rgba(2,6,23,0.45)] xl:text-6xl">Fidelidade</h2>
                                <div class="mt-8 h-1.5 w-20 rounded-full bg-cyan-300"></div>
                                <p class="mt-8 max-w-md text-base font-semibold leading-8 text-blue-50">
                                    Benefício exclusivo para o cliente <strong class="text-cyan-200">{{ $customer?->name }}</strong> utilizar na próxima visita à unidade <strong class="text-cyan-200">{{ $location?->name }}</strong>.
                                </p>
                            </div>

                            <div class="loyalty-ticket relative mx-auto w-full max-w-lg bg-white p-5 shadow-2xl shadow-blue-950/25" data-tour="loyalty-coupon-code">
                                <div class="rounded-[1.5rem] border-2 border-dashed border-slate-300 px-6 py-8 text-center">
                                    <p class="text-xs font-black uppercase tracking-[0.28em] text-blue-700">Código do cupom</p>
                                    <div class="my-4 flex items-center justify-center gap-3 text-cyan-400">
                                        <span class="h-px w-20 bg-cyan-200"></span>
                                        <span>★ ★ ★</span>
                                        <span class="h-px w-20 bg-cyan-200"></span>
                                    </div>
                                    <p class="break-words text-4xl font-black uppercase leading-tight tracking-[0.12em] text-slate-950 xl:text-5xl">{{ $coupon->code }}</p>
                                    <div class="mt-5 flex items-center justify-center gap-3 text-blue-700">
                                        <span class="h-px w-20 bg-slate-300"></span>
                                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M6 17h12l1-5H5l1 5Z" />
                                            <path d="M7 17a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z" />
                                            <path d="M17 17a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z" />
                                            <path d="M7 12l2-5h6l2 5" />
                                            <path d="M9 7h6" />
                                        </svg>
                                        <span class="h-px w-20 bg-slate-300"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 flex flex-wrap items-center justify-end gap-4 rounded-l-[3rem] bg-blue-950/30 px-5 py-4 text-white backdrop-blur-sm">
                            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-white/10 text-white">
                                <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M20 12v8a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-8" />
                                    <path d="M2 8h20v4H2z" />
                                    <path d="M12 21V8" />
                                    <path d="M12 8H7.5a2.5 2.5 0 1 1 2-4L12 8Z" />
                                    <path d="M12 8h4.5a2.5 2.5 0 1 0-2-4L12 8Z" />
                                </svg>
                            </div>
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
                                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-700 text-white">
                                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M20 21a8 8 0 0 0-16 0" />
                                        <circle cx="12" cy="8" r="4" />
                                    </svg>
                                </span>
                                Cliente
                            </dt>
                            <dd class="mt-3 text-lg font-black text-slate-950">{{ $customer?->name }}</dd>
                            <dd class="mt-1 text-sm font-semibold text-slate-500">{{ $customer?->phone }}</dd>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:p-6">
                            <dt class="flex items-center gap-3 text-xs font-black uppercase tracking-[0.14em] text-blue-700">
                                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-700 text-white">
                                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M12 21s7-5.2 7-11a7 7 0 1 0-14 0c0 5.8 7 11 7 11Z" />
                                        <circle cx="12" cy="10" r="2.5" />
                                    </svg>
                                </span>
                                Unidade
                            </dt>
                            <dd class="mt-3 text-lg font-black text-slate-950">{{ $location?->name }}</dd>
                            <dd class="mt-1 text-sm font-semibold leading-6 text-slate-500">{{ $location?->fullAddress() }}</dd>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                <dt class="flex items-center gap-2 text-xs font-black uppercase tracking-[0.14em] text-blue-700">
                                    <svg class="h-6 w-6 rounded-full bg-blue-50 p-1 text-blue-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M8 2v4" />
                                        <path d="M16 2v4" />
                                        <path d="M3 10h18" />
                                        <rect x="3" y="4" width="18" height="18" rx="2" />
                                    </svg>
                                    Emissão
                                </dt>
                                <dd class="mt-2 font-black text-slate-950">{{ $coupon->earned_at?->format('d/m/Y') ?? '-' }}</dd>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                <dt class="flex items-center gap-2 text-xs font-black uppercase tracking-[0.14em] text-blue-700">
                                    <svg class="h-6 w-6 rounded-full bg-blue-50 p-1 text-blue-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M8 2v4" />
                                        <path d="M16 2v4" />
                                        <path d="M3 10h18" />
                                        <rect x="3" y="4" width="18" height="18" rx="2" />
                                        <path d="m9 16 2 2 4-4" />
                                    </svg>
                                    Validade
                                </dt>
                                <dd class="mt-2 font-black text-slate-950">{{ $coupon->expires_at?->format('d/m/Y') ?? '-' }}</dd>
                            </div>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:p-6">
                            <dt class="flex items-center gap-3 text-xs font-black uppercase tracking-[0.14em] text-blue-700">
                                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-50 text-blue-700">
                                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M6 17h12l1-5H5l1 5Z" />
                                        <path d="M7 17a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z" />
                                        <path d="M17 17a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z" />
                                        <path d="M7 12l2-5h6l2 5" />
                                    </svg>
                                </span>
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
                        <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full border border-blue-100 bg-white text-blue-700">
                            <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" />
                                <path d="m9 12 2 2 4-4" />
                            </svg>
                        </span>
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

    <script>
        (() => {
            const button = document.querySelector('[data-coupon-download]');
            const card = document.querySelector('[data-coupon-card]');

            if (!button || !card) {
                return;
            }

            const read = (key, fallback = '-') => card.dataset[key] || fallback;
            const sanitizeFileName = (value) => value
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-zA-Z0-9_-]+/g, '-')
                .replace(/^-+|-+$/g, '')
                .toLowerCase();

            const loadImage = (src) => new Promise((resolve) => {
                const image = new Image();
                image.onload = () => resolve(image);
                image.onerror = () => resolve(null);
                image.src = src;
            });

            const canvasToBlob = (canvas) => new Promise((resolve, reject) => {
                canvas.toBlob((blob) => {
                    if (blob) {
                        resolve(blob);
                    } else {
                        reject(new Error('Imagem vazia.'));
                    }
                }, 'image/png', 0.95);
            });

            const roundRect = (context, x, y, width, height, radius) => {
                const r = Math.min(radius, width / 2, height / 2);
                context.beginPath();
                context.moveTo(x + r, y);
                context.arcTo(x + width, y, x + width, y + height, r);
                context.arcTo(x + width, y + height, x, y + height, r);
                context.arcTo(x, y + height, x, y, r);
                context.arcTo(x, y, x + width, y, r);
                context.closePath();
            };

            const fillRoundRect = (context, x, y, width, height, radius, fill) => {
                context.fillStyle = fill;
                roundRect(context, x, y, width, height, radius);
                context.fill();
            };

            const strokeRoundRect = (context, x, y, width, height, radius, stroke, lineWidth = 2, dash = []) => {
                context.strokeStyle = stroke;
                context.lineWidth = lineWidth;
                context.setLineDash(dash);
                roundRect(context, x, y, width, height, radius);
                context.stroke();
                context.setLineDash([]);
            };

            const drawText = (context, text, x, y, options = {}) => {
                context.fillStyle = options.color || '#020617';
                context.font = `${options.weight || 700} ${options.size || 28}px ${options.family || 'Arial, sans-serif'}`;
                context.textAlign = options.align || 'left';
                context.textBaseline = options.baseline || 'top';
                context.fillText(text, x, y);
            };

            const wrapText = (context, text, x, y, maxWidth, lineHeight, options = {}) => {
                const words = String(text || '-').split(' ');
                let line = '';
                let currentY = y;
                let lines = 0;
                const maxLines = options.maxLines || 999;

                words.forEach((word, index) => {
                    if (lines >= maxLines) {
                        return;
                    }

                    const testLine = line ? `${line} ${word}` : word;

                    if (context.measureText(testLine).width > maxWidth && line) {
                        const hasMore = index < words.length;
                        drawText(context, hasMore && lines + 1 === maxLines ? `${line.replace(/[.,;:!?-]+$/, '')}...` : line, x, currentY, options);
                        line = word;
                        currentY += lineHeight;
                        lines += 1;
                    } else {
                        line = testLine;
                    }

                    if (index === words.length - 1 && lines < maxLines) {
                        drawText(context, line, x, currentY, options);
                        lines += 1;
                    }
                });

                return currentY + lineHeight;
            };

            const drawIcon = (context, type, x, y, size, color = '#ffffff') => {
                context.save();
                context.strokeStyle = color;
                context.fillStyle = color;
                context.lineWidth = Math.max(3, size / 13);
                context.lineCap = 'round';
                context.lineJoin = 'round';

                if (type === 'user') {
                    context.beginPath();
                    context.arc(x, y - size * 0.18, size * 0.17, 0, Math.PI * 2);
                    context.stroke();
                    context.beginPath();
                    context.arc(x, y + size * 0.25, size * 0.28, Math.PI, 0);
                    context.stroke();
                } else if (type === 'pin') {
                    context.beginPath();
                    context.arc(x, y - size * 0.08, size * 0.23, 0, Math.PI * 2);
                    context.stroke();
                    context.beginPath();
                    context.moveTo(x, y + size * 0.48);
                    context.lineTo(x - size * 0.23, y + size * 0.12);
                    context.lineTo(x + size * 0.23, y + size * 0.12);
                    context.closePath();
                    context.stroke();
                    context.beginPath();
                    context.arc(x, y - size * 0.08, size * 0.07, 0, Math.PI * 2);
                    context.fill();
                } else if (type === 'calendar' || type === 'calendar-check') {
                    context.strokeRect(x - size * 0.34, y - size * 0.28, size * 0.68, size * 0.58);
                    context.beginPath();
                    context.moveTo(x - size * 0.2, y - size * 0.42);
                    context.lineTo(x - size * 0.2, y - size * 0.18);
                    context.moveTo(x + size * 0.2, y - size * 0.42);
                    context.lineTo(x + size * 0.2, y - size * 0.18);
                    context.moveTo(x - size * 0.34, y - size * 0.08);
                    context.lineTo(x + size * 0.34, y - size * 0.08);
                    context.stroke();
                    if (type === 'calendar-check') {
                        context.beginPath();
                        context.moveTo(x - size * 0.14, y + size * 0.12);
                        context.lineTo(x - size * 0.02, y + size * 0.24);
                        context.lineTo(x + size * 0.2, y);
                        context.stroke();
                    }
                } else if (type === 'gift') {
                    context.strokeRect(x - size * 0.35, y - size * 0.12, size * 0.7, size * 0.42);
                    context.strokeRect(x - size * 0.4, y - size * 0.28, size * 0.8, size * 0.18);
                    context.beginPath();
                    context.moveTo(x, y - size * 0.28);
                    context.lineTo(x, y + size * 0.3);
                    context.moveTo(x - size * 0.16, y - size * 0.33);
                    context.quadraticCurveTo(x - size * 0.36, y - size * 0.52, x, y - size * 0.28);
                    context.moveTo(x + size * 0.16, y - size * 0.33);
                    context.quadraticCurveTo(x + size * 0.36, y - size * 0.52, x, y - size * 0.28);
                    context.stroke();
                } else if (type === 'shield') {
                    context.beginPath();
                    context.moveTo(x, y - size * 0.42);
                    context.lineTo(x + size * 0.34, y - size * 0.26);
                    context.lineTo(x + size * 0.28, y + size * 0.16);
                    context.quadraticCurveTo(x, y + size * 0.5, x - size * 0.28, y + size * 0.16);
                    context.lineTo(x - size * 0.34, y - size * 0.26);
                    context.closePath();
                    context.stroke();
                    context.beginPath();
                    context.moveTo(x - size * 0.15, y);
                    context.lineTo(x - size * 0.03, y + size * 0.13);
                    context.lineTo(x + size * 0.18, y - size * 0.12);
                    context.stroke();
                } else {
                    context.beginPath();
                    context.moveTo(x - size * 0.42, y + size * 0.12);
                    context.lineTo(x - size * 0.22, y - size * 0.18);
                    context.lineTo(x + size * 0.2, y - size * 0.18);
                    context.lineTo(x + size * 0.42, y + size * 0.12);
                    context.closePath();
                    context.stroke();
                    context.beginPath();
                    context.arc(x - size * 0.24, y + size * 0.22, size * 0.1, 0, Math.PI * 2);
                    context.arc(x + size * 0.24, y + size * 0.22, size * 0.1, 0, Math.PI * 2);
                    context.stroke();
                }

                context.restore();
            };

            const drawInfoCard = (context, icon, label, title, detail, x, y, width, height, options = {}) => {
                fillRoundRect(context, x, y, width, height, 28, '#ffffff');
                strokeRoundRect(context, x, y, width, height, 28, '#dbe5f1');
                fillRoundRect(context, x + 28, y + 30, 64, 64, 32, '#0b63ce');
                drawIcon(context, icon, x + 60, y + 62, 46);
                drawText(context, label.toUpperCase(), x + 120, y + 28, { color: '#0b63ce', size: 22, weight: 900 });
                const displayTitle = options.breakHyphen ? String(title || '-').replace(/-/g, '- ') : title;
                const titleEnd = wrapText(context, displayTitle, x + 120, y + 64, width - 150, options.titleLineHeight || 28, {
                    color: '#020617',
                    size: options.titleSize || 24,
                    weight: 900,
                    maxLines: options.titleLines || 2,
                });

                if (detail) {
                    wrapText(context, detail, x + 120, Math.max(y + 106, titleEnd + 6), width - 150, options.detailLineHeight || 25, {
                        color: '#475569',
                        size: options.detailSize || 21,
                        weight: 600,
                        maxLines: options.detailLines || 2,
                    });
                }
            };

            const splitCouponCode = (context, code, maxWidth, fontSize) => {
                const normalized = String(code || '-').toUpperCase();
                const tokens = normalized.split('-').filter(Boolean).map((part, index, parts) => index < parts.length - 1 ? `${part}-` : part);
                const lines = [];
                let current = '';

                tokens.forEach((token) => {
                    const next = current ? `${current}${token}` : token;

                    if (context.measureText(next).width <= maxWidth || !current) {
                        current = next;
                    } else {
                        lines.push(current);
                        current = token;
                    }
                });

                if (current) {
                    lines.push(current);
                }

                if (lines.length > 3) {
                    return normalized.match(/.{1,11}/g) || [normalized];
                }

                return lines;
            };

            const drawTicket = (context, x, y, width, height, code) => {
                fillRoundRect(context, x, y, width, height, 34, '#ffffff');

                context.save();
                context.beginPath();
                context.fillStyle = '#0b3b88';
                context.arc(x, y + height / 2, 34, 0, Math.PI * 2);
                context.fill();

                context.beginPath();
                context.fillStyle = '#edf4fb';
                context.arc(x + width, y + height / 2, 34, 0, Math.PI * 2);
                context.fill();
                context.restore();

                strokeRoundRect(context, x + 34, y + 32, width - 68, height - 64, 24, '#bfcede', 2, [9, 8]);
                drawText(context, 'CÓDIGO DO CUPOM', x + width / 2, y + 62, { align: 'center', color: '#0b63ce', size: 23, weight: 900 });
                context.strokeStyle = '#5eead4';
                context.lineWidth = 3;
                context.beginPath();
                context.moveTo(x + 170, y + 120);
                context.lineTo(x + 335, y + 120);
                context.moveTo(x + width - 335, y + 120);
                context.lineTo(x + width - 170, y + 120);
                context.stroke();
                drawText(context, '★ ★ ★', x + width / 2, y + 105, { align: 'center', color: '#22d3ee', size: 28, weight: 900 });

                let fontSize = 58;
                let lines = [];
                do {
                    context.font = `900 ${fontSize}px Arial, sans-serif`;
                    lines = splitCouponCode(context, code, width - 118, fontSize);
                    fontSize -= 2;
                } while ((lines.length > 3 || lines.some((line) => context.measureText(line).width > width - 118)) && fontSize >= 34);

                const effectiveFontSize = fontSize + 2;
                const lineHeight = Math.max(52, effectiveFontSize + 6);
                const startY = y + 176 - ((Math.min(lines.length, 3) - 1) * lineHeight) / 2;
                lines.slice(0, 3).forEach((line, index) => {
                    drawText(context, line, x + width / 2, startY + index * lineHeight, {
                        align: 'center',
                        color: '#020617',
                        size: effectiveFontSize,
                        weight: 900,
                    });
                });

                context.strokeStyle = '#cbd5e1';
                context.lineWidth = 2;
                context.beginPath();
                context.moveTo(x + 165, y + height - 48);
                context.lineTo(x + 350, y + height - 48);
                context.moveTo(x + width - 350, y + height - 48);
                context.lineTo(x + width - 165, y + height - 48);
                context.stroke();
                drawIcon(context, 'car', x + width / 2, y + height - 38, 34, '#0b63ce');
            };

            button.addEventListener('click', async () => {
                const originalText = button.textContent;
                button.disabled = true;
                button.textContent = 'Gerando imagem...';

                try {
                    const canvas = document.createElement('canvas');
                    canvas.width = 1600;
                    canvas.height = 1000;
                    const context = canvas.getContext('2d');
                    const gradient = context.createLinearGradient(0, 0, 1600, 1000);
                    gradient.addColorStop(0, '#f8fbff');
                    gradient.addColorStop(1, '#e6f4ff');
                    context.fillStyle = gradient;
                    context.fillRect(0, 0, 1600, 1000);

                    fillRoundRect(context, 18, 18, 1564, 964, 34, '#ffffff');
                    strokeRoundRect(context, 18, 18, 1564, 964, 34, '#cfe0f3', 2);

                    const leftGradient = context.createLinearGradient(18, 18, 1100, 820);
                    leftGradient.addColorStop(0, '#031b4e');
                    leftGradient.addColorStop(0.52, '#063c8e');
                    leftGradient.addColorStop(1, '#0b63ce');
                    context.fillStyle = leftGradient;
                    context.fillRect(18, 18, 1090, 785);

                    context.globalAlpha = 0.18;
                    for (let i = 0; i < 58; i += 1) {
                        const x = 44 + ((i * 41) % 420);
                        const y = 38 + ((i * 29) % 196);
                        context.beginPath();
                        context.arc(x, y, 4 + (i % 8), 0, Math.PI * 2);
                        context.strokeStyle = '#ffffff';
                        context.lineWidth = 2;
                        context.stroke();
                    }
                    context.globalAlpha = 1;

                    context.fillStyle = 'rgba(255,255,255,0.92)';
                    context.beginPath();
                    context.moveTo(620, 18);
                    context.bezierCurveTo(780, 180, 690, 360, 1108, 803);
                    context.lineTo(1108, 18);
                    context.closePath();
                    context.fill();

                    context.globalAlpha = 0.34;
                    context.fillStyle = '#b9d8ed';
                    for (let i = 0; i < 18; i += 1) {
                        context.beginPath();
                        context.arc(720 + i * 34, 115 + i * 12, 55 + i * 3, 0, Math.PI * 2);
                        context.stroke();
                    }
                    context.globalAlpha = 1;

                    context.fillStyle = '#ffffff';
                    context.beginPath();
                    context.moveTo(18, 18);
                    context.lineTo(676, 18);
                    context.bezierCurveTo(605, 82, 575, 176, 530, 258);
                    context.lineTo(18, 258);
                    context.closePath();
                    context.fill();

                    const logo = await loadImage(read('couponLogo', ''));
                    if (logo) {
                        context.drawImage(logo, 260, 74, 260, 130);
                    } else {
                        drawText(context, 'AutoFlow', 320, 110, { color: '#0b63ce', size: 54, weight: 900 });
                    }

                    const status = read('couponStatus', 'Ativo').toUpperCase();
                    fillRoundRect(context, 880, 60, 190, 64, 32, '#10b981');
                    drawText(context, `✓ ${status}`, 975, 77, { align: 'center', color: '#ffffff', size: 28, weight: 900 });

                    drawText(context, 'CUPOM DE', 78, 410, { color: '#ffffff', size: 28, weight: 900 });
                    drawText(context, 'FIDELIDADE', 78, 448, { color: '#ffffff', size: 62, weight: 900 });
                    fillRoundRect(context, 78, 548, 78, 7, 4, '#2dd4bf');
                    wrapText(context, `Benefício exclusivo: ${read('couponBenefit')}`, 78, 590, 390, 36, { color: '#dbeafe', size: 26, weight: 700 });
                    fillRoundRect(context, 492, 716, 604, 88, 44, 'rgba(15, 74, 175, 0.78)');
                    drawIcon(context, 'gift', 552, 758, 42, '#ffffff');
                    drawText(context, 'Agradecemos sua preferência!', 590, 730, { color: '#67e8f9', size: 24, weight: 900 });
                    drawText(context, 'Continue cuidando do seu carro com quem entende do assunto.', 590, 762, { color: '#ffffff', size: 20, weight: 700 });

                    drawTicket(context, 486, 306, 574, 370, read('couponCode'));

                    context.setLineDash([6, 8]);
                    context.strokeStyle = '#d4e0ed';
                    context.lineWidth = 2;
                    context.beginPath();
                    context.moveTo(1108, 18);
                    context.lineTo(1108, 803);
                    context.stroke();
                    context.setLineDash([]);

                    drawInfoCard(context, 'user', 'Cliente', read('couponCustomer'), read('couponPhone'), 1144, 68, 400, 164, { detailSize: 20, detailLineHeight: 24 });
                    drawInfoCard(context, 'pin', 'Unidade', read('couponLocation'), read('couponAddress'), 1144, 250, 400, 198, { detailLines: 3, detailSize: 20, detailLineHeight: 24 });

                    fillRoundRect(context, 1144, 472, 190, 116, 24, '#ffffff');
                    strokeRoundRect(context, 1144, 472, 190, 116, 24, '#dbe5f1');
                    drawIcon(context, 'calendar', 1182, 530, 34, '#0b63ce');
                    drawText(context, 'EMISSÃO', 1212, 496, { color: '#0b63ce', size: 20, weight: 900 });
                    drawText(context, read('couponEarned'), 1212, 532, { color: '#020617', size: 23, weight: 900 });

                    fillRoundRect(context, 1354, 472, 190, 116, 24, '#ffffff');
                    strokeRoundRect(context, 1354, 472, 190, 116, 24, '#dbe5f1');
                    drawIcon(context, 'calendar-check', 1392, 530, 34, '#0b63ce');
                    drawText(context, 'VALIDADE', 1422, 496, { color: '#0b63ce', size: 20, weight: 900 });
                    drawText(context, read('couponExpires'), 1422, 532, { color: '#020617', size: 23, weight: 900 });

                    drawInfoCard(context, 'car', 'Lavagem que gerou', read('couponSource'), read('couponSourceDate'), 1144, 612, 400, 168, { breakHyphen: true, titleSize: 21, titleLines: 2, detailSize: 20, detailLineHeight: 24 });

                    context.setLineDash([6, 8]);
                    context.strokeStyle = '#d4e0ed';
                    context.beginPath();
                    context.moveTo(18, 816);
                    context.lineTo(1582, 816);
                    context.stroke();
                    context.setLineDash([]);

                    fillRoundRect(context, 54, 832, 1038, 110, 24, '#ffffff');
                    strokeRoundRect(context, 54, 832, 1038, 110, 24, '#edf2f7');
                    drawIcon(context, 'shield', 112, 884, 50, '#0b63ce');
                    drawText(context, 'Cupom pessoal e vinculado ao cliente informado.', 180, 858, { color: '#334155', size: 24, weight: 700 });
                    drawText(context, 'A validade e o status devem ser conferidos antes de aplicar o benefício no atendimento.', 180, 892, { color: '#334155', size: 22, weight: 700 });

                    fillRoundRect(context, 1160, 832, 384, 110, 24, '#f8fffb');
                    strokeRoundRect(context, 1160, 832, 384, 110, 24, '#d1fae5');
                    drawText(context, 'STATUS DO CUPOM', 1260, 852, { align: 'center', color: '#0b63ce', size: 20, weight: 900 });
                    drawText(context, status, 1320, 882, { align: 'center', color: '#10b981', size: 48, weight: 900 });
                    drawText(context, '✓', 1474, 870, { align: 'center', color: '#10b981', size: 58, weight: 900 });

                    const blob = await canvasToBlob(canvas);
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = `cupom-${sanitizeFileName(read('couponCode')) || 'fidelidade'}.png`;
                    link.click();
                    URL.revokeObjectURL(url);
                } catch (error) {
                    window.alert('Não foi possível gerar a imagem do cupom agora. Tente imprimir em PDF.');
                } finally {
                    button.disabled = false;
                    button.textContent = originalText;
                }
            });
        })();
    </script>
</x-app.layout>
