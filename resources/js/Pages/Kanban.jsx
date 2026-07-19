import { Head, router } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';

const nextStatusFor = {
    aguardando: 'lavando',
    lavando: 'finalizando',
    finalizando: 'pronto_para_retirada',
    pronto_para_retirada: 'entregue',
};

const columnStyles = {
    awaiting: {
        container: 'border-t-slate-400 text-slate-700 bg-slate-50',
        count: 'bg-slate-100 text-slate-700',
        accent: 'bg-slate-500',
    },
    washing: {
        container: 'border-t-blue-600 text-blue-700 bg-blue-50',
        count: 'bg-blue-100 text-blue-700',
        accent: 'bg-blue-600',
    },
    finishing: {
        container: 'border-t-orange-500 text-orange-600 bg-orange-50',
        count: 'bg-orange-100 text-orange-700',
        accent: 'bg-orange-500',
    },
    ready: {
        container: 'border-t-green-600 text-green-700 bg-green-50',
        count: 'bg-green-100 text-green-700',
        accent: 'bg-green-600',
    },
    delivered: {
        container: 'border-t-slate-950 text-slate-800 bg-slate-50',
        count: 'bg-slate-900 text-white',
        accent: 'bg-slate-950',
    },
};

function StatusPill({ label, columnKey }) {
    const style = columnStyles[columnKey] ?? columnStyles.awaiting;

    return (
        <span className={`shrink-0 rounded-full px-2 py-1 text-[11px] font-bold shadow-sm ${style.count}`}>
            {label}
        </span>
    );
}

function OrderCard({ order, statuses, onMove, canUpdateStatus, columnKey, showOutsideDayBadge }) {
    const nextStatus = nextStatusFor[order.status];
    const canMoveOrder = canUpdateStatus && order.can_update_status;
    const visibleServices = order.services.slice(0, 2);
    const teamNames = order.team_members?.map((member) => member.name) ?? [];
    const teamLabel = teamNames.length > 0
        ? `${teamNames.slice(0, 2).join(', ')}${teamNames.length > 2 ? ` +${teamNames.length - 2}` : ''}`
        : 'Sem equipe';
    const style = columnStyles[columnKey] ?? columnStyles.awaiting;
    const isOverdue = Boolean(order.is_overdue);

    const handleDragStart = (event) => {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('application/json', JSON.stringify({
            id: order.id,
            status: order.status,
            updateUrl: order.update_url,
        }));
    };

    return (
        <article
            data-tour="kanban-card"
            className={`group rounded-lg border p-3 text-slate-950 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md ${isOverdue ? 'border-red-200 bg-red-50 ring-1 ring-red-100 hover:border-red-300' : 'border-slate-200 bg-white hover:border-blue-200'}`}
            draggable={canMoveOrder}
            onDragStart={handleDragStart}
        >
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    {order.show_url ? (
                        <a href={order.show_url} className="text-sm font-black tracking-wide text-slate-950 group-hover:text-blue-700">{order.vehicle.plate}</a>
                    ) : (
                        <span className="text-sm font-black tracking-wide text-slate-950">{order.vehicle.plate}</span>
                    )}
                    <p className="mt-1 truncate text-xs font-medium text-slate-500">{order.customer.name}</p>
                </div>
                <div className="flex shrink-0 flex-col items-end gap-1">
                    <StatusPill label={order.status_label} columnKey={columnKey} />
                    {showOutsideDayBadge && order.is_outside_today && (
                        <span className="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-black uppercase text-amber-700">
                            Fora do dia · {order.entered_at_date_label}
                        </span>
                    )}
                    {isOverdue && (
                        <span className="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-black uppercase text-red-700">
                            Tempo estourado
                        </span>
                    )}
                </div>
            </div>

            <div className="mt-3 flex items-center justify-between gap-3 text-xs">
                <div className="min-w-0">
                    <p className="truncate font-semibold text-slate-700">{order.vehicle.brand} {order.vehicle.model}</p>
                    <p className="mt-1 truncate text-slate-500">{visibleServices[0]?.name ?? 'Sem serviço'}</p>
                </div>
                <div className="shrink-0 text-right">
                    <p className="font-black text-slate-900">R$ {order.total_amount}</p>
                    <p className="mt-1 text-slate-500">{order.entered_at_for_humans}</p>
                    {order.estimated_completion_label && (
                        <p className={`mt-1 font-bold ${isOverdue ? 'text-red-700' : 'text-slate-500'}`}>
                            Prev.: {order.estimated_completion_label}
                        </p>
                    )}
                </div>
            </div>

            {isOverdue && order.overdue_label && (
                <div className="mt-3 rounded-lg border border-red-200 bg-white px-3 py-2 text-xs font-bold text-red-700">
                    {order.overdue_label}. Decida se deve avançar para a próxima etapa.
                </div>
            )}

            <div className="mt-3 flex flex-wrap gap-1.5">
                {visibleServices.slice(1).map((service, index) => (
                    <span key={`${service.name}-${index}`} className="max-w-full truncate rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600">
                        {service.name}
                    </span>
                ))}
                {order.services.length > 2 && (
                    <span className="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600">+{order.services.length - 2}</span>
                )}
            </div>

            <div className="mt-3 flex items-center justify-between gap-3 border-t border-slate-100 pt-3 text-xs">
                <p className="min-w-0 truncate text-slate-500">
                    <span className="font-semibold text-slate-700">Equipe:</span> {teamLabel}
                </p>
                <span className={`h-2 w-2 shrink-0 rounded-full ${style.accent}`}></span>
            </div>

            <div data-tour="kanban-actions" className="mt-3 grid grid-cols-2 gap-1.5">
                {order.show_url ? (
                    <a href={order.show_url} className="rounded-lg border border-slate-200 px-2 py-1.5 text-center text-xs font-bold text-slate-700 hover:bg-slate-50">Detalhes</a>
                ) : null}
                {nextStatus && canMoveOrder ? (
                    <button
                        type="button"
                        onClick={() => onMove(order.update_url, nextStatus)}
                        className="rounded-lg bg-slate-950 px-2 py-1.5 text-xs font-bold text-white hover:bg-blue-700"
                    >
                        {statuses[nextStatus]}
                    </button>
                ) : (
                    <span className="rounded-lg bg-slate-100 px-2 py-1.5 text-center text-xs font-bold text-slate-500">
                        {nextStatus ? 'Restrito' : 'Concluído'}
                    </span>
                )}
            </div>
        </article>
    );
}

