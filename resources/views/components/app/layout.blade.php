<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'AutoFlow' }}</title>
    @include('components.favicon')
    <script>
        (() => {
            const preferredTheme = @json(\App\Models\AppSetting::theme());
            const resolvedTheme = preferredTheme === 'system'
                ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                : preferredTheme;

            document.documentElement.dataset.theme = preferredTheme;
            document.documentElement.dataset.themeEffective = resolvedTheme;
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php($appSettings = \App\Models\AppSetting::allSettings())
@php($appTheme = \App\Models\AppSetting::theme())
@php($currentLocation = \App\Support\TenantContext::currentLocation())
@php($isSuperAdmin = auth()->user()->isSuperAdmin())
@php($unitDisplayName = $currentLocation?->name ?? ($isSuperAdmin ? 'AutoFlow Admin' : ($appSettings['company_name'] ?? 'AutoFlow')))
@php($unitStatusLabel = $currentLocation?->accountStatusLabel())
@php($trialDaysRemaining = $currentLocation?->trialDaysRemaining())
@php($canAccess = fn (string $permission) => auth()->user()->canAccess($permission))
@php($appNotifications = \App\Support\AppNotificationCenter::for(auth()->user()))
@php($homeRoute = $isSuperAdmin ? route('super-admin.location-requests.index') : ($canAccess(\App\Support\Access\AccessControl::VIEW_DASHBOARD) ? route('dashboard') : route('kanban')))
@php($brandLogoUrl = $currentLocation?->logoUrl() ?? asset('images/autoflow-logo.png'))
@php($brandLogoAlt = $currentLocation?->name ?? 'AutoFlow')
@php($mobilePrimaryNav = $isSuperAdmin ? [
    ['label' => 'Solicitações', 'icon' => 'S', 'href' => route('super-admin.location-requests.index'), 'active' => request()->routeIs('super-admin.location-requests.*')],
    ['label' => 'Unidades', 'icon' => 'U', 'href' => route('super-admin.locations.index'), 'active' => request()->routeIs('super-admin.locations.*')],
    ['label' => 'Planos', 'icon' => 'P', 'href' => route('super-admin.plans.index'), 'active' => request()->routeIs('super-admin.plans.*')],
] : collect([
    ['label' => 'Painel', 'icon' => 'P', 'href' => $canAccess(\App\Support\Access\AccessControl::VIEW_DASHBOARD) ? route('dashboard') : null, 'active' => request()->routeIs('dashboard')],
    ['label' => 'Lavagens', 'icon' => 'L', 'href' => $canAccess(\App\Support\Access\AccessControl::CREATE_WASH_ORDER) ? route('wash-orders.index') : null, 'active' => request()->routeIs('wash-orders.*')],
    ['label' => 'Kanban', 'icon' => 'K', 'href' => $canAccess(\App\Support\Access\AccessControl::VIEW_KANBAN) ? route('kanban') : null, 'active' => request()->routeIs('kanban')],
    ['label' => 'Clientes', 'icon' => 'C', 'href' => $canAccess(\App\Support\Access\AccessControl::MANAGE_CUSTOMERS) ? route('customers.index') : null, 'active' => request()->routeIs('customers.*')],
    ['label' => 'Financeiro', 'icon' => '$', 'href' => $canAccess(\App\Support\Access\AccessControl::VIEW_FINANCE) ? route('finance.index') : null, 'active' => request()->routeIs('finance.*')],
])->filter(fn ($item) => filled($item['href']))->take(5)->values()->all())
<body class="{{ $appTheme === 'dark' ? 'bg-slate-950' : 'bg-[#061832]' }} text-slate-950 antialiased" data-theme="{{ $appTheme }}" data-theme-effective="{{ $appTheme === 'system' ? 'light' : $appTheme }}">
    <div class="min-h-screen p-2 lg:p-3" data-app-shell>
        <aside data-sidebar class="fixed inset-y-3 left-3 z-30 hidden w-[17rem] flex-col rounded-2xl {{ $appTheme === 'dark' ? 'bg-slate-950' : 'bg-[#061b36]' }} px-3 py-3 text-white shadow-2xl shadow-black/30 transition-transform duration-200 lg:flex">
            <a href="{{ $homeRoute }}" class="block shrink-0 rounded-xl bg-white px-4 py-4 shadow-inner shadow-slate-200">
                <img src="{{ $brandLogoUrl }}" alt="{{ $brandLogoAlt }}" class="mx-auto h-auto max-h-24 w-40 object-contain">
            </a>

            <nav class="mt-3 min-h-0 flex-1 space-y-1 overflow-y-auto pr-1">
                @if ($isSuperAdmin)
                    <a href="{{ route('super-admin.location-requests.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('super-admin.location-requests.*') ? 'bg-blue-600 text-white shadow-lg shadow-blue-950/30' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md border border-white/15 bg-white/10 text-[11px] font-bold">S</span>
                        Solicitações de cadastros
                    </a>
                    <a href="{{ route('super-admin.locations.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('super-admin.locations.*') ? 'bg-blue-600 text-white shadow-lg shadow-blue-950/30' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md border border-white/15 bg-white/10 text-[11px] font-bold">U</span>
                        Unidades
                    </a>
                    <a href="{{ route('super-admin.plans.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('super-admin.plans.*') ? 'bg-blue-600 text-white shadow-lg shadow-blue-950/30' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md border border-white/15 bg-white/10 text-[11px] font-bold">P</span>
                        Planos
                    </a>
                @else
                    @foreach ([
                        ['route' => 'dashboard', 'label' => 'Painel Principal', 'icon' => 'P', 'permission' => \App\Support\Access\AccessControl::VIEW_DASHBOARD],
                        ['route' => 'wash-orders.index', 'label' => 'Lavagens', 'icon' => 'L', 'permission' => \App\Support\Access\AccessControl::CREATE_WASH_ORDER],
                        ['route' => 'kanban', 'label' => 'Kanban de Lavagens', 'icon' => 'K', 'permission' => \App\Support\Access\AccessControl::VIEW_KANBAN],
                        ['route' => 'schedule.index', 'label' => 'Agenda', 'icon' => 'AG', 'permission' => \App\Support\Access\AccessControl::VIEW_SCHEDULE, 'module' => 'module_schedule'],
                        ['route' => 'history.index', 'label' => 'Histórico', 'icon' => 'H', 'permission' => \App\Support\Access\AccessControl::VIEW_OPERATIONAL_HISTORY],
                        ['route' => 'customers.index', 'label' => 'Clientes', 'icon' => 'C', 'permission' => \App\Support\Access\AccessControl::MANAGE_CUSTOMERS],
                        ['route' => 'loyalty-reports.index', 'label' => 'Fidelidade', 'icon' => 'FID', 'permission' => \App\Support\Access\AccessControl::MANAGE_CUSTOMERS],
                        ['route' => 'vehicles.index', 'label' => 'Veículos', 'icon' => 'V', 'permission' => \App\Support\Access\AccessControl::MANAGE_VEHICLES],
                        ['route' => 'services.index', 'label' => 'Serviços', 'icon' => 'S', 'permission' => \App\Support\Access\AccessControl::MANAGE_SERVICES],
                        ['route' => 'employees.index', 'label' => 'Equipe', 'icon' => 'E', 'permission' => \App\Support\Access\AccessControl::MANAGE_EMPLOYEES],
                        ['route' => 'audit-logs.index', 'label' => 'Auditoria', 'icon' => 'A', 'permission' => \App\Support\Access\AccessControl::VIEW_AUDIT_LOGS],
                        ['route' => 'finance.index', 'label' => 'Financeiro', 'icon' => '$', 'permission' => \App\Support\Access\AccessControl::VIEW_FINANCE],
                        ['route' => 'finance.cash-registers.index', 'label' => 'Caixa', 'icon' => 'CX', 'permission' => \App\Support\Access\AccessControl::MANAGE_CASH_REGISTER, 'module' => 'module_cash_register'],
                        ['route' => 'finance.credit-receivables.index', 'label' => 'Fiado', 'icon' => 'F$', 'permission' => \App\Support\Access\AccessControl::MANAGE_CREDIT_RECEIVABLES, 'module' => 'module_credit_receivables'],
                    ] as $item)
                        @continue(! $canAccess($item['permission']))
                        @continue(($item['module'] ?? null) && empty($appSettings[$item['module']]))
                        <a href="{{ route($item['route']) }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs($item['route']) || request()->routeIs(str_replace('.index', '.*', $item['route'])) ? 'bg-blue-600 text-white shadow-lg shadow-blue-950/30' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md border border-white/15 bg-white/10 text-[11px] font-bold">{{ $item['icon'] }}</span>
                            {{ $item['label'] }}
                        </a>
                    @endforeach

                    @foreach ([
                        ['label' => 'Relatorios', 'icon' => 'R', 'href' => route('reports.executive'), 'permission' => \App\Support\Access\AccessControl::VIEW_FINANCE],
                        ['label' => 'Assinatura', 'icon' => 'A', 'href' => route('subscriptions.show'), 'permission' => \App\Support\Access\AccessControl::MANAGE_SUBSCRIPTION],
                        ['label' => 'Configurações', 'icon' => 'G', 'href' => route('settings.edit'), 'permission' => \App\Support\Access\AccessControl::MANAGE_SETTINGS],
                    ] as $item)
                        @continue(! $canAccess($item['permission']))
                        <a href="{{ $item['href'] }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-200 transition hover:bg-white/10 hover:text-white">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md border border-white/15 bg-white/10 text-[11px] font-bold">{{ $item['icon'] }}</span>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                @endif

            </nav>

            <div class="mt-3 shrink-0 rounded-xl border border-white/10 bg-white/[0.06] px-3 py-3 shadow-inner shadow-white/5">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-sm font-black text-white shadow-lg shadow-blue-950/30">A</span>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold leading-5">Organize. Acompanhe.</p>
                        <p class="truncate text-xs font-semibold text-cyan-300">Fidelize seus clientes.</p>
                    </div>
                </div>
            </div>
        </aside>

        <div data-content class="min-h-[calc(100vh-16px)] overflow-visible rounded-2xl {{ $appTheme === 'dark' ? 'bg-slate-900 text-slate-100' : 'bg-slate-50' }} shadow-2xl shadow-black/30 transition-[margin] duration-200 lg:ml-[18rem]">
            <header class="sticky top-0 z-20 border-b {{ $appTheme === 'dark' ? 'border-slate-800 bg-slate-950/95' : 'border-slate-200 bg-white/95' }} px-3 py-3 backdrop-blur sm:px-6 lg:px-7">
                <div class="flex flex-wrap items-center justify-between gap-2 sm:gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <button type="button" data-sidebar-toggle aria-label="Ocultar menu" title="Ocultar menu" aria-expanded="true" class="hidden h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-100 lg:inline-flex">
                            <svg data-sidebar-icon-open xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="4" width="18" height="16" rx="2"></rect>
                                <path d="M9 4v16"></path>
                                <path d="M14 10l-2 2 2 2"></path>
                            </svg>
                            <svg data-sidebar-icon-closed xmlns="http://www.w3.org/2000/svg" class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M4 6h16"></path>
                                <path d="M4 12h16"></path>
                                <path d="M4 18h16"></path>
                            </svg>
                        </button>
                        <div class="min-w-0 py-0.5">
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="truncate text-lg font-bold {{ $appTheme === 'dark' ? 'text-white' : 'text-slate-950' }} sm:text-xl">{{ $heading ?? 'Painel Principal' }}</h1>
                                @if ($currentLocation)
                                    <span class="rounded-full {{ $currentLocation->account_status === \App\Models\WashLocation::ACCOUNT_STATUS_ACTIVE ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-amber-200' }} px-2 py-0.5 text-[11px] font-bold ring-1">
                                        {{ $unitStatusLabel }}
                                    </span>
                                @endif
                            </div>
                            <p class="truncate text-xs text-slate-500">{{ $isSuperAdmin ? 'Gerencie aprovações, unidades e planos.' : $unitDisplayName }}</p>
                        </div>
                    </div>

                    <div class="flex min-w-0 flex-1 items-center justify-end gap-2 sm:flex-none">
                        <div class="hidden h-12 max-w-[18rem] items-center gap-3 rounded-xl border {{ $appTheme === 'dark' ? 'border-slate-700 bg-slate-900' : 'border-slate-200 bg-white' }} px-3 shadow-sm md:flex">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $currentLocation ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-700' }} text-xs font-black">{{ $currentLocation ? strtoupper(substr($currentLocation->name, 0, 1)) : 'A' }}</span>
                            <div class="min-w-0">
                                <p class="text-xs text-slate-500">{{ $isSuperAdmin ? 'Administração do produto' : 'Unidade atual' }}</p>
                                <p class="truncate text-sm font-semibold {{ $appTheme === 'dark' ? 'text-white' : 'text-slate-950' }}">{{ $unitDisplayName }}</p>
                                @if ($currentLocation)
                                    <p class="truncate text-[11px] font-semibold text-slate-500">
                                        @if ($trialDaysRemaining !== null && $currentLocation->account_status === \App\Models\WashLocation::ACCOUNT_STATUS_TRIAL)
                                            Trial: {{ $trialDaysRemaining }} dia{{ $trialDaysRemaining === 1 ? '' : 's' }}
                                        @else
                                            {{ $unitStatusLabel }}
                                        @endif
                                    </p>
                                @endif
                            </div>
                        </div>
                        <details class="relative">
                            <summary class="inline-flex h-12 w-12 cursor-pointer list-none items-center justify-center rounded-xl border {{ $appTheme === 'dark' ? 'border-slate-700 bg-slate-900 text-slate-100' : 'border-slate-200 bg-white text-slate-700' }} shadow-sm transition hover:bg-slate-50" aria-label="Notificacoes" title="Notificacoes">
                                <span class="relative">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 7h18s-3 0-3-7"></path>
                                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                                    </svg>
                                    @if (count($appNotifications) > 0)
                                        <span class="absolute -right-2 -top-2 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-black leading-none text-white">{{ count($appNotifications) }}</span>
                                    @endif
                                </span>
                            </summary>
                            <div class="absolute right-0 z-30 mt-2 w-80 max-w-[calc(100vw-2rem)] rounded-2xl border border-slate-200 bg-white p-3 text-sm text-slate-700 shadow-2xl shadow-slate-950/15">
                                <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-2">
                                    <p class="font-black text-slate-950">Notificacoes</p>
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-600">{{ count($appNotifications) }}</span>
                                </div>
                                <div class="mt-3 space-y-2">
                                    @forelse ($appNotifications as $notification)
                                        @php($notificationTone = match ($notification['tone']) {
                                            'danger' => 'border-red-200 bg-red-50 text-red-900',
                                            'warning' => 'border-amber-200 bg-amber-50 text-amber-900',
                                            'success' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
                                            default => 'border-blue-200 bg-blue-50 text-blue-900',
                                        })
                                        <div class="rounded-xl border p-3 {{ $notificationTone }}">
                                            <p class="font-black">{{ $notification['title'] }}</p>
                                            <p class="mt-1 text-xs leading-5">{{ $notification['body'] }}</p>
                                            @if ($notification['url'])
                                                <a href="{{ $notification['url'] }}" class="mt-2 inline-flex text-xs font-black underline">{{ $notification['action'] ?? 'Abrir' }}</a>
                                            @endif
                                        </div>
                                    @empty
                                        <p class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-4 text-center text-sm text-slate-500">Nenhuma notificacao importante.</p>
                                    @endforelse
                                </div>
                            </div>
                        </details>
                        <div class="hidden h-12 items-center gap-2 rounded-xl border {{ $appTheme === 'dark' ? 'border-slate-700 bg-slate-900' : 'border-slate-200 bg-white' }} px-3 shadow-sm sm:flex">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-cyan-400 text-xs font-bold text-white">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                            <div class="hidden min-w-0 sm:block">
                                <p class="max-w-32 truncate text-sm font-semibold">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-slate-500">{{ auth()->user()->roleLabel() }}</p>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-slate-950 text-white shadow-sm transition hover:bg-slate-800" aria-label="Sair" title="Sair">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                    <path d="M16 17l5-5-5-5"></path>
                                    <path d="M21 12H9"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>

                <nav class="mt-3 flex gap-2 overflow-x-auto pb-0.5 lg:hidden" aria-label="Navegacao secundaria">
                    @if ($isSuperAdmin)
                        <a href="{{ route('super-admin.location-requests.index') }}" class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium">Solicitações</a>
                        <a href="{{ route('super-admin.locations.index') }}" class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium">Unidades</a>
                        <a href="{{ route('super-admin.plans.index') }}" class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium">Planos</a>
                    @else
                        @if ($canAccess(\App\Support\Access\AccessControl::VIEW_DASHBOARD))
                            <a href="{{ route('dashboard') }}" class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium">Painel</a>
                        @endif
                        @if ($canAccess(\App\Support\Access\AccessControl::CREATE_WASH_ORDER))
                            <a href="{{ route('wash-orders.index') }}" class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium">Lavagens</a>
                        @endif
                        @if ($canAccess(\App\Support\Access\AccessControl::VIEW_KANBAN))
                            <a href="{{ route('kanban') }}" class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium">Kanban</a>
                        @endif
                        @if ($canAccess(\App\Support\Access\AccessControl::VIEW_SCHEDULE) && ! empty($appSettings['module_schedule']))
                            <a href="{{ route('schedule.index') }}" class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium">Agenda</a>
                        @endif
                        @if ($canAccess(\App\Support\Access\AccessControl::VIEW_OPERATIONAL_HISTORY))
                            <a href="{{ route('history.index') }}" class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium">Histórico</a>
                        @endif
                        @if ($canAccess(\App\Support\Access\AccessControl::MANAGE_CUSTOMERS))
                            <a href="{{ route('customers.index') }}" class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium">Clientes</a>
                            <a href="{{ route('loyalty-reports.index') }}" class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium">Fidelidade</a>
                        @endif
                        @if ($canAccess(\App\Support\Access\AccessControl::MANAGE_VEHICLES))
                            <a href="{{ route('vehicles.index') }}" class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium">Veículos</a>
                        @endif
                        @if ($canAccess(\App\Support\Access\AccessControl::VIEW_FINANCE))
                            <a href="{{ route('finance.index') }}" class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium">Financeiro</a>
                            <a href="{{ route('reports.executive') }}" class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium">Relatorios</a>
                        @endif
                        @if ($canAccess(\App\Support\Access\AccessControl::MANAGE_CASH_REGISTER))
                            @if (! empty($appSettings['module_cash_register']))
                                <a href="{{ route('finance.cash-registers.index') }}" class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium">Caixa</a>
                            @endif
                        @endif
                        @if ($canAccess(\App\Support\Access\AccessControl::MANAGE_CREDIT_RECEIVABLES))
                            @if (! empty($appSettings['module_credit_receivables']))
                                <a href="{{ route('finance.credit-receivables.index') }}" class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium">Fiado</a>
                            @endif
                        @endif
                        @if ($canAccess(\App\Support\Access\AccessControl::MANAGE_EMPLOYEES))
                            <a href="{{ route('employees.index') }}" class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium">Equipe</a>
                        @endif
                        @if ($canAccess(\App\Support\Access\AccessControl::VIEW_AUDIT_LOGS))
                            <a href="{{ route('audit-logs.index') }}" class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium">Auditoria</a>
                        @endif
                        @if ($canAccess(\App\Support\Access\AccessControl::MANAGE_SUBSCRIPTION))
                            <a href="{{ route('subscriptions.show') }}" class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium">Assinatura</a>
                        @endif
                        @if ($canAccess(\App\Support\Access\AccessControl::MANAGE_SETTINGS))
                            <a href="{{ route('settings.edit') }}" class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium">Configurações</a>
                        @endif
                    @endif
                </nav>
            </header>

            <main class="px-4 pb-24 pt-5 sm:px-6 lg:px-8 lg:pb-5">
                @if (session('status'))
                    <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
                @endif

                @if ($currentLocation && $canAccess(\App\Support\Access\AccessControl::MANAGE_SUBSCRIPTION) && $currentLocation->subscriptionStatus() === \App\Models\WashLocation::ACCOUNT_STATUS_TRIAL && $trialDaysRemaining !== null && $trialDaysRemaining <= 5)
                    <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        @if ($trialDaysRemaining === 0)
                            <strong>Trial expirado:</strong>
                            escolha um plano para reativar a unidade.
                        @else
                            <strong>Trial em andamento:</strong>
                            restam {{ $trialDaysRemaining }} dia{{ $trialDaysRemaining === 1 ? '' : 's' }} para ativar a assinatura da unidade.
                        @endif
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>

        <nav data-mobile-bottom-nav class="fixed inset-x-2 bottom-2 z-40 grid gap-1 rounded-2xl border border-slate-200 bg-white/95 p-1.5 shadow-2xl shadow-slate-950/20 backdrop-blur lg:hidden" style="grid-template-columns: repeat({{ max(1, count($mobilePrimaryNav)) }}, minmax(0, 1fr));" aria-label="Navegacao principal mobile">
            @foreach ($mobilePrimaryNav as $item)
                <a href="{{ $item['href'] }}" class="flex min-h-14 min-w-0 flex-col items-center justify-center rounded-xl px-1 py-1 text-center text-[11px] font-black transition {{ $item['active'] ? 'bg-blue-700 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}">
                    <span class="flex h-6 w-6 items-center justify-center rounded-lg {{ $item['active'] ? 'bg-white/15 text-white' : 'bg-slate-100 text-slate-700' }} text-[10px]">{{ $item['icon'] }}</span>
                    <span class="mt-0.5 max-w-full truncate">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    <script>
        const preferredTheme = document.body.dataset.theme || 'light';
        const themeMedia = window.matchMedia('(prefers-color-scheme: dark)');
        const applyResolvedTheme = () => {
            const resolvedTheme = preferredTheme === 'system' && themeMedia.matches ? 'dark' : (preferredTheme === 'dark' ? 'dark' : 'light');

            document.documentElement.dataset.theme = preferredTheme;
            document.documentElement.dataset.themeEffective = resolvedTheme;
            document.body.dataset.themeEffective = resolvedTheme;
        };

        applyResolvedTheme();
        themeMedia.addEventListener?.('change', applyResolvedTheme);

        const shell = document.querySelector('[data-app-shell]');
        const sidebar = document.querySelector('[data-sidebar]');
        const content = document.querySelector('[data-content]');
        const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
        const sidebarIconOpen = document.querySelector('[data-sidebar-icon-open]');
        const sidebarIconClosed = document.querySelector('[data-sidebar-icon-closed]');
        const sidebarStorageKey = 'autoflow.sidebar.collapsed';

        const applySidebarState = (collapsed) => {
            sidebar.classList.toggle('-translate-x-[calc(100%+1rem)]', collapsed);
            content.classList.toggle('lg:ml-[18rem]', !collapsed);
            content.classList.toggle('lg:ml-0', collapsed);
            sidebarToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            sidebarToggle.setAttribute('aria-label', collapsed ? 'Mostrar menu' : 'Ocultar menu');
            sidebarToggle.setAttribute('title', collapsed ? 'Mostrar menu' : 'Ocultar menu');
            sidebarIconOpen.classList.toggle('hidden', collapsed);
            sidebarIconClosed.classList.toggle('hidden', !collapsed);
            shell.dataset.sidebarCollapsed = collapsed ? 'true' : 'false';
        };

        const initialSidebarCollapsed = localStorage.getItem(sidebarStorageKey) === 'true';
        applySidebarState(initialSidebarCollapsed);

        sidebarToggle.addEventListener('click', () => {
            const collapsed = shell.dataset.sidebarCollapsed !== 'true';

            localStorage.setItem(sidebarStorageKey, collapsed ? 'true' : 'false');
            applySidebarState(collapsed);
        });

        document.querySelectorAll('[data-copy-message-button]').forEach((button) => {
            button.addEventListener('click', async () => {
                const textarea = button.closest('section')?.querySelector('[data-copy-message]');

                if (! textarea) {
                    return;
                }

                try {
                    await navigator.clipboard.writeText(textarea.value);
                    button.textContent = 'Mensagem copiada';
                } catch (error) {
                    textarea.select();
                    document.execCommand('copy');
                    button.textContent = 'Mensagem copiada';
                }
            });
        });
    </script>
</body>
</html>
