import { Head } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

const nextStatusFor = {
    aguardando: 'lavando',
    lavando: 'finalizando',
    finalizando: 'pronto_para_retirada',
    pronto_para_retirada: 'entregue',
};

function StatusPill({ label }) {
    return (
        <span className="shrink-0 rounded-full bg-zinc-100 px-2 py-1 text-[11px] font-semibold text-zinc-600">
            {label}
        </span>
    );
}

function OrderCard({ order, statuses, onMove, canUpdateStatus }) {
    const nextStatus = nextStatusFor[order.status];
    const visibleServices = order.services.slice(0, 2);
    const teamNames = order.team_members?.map((member) => member.name) ?? [];
    const teamLabel = teamNames.length > 0
        ? `${teamNames.slice(0, 2).join(', ')}${teamNames.length > 2 ? ` +${teamNames.length - 2}` : ''}`
        : 'Sem equipe';

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
            className="rounded-md border border-zinc-200 bg-white p-3 shadow-sm transition hover:border-cyan-300"
            draggable={canUpdateStatus}
            onDragStart={handleDragStart}
        >
            <div className="flex items-start justify-between gap-2">
                <div className="min-w-0">
                    <a href={order.show_url} className="font-semibold text-zinc-950">{order.vehicle.plate}</a>
                    <p className="mt-0.5 truncate text-xs text-zinc-500">{order.vehicle.brand} {order.vehicle.model}</p>
                </div>
                <StatusPill label={order.status_label} />
            </div>

            <dl className="mt-3 space-y-1.5 text-xs">
                <div className="flex justify-between gap-2">
                    <dt className="text-zinc-500">Cliente</dt>
                    <dd className="max-w-28 truncate font-medium">{order.customer.name}</dd>
                </div>
                <div className="flex justify-between gap-2">
                    <dt className="text-zinc-500">Tempo</dt>
                    <dd className="font-medium">{order.entered_at_for_humans}</dd>
                </div>
                <div className="flex justify-between gap-2">
                    <dt className="text-zinc-500">Equipe</dt>
                    <dd className="max-w-28 truncate font-medium">{teamLabel}</dd>
                </div>
                <div className="flex justify-between gap-2">
                    <dt className="text-zinc-500">Valor</dt>
                    <dd className="font-semibold">R$ {order.total_amount}</dd>
                </div>
            </dl>

            <div className="mt-3 flex flex-wrap gap-1">
                {visibleServices.map((service, index) => (
                    <span key={`${service.name}-${index}`} className="max-w-full truncate rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] text-zinc-600">
                        {service.name}
                    </span>
                ))}
                {order.services.length > 2 && (
                    <span className="rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] text-zinc-600">+{order.services.length - 2}</span>
                )}
            </div>

            <div className="mt-3 grid grid-cols-2 gap-1.5">
                <a href={order.show_url} className="rounded-md border border-zinc-300 px-2 py-1.5 text-center text-xs font-semibold">Detalhes</a>
                {nextStatus && canUpdateStatus ? (
                    <button
                        type="button"
                        onClick={() => onMove(order.update_url, nextStatus)}
                        className="rounded-md bg-zinc-950 px-2 py-1.5 text-xs font-semibold text-white"
                    >
                        {statuses[nextStatus]}
                    </button>
                ) : (
                    <span className="rounded-md bg-zinc-100 px-2 py-1.5 text-center text-xs font-semibold text-zinc-500">Concluido</span>
                )}
            </div>
        </article>
    );
}

function Column({ column, statuses, onMove, canUpdateStatus }) {
    const [isDraggingOver, setIsDraggingOver] = useState(false);

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
            className={`min-h-[520px] min-w-56 rounded-lg border bg-zinc-100 ${isDraggingOver ? 'border-cyan-400' : 'border-zinc-200'}`}
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
            <header className="sticky top-0 z-[1] flex items-center justify-between gap-3 border-b border-zinc-200 bg-zinc-100 px-3 py-2.5">
                <h2 className="text-sm font-semibold">{column.title}</h2>
                <span className="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-zinc-600">{column.orders.length}</span>
            </header>

            <div className="space-y-2.5 p-2.5">
                {column.orders.length ? column.orders.map((order) => (
                    <OrderCard key={order.id} order={order} statuses={statuses} onMove={onMove} canUpdateStatus={canUpdateStatus} />
                )) : (
                    <div className="rounded-md border border-dashed border-zinc-300 bg-white px-3 py-6 text-center text-sm text-zinc-500">
                        Sem lavagens nesta etapa.
                    </div>
                )}
            </div>
        </section>
    );
}

export default function Kanban({ columns: initialColumns, statuses, feedUrl, createUrl, dashboardUrl, logoUrl, auth }) {
    const [columns, setColumns] = useState(initialColumns);
    const [realtimeUpdated, setRealtimeUpdated] = useState(false);
    const canCreateWashOrder = ['admin', 'attendant'].includes(auth?.user?.role);
    const canUpdateStatus = ['admin', 'operator'].includes(auth?.user?.role);

    const refreshFeed = async () => {
        const { data } = await window.axios.get(feedUrl);
        setColumns(data.columns);
        setRealtimeUpdated(true);
    };

    const moveOrder = async (updateUrl, status) => {
        await window.axios.patch(updateUrl, {
            status,
            notes: 'Status atualizado pelo Kanban.',
        }, {
            headers: { Accept: 'application/json' },
        });

        await refreshFeed();
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

        return `${auth.user.name} · ${auth.user.role}`;
    }, [auth]);

    return (
        <>
            <Head title="Kanban · AutoFlow" />
            <div className="min-h-screen bg-zinc-50">
                <header className="border-b border-zinc-200 bg-white px-4 py-4 sm:px-6 lg:px-8">
                    <div className="flex flex-wrap items-center justify-between gap-4">
                        <div className="flex items-center gap-4">
                            <a href={dashboardUrl}>
                                <img src={logoUrl} alt="AutoFlow" className="w-32" />
                            </a>
                            <div>
                                {userLine && <p className="text-sm text-zinc-500">{userLine}</p>}
                                <h1 className="text-2xl font-semibold">Kanban operacional</h1>
                                {realtimeUpdated && <p className="mt-1 text-xs font-medium text-cyan-700">Atualizado em tempo real.</p>}
                            </div>
                        </div>
                        <div className="flex gap-2">
                            <a href={dashboardUrl} className="rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold">Dashboard</a>
                            {canCreateWashOrder && (
                                <a href={createUrl} className="rounded-md bg-cyan-700 px-4 py-2 text-sm font-semibold text-white">Nova lavagem</a>
                            )}
                        </div>
                    </div>
                </header>

                <main className="px-4 py-6 sm:px-6 lg:px-8">
                    <div className="grid gap-3 overflow-x-auto pb-4 xl:grid-cols-5">
                        {columns.map((column) => (
                            <Column key={column.key} column={column} statuses={statuses} onMove={moveOrder} canUpdateStatus={canUpdateStatus} />
                        ))}
                    </div>
                </main>
            </div>
        </>
    );
}