function Column({ column, statuses, onMove, canUpdateStatus, showOutsideDayBadge, isMobileActive }) {
    const [isDraggingOver, setIsDraggingOver] = useState(false);
    const style = columnStyles[column.key] ?? columnStyles.awaiting;

    const handleDrop = async (event) => {
        event.preventDefault();
        setIsDraggingOver(false);

        if (!canUpdateStatus) {
            return;
        }

        const payload = JSON.parse(event.dataTransfer.getData('application/json') || '{}');

        if (!payload.updateUrl || payload.status === column.target_status) {
            return;
        }

        await onMove(payload.updateUrl, column.target_status);
    };

    return (
        <section
            id={`kanban-column-${column.key}`}
            className={`min-h-[520px] w-[min(86vw,22rem)] shrink-0 snap-center scroll-mt-28 rounded-xl border border-t-4 transition xl:min-h-[560px] xl:w-auto xl:min-w-0 ${style.container} ${isDraggingOver ? 'ring-2 ring-blue-300' : ''} ${isMobileActive ? 'ring-2 ring-blue-200 xl:ring-0' : ''}`}
            onDragOver={(event) => {
                if (!canUpdateStatus) {
                    return;
                }

                event.preventDefault();
                setIsDraggingOver(true);
            }}
            onDragLeave={() => setIsDraggingOver(false)}
            onDrop={handleDrop}
        >
            <header className="sticky top-0 z-[1] flex items-center justify-between gap-3 rounded-t-xl bg-white/80 px-3 py-3 backdrop-blur">
                <div className="min-w-0">
                    <h2 className="truncate text-sm font-black">{column.title}</h2>
                    <p className="mt-0.5 text-[11px] font-semibold text-slate-500">Fluxo operacional</p>
                </div>
                <span className={`rounded-full px-2.5 py-1 text-xs font-black shadow-sm ${style.count}`}>{column.orders.length}</span>
            </header>

            <div className="space-y-2.5 p-2.5">
                {column.orders.length ? column.orders.map((order) => (
                    <OrderCard
                        key={order.id}
                        order={order}
                        statuses={statuses}
                        onMove={onMove}
                        canUpdateStatus={canUpdateStatus}
                        columnKey={column.key}
                        showOutsideDayBadge={showOutsideDayBadge}
                    />
                )) : (
                    <div className="rounded-lg border border-dashed border-slate-200 bg-white/60 px-3 py-10 text-center text-sm text-slate-500">
                        Sem lavagens nesta etapa.
                    </div>
                )}
            </div>
        </section>
    );
}

export default function Kanban({
    columns: initialColumns,
    statuses,
    filters,
    periodOptions,
    feedUrl,
    filterUrl,
    createUrl,
    logoutUrl,
    csrfToken,
    dashboardUrl,
    logoUrl,
    auth,
    currentLocation,
    onboardingTour,
}) {
    const [columns, setColumns] = useState(initialColumns);
    const [selectedDate, setSelectedDate] = useState(filters?.date ?? '');
    const [overdueOnly, setOverdueOnly] = useState(Boolean(filters?.overdue_only));
    const [realtimeUpdated, setRealtimeUpdated] = useState(false);
    const [statusError, setStatusError] = useState(null);
    const [activeMobileColumn, setActiveMobileColumn] = useState(initialColumns[0]?.key ?? null);
    const boardRef = useRef(null);
    const canUpdateStatus = ['owner', 'admin', 'operator'].includes(auth?.user?.role);
    const activePeriod = filters?.period ?? 'today';
    const showOutsideDayBadge = Boolean(filters?.show_outside_day_badge);
    const overdueOrders = useMemo(
        () => columns.reduce((total, column) => total + column.orders.filter((order) => order.is_overdue).length, 0),
        [columns],
    );
    const totalOrders = useMemo(
        () => columns.reduce((total, column) => total + column.orders.length, 0),
        [columns],
    );
    const busiestColumn = useMemo(
        () => columns.reduce((current, column) => (column.orders.length > current.orders.length ? column : current), columns[0] ?? { orders: [] }),
        [columns],
    );

    const applyFilters = (nextFilters = {}) => {
        const period = nextFilters.period ?? activePeriod;
        const date = nextFilters.date ?? selectedDate;

        router.get(filterUrl, {
            period: period === 'today' ? undefined : period,
            date: period === 'date' ? date : undefined,
            overdue: (nextFilters.overdueOnly ?? overdueOnly) ? 1 : undefined,
        }, {
            preserveScroll: true,
            replace: true,
        });
    };

    const refreshFeed = async () => {
        const { data } = await window.axios.get(feedUrl);
        setColumns(data.columns);
        setRealtimeUpdated(true);
    };

    const moveOrder = async (updateUrl, status) => {
        try {
            setStatusError(null);
            await window.axios.patch(updateUrl, {
                status,
                notes: 'Status atualizado pelo Kanban.',
            }, {
                headers: { Accept: 'application/json' },
            });

            await refreshFeed();
        } catch (error) {
            setStatusError(error.response?.data?.message ?? 'Não foi possível atualizar o status.');
        }
    };

    useEffect(() => {
        setColumns(initialColumns);
        setSelectedDate(filters?.date ?? '');
        setOverdueOnly(Boolean(filters?.overdue_only));
        setActiveMobileColumn((current) => {
            if (current && initialColumns.some((column) => column.key === current)) {
                return current;
            }

            return initialColumns.find((column) => column.orders.length > 0)?.key ?? initialColumns[0]?.key ?? null;
        });
    }, [initialColumns, filters?.date, filters?.overdue_only]);

    const focusMobileColumn = (columnKey) => {
        setActiveMobileColumn(columnKey);

        const target = boardRef.current?.querySelector(`#kanban-column-${columnKey}`);
        target?.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
    };

    useEffect(() => {
        if (!window.Echo) {
            return undefined;
        }

        const channel = window.Echo.channel('wash-orders');
        channel.listen('.WashOrderStatusChanged', refreshFeed);

        return () => window.Echo.leave('wash-orders');
    }, [feedUrl]);

    const userLine = useMemo(() => {
        if (!auth?.user) {
            return null;
        }

        return `${auth.user.name} · ${auth.user.role_label ?? auth.user.role}`;
    }, [auth]);

    useEffect(() => {
        if (!onboardingTour?.key) {
            return undefined;
        }

        const script = document.createElement('script');
        script.type = 'application/json';
        script.dataset.onboardingTour = 'true';
        script.dataset.dynamicTour = onboardingTour.key;
        script.textContent = JSON.stringify(onboardingTour);
        document.body.appendChild(script);
        window.dispatchEvent(new Event('autoflow:tours-ready'));

        return () => {
            script.remove();
            document.querySelector(`[data-onboarding-tour-launch="${onboardingTour.key}"]`)?.remove();
        };
    }, [onboardingTour]);

    return (
        <>
            <Head title="Kanban · AutoFlow" />
            <div className="min-h-screen bg-[#061832] p-1.5 sm:p-2 lg:p-3">
                <div className="min-h-[calc(100vh-12px)] overflow-hidden rounded-2xl bg-slate-50 shadow-2xl shadow-black/30 sm:min-h-[calc(100vh-16px)]">
                <header data-tour="kanban-header" className="sticky top-0 z-20 border-b border-slate-200 bg-white/95 px-3 py-3 backdrop-blur sm:px-6 sm:py-4 lg:px-8">
                    <div className="flex flex-wrap items-center justify-between gap-4">
                        <div className="flex items-center gap-4">
                            {dashboardUrl ? (
                                <a href={dashboardUrl} className="hidden rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm sm:block">
                                    <img src={logoUrl} alt="AutoFlow" className="w-28" />
                                </a>
                            ) : (
                                <div className="hidden rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm sm:block">
                                    <img src={logoUrl} alt="AutoFlow" className="w-28" />
                                </div>
                            )}
                            <div>
                                <div className="flex items-center gap-3">
                                    <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-sm font-black text-blue-700">K</span>
                                    <div>
                                        <h1 className="text-lg font-black text-slate-950 sm:text-2xl">Kanban operacional</h1>
                                        {userLine && <p className="text-sm text-slate-500">{userLine}</p>}
                                    </div>
                                </div>
                                {realtimeUpdated && <p className="mt-2 text-xs font-bold text-blue-700">Atualizado em tempo real.</p>}
                            </div>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            {dashboardUrl && (
                                <a href={dashboardUrl} className="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 shadow-sm hover:bg-slate-50">Dashboard</a>
                            )}
                            {createUrl && (
                                <a href={createUrl} className="rounded-lg bg-blue-700 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-blue-800">Nova lavagem</a>
                            )}
                            {logoutUrl && (
                                <form method="POST" action={logoutUrl}>
                                    <input type="hidden" name="_token" value={csrfToken ?? ''} />
                                    <button type="submit" className="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-slate-950 text-white shadow-sm transition hover:bg-slate-800" aria-label="Sair" title="Sair">
                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                            <path d="M16 17l5-5-5-5" />
                                            <path d="M21 12H9" />
                                        </svg>
                                    </button>
                                </form>
                            )}
                        </div>
                    </div>
                </header>

                <main className="px-3 py-4 sm:px-6 sm:py-5 lg:px-8">
                    {currentLocation && (
                        <section data-tour="kanban-location" className="mb-5 rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-950 shadow-sm">
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p className="text-xs font-black uppercase tracking-[0.18em] text-blue-700">Unidade atual</p>
                                    <p className="mt-1 font-black">{currentLocation.name}</p>
                                </div>
                                <span className="rounded-full bg-white px-3 py-1 text-xs font-black text-blue-700">{currentLocation.account_status}</span>
                            </div>
                        </section>
                    )}

                    <section className="rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
                        <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                            <div className="flex items-center gap-3">
                                <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-sm font-black text-blue-700">F</span>
                                <div>
                                    <h2 className="text-lg font-black text-slate-950 sm:text-xl">Fluxo de Lavagens</h2>
                                    <p className="text-sm text-slate-500">Acompanhe, arraste e avance as lavagens abertas.</p>
                                </div>
                            </div>
                            <div className="hidden gap-2 sm:flex">
                                <span className="rounded-lg border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700">Visualização: Kanban</span>
                            </div>
                        </div>

                        <div data-tour="kanban-filters" className="mb-4 grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 lg:grid-cols-[1fr_auto] lg:items-end">
                            <div>
                                <p className="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Período operacional</p>
                                <p className="mt-1 text-sm font-bold text-slate-900">{filters?.label ?? 'Hoje'}</p>
                                <div className="mt-2 flex flex-wrap gap-2 text-xs font-black text-slate-600">
                                    <span className="rounded-full bg-white px-3 py-1 ring-1 ring-slate-200">{totalOrders} lavagem{totalOrders === 1 ? '' : 'ns'}</span>
                                    <span className={`rounded-full px-3 py-1 ring-1 ${overdueOrders > 0 ? 'bg-red-50 text-red-700 ring-red-100' : 'bg-white ring-slate-200'}`}>
                                        {overdueOrders} atrasada{overdueOrders === 1 ? '' : 's'}
                                    </span>
                                    {busiestColumn?.title && (
                                        <span className="rounded-full bg-white px-3 py-1 ring-1 ring-slate-200">Maior fila: {busiestColumn.title}</span>
                                    )}
                                </div>
                            </div>
                            <div className="flex flex-wrap items-end gap-2">
                                <label className="text-xs font-bold text-slate-600">
                                    <span className="mb-1 block">Filtrar por</span>
                                    <select
                                        value={activePeriod}
                                        onChange={(event) => applyFilters({ period: event.target.value })}
                                        className="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-bold text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                    >
                                        {Object.entries(periodOptions ?? {}).map(([value, label]) => (
                                            <option key={value} value={value}>{label}</option>
                                        ))}
                                    </select>
                                </label>
                                {activePeriod === 'date' && (
                                    <label className="text-xs font-bold text-slate-600">
                                        <span className="mb-1 block">Data</span>
                                        <input
                                            type="date"
                                            value={selectedDate}
                                            onChange={(event) => setSelectedDate(event.target.value)}
                                            className="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-bold text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                        />
                                    </label>
                                )}
                                {activePeriod === 'date' && (
                                    <button
                                        type="button"
                                        onClick={() => applyFilters({ period: 'date', date: selectedDate, overdueOnly })}
                                        className="rounded-lg bg-slate-950 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-blue-700"
                                    >
                                        Aplicar
                                    </button>
                                )}
                                <label className="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-bold text-slate-700 shadow-sm">
                                    <input
                                        type="checkbox"
                                        checked={overdueOnly}
                                        onChange={(event) => {
                                            const checked = event.target.checked;
                                            setOverdueOnly(checked);
                                            applyFilters({ overdueOnly: checked });
                                        }}
                                        className="rounded border-slate-300 text-red-600 focus:ring-red-500"
                                    />
                                    Somente atrasadas
                                </label>
                            </div>
                        </div>

                        <div data-tour="kanban-mobile-tabs" className="mb-3 flex gap-2 overflow-x-auto pb-1 xl:hidden" aria-label="Atalhos das colunas do Kanban">
                            {columns.map((column) => {
                                const style = columnStyles[column.key] ?? columnStyles.awaiting;
                                const isActive = activeMobileColumn === column.key;

                                return (
                                    <button
                                        key={column.key}
                                        type="button"
                                        onClick={() => focusMobileColumn(column.key)}
                                        className={`shrink-0 rounded-full px-3 py-2 text-xs font-black ring-1 transition ${isActive ? `${style.count} ring-transparent` : 'bg-white text-slate-600 ring-slate-200'}`}
                                    >
                                        {column.title} · {column.orders.length}
                                    </button>
                                );
                            })}
                        </div>

                        <div ref={boardRef} data-tour="kanban-board" className="-mx-3 flex snap-x gap-3 overflow-x-auto px-3 pb-4 sm:-mx-4 sm:px-4 xl:mx-0 xl:grid xl:grid-cols-5 xl:px-0">
                            {statusError && (
                                <div className="w-[min(86vw,22rem)] shrink-0 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-800 xl:col-span-5 xl:w-auto">
                                    {statusError}
                                </div>
                            )}
                            {columns.map((column) => (
                                <Column
                                    key={column.key}
                                    column={column}
                                    statuses={statuses}
                                    onMove={moveOrder}
                                    canUpdateStatus={canUpdateStatus}
                                    showOutsideDayBadge={showOutsideDayBadge}
                                    isMobileActive={activeMobileColumn === column.key}
                                />
                            ))}
                        </div>
                    </section>
                </main>
                </div>
            </div>
        </>
    );
}
