import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';

function ProgressStep({ label, done, current }) {
    const classes = current
        ? 'border-cyan-700 bg-cyan-50'
        : done
            ? 'border-emerald-200 bg-emerald-50'
            : 'border-zinc-200 bg-zinc-50';

    return (
        <div className={`rounded-md border px-3 py-3 ${classes}`}>
            <div className={`h-2 w-full rounded-full ${done ? 'bg-cyan-700' : 'bg-zinc-200'}`} />
            <p className="mt-3 text-xs font-semibold">{label}</p>
        </div>
    );
}

export default function Tracking({ washOrder: initialWashOrder, statuses, progressStatuses, feedUrl, logoUrl }) {
    const [washOrder, setWashOrder] = useState(initialWashOrder);
    const [realtimeUpdated, setRealtimeUpdated] = useState(false);

    const refreshFeed = async () => {
        const { data } = await window.axios.get(feedUrl);
        setWashOrder(data.washOrder);
        setRealtimeUpdated(true);
    };

    useEffect(() => {
        if (!window.Echo) {
            return undefined;
        }

        const channelName = `wash-order.${washOrder.id}`;
        const channel = window.Echo.channel(channelName);
        channel.listen('.WashOrderStatusChanged', refreshFeed);

        return () => window.Echo.leave(channelName);
    }, [feedUrl, washOrder.id]);

    const currentIndex = progressStatuses.indexOf(washOrder.status);

    return (
        <>
            <Head title={`Acompanhamento ${washOrder.code} · AutoFlow`} />
            <main className="mx-auto min-h-screen max-w-5xl bg-zinc-50 px-4 py-6 text-zinc-950 sm:px-6 lg:px-8">
                <header className="flex flex-wrap items-start justify-between gap-4 border-b border-zinc-200 bg-white px-4 py-5 sm:rounded-lg sm:border">
                    <div>
                        <img src={logoUrl || '/images/autoflow-logo.png'} alt="AutoFlow" className="max-h-24 w-40 object-contain" />
                        <h1 className="mt-4 text-3xl font-bold sm:text-4xl">
                            {washOrder.vehicle.model} {washOrder.vehicle.color} - {washOrder.vehicle.plate}
                        </h1>
                        <p className="mt-2 text-sm text-zinc-500">Codigo {washOrder.code}</p>
                        {realtimeUpdated && <p className="mt-1 text-xs font-medium text-cyan-700">Atualizado em tempo real.</p>}
                    </div>
                    <div className="rounded-lg border border-cyan-200 bg-cyan-50 px-4 py-3 text-right">
                        <p className="text-sm text-cyan-800">Status atual</p>
                        <p className="mt-1 text-xl font-semibold text-cyan-950">{washOrder.status_label}</p>
                    </div>
                </header>

                <section className="grid gap-4 py-6 md:grid-cols-3">
                    <div className="rounded-lg border border-zinc-200 bg-white p-5">
                        <p className="text-sm text-zinc-500">Previsao</p>
                        <p className="mt-2 text-2xl font-semibold">{washOrder.estimated_completion_at}</p>
                    </div>
                    <div className="rounded-lg border border-zinc-200 bg-white p-5">
                        <p className="text-sm text-zinc-500">Entrada</p>
                        <p className="mt-2 text-2xl font-semibold">{washOrder.entered_at}</p>
                    </div>
                    <div className="rounded-lg border border-zinc-200 bg-white p-5">
                        <p className="text-sm text-zinc-500">Servicos</p>
                        <p className="mt-2 text-2xl font-semibold">{washOrder.services.length}</p>
                    </div>
                </section>

                <section className="rounded-lg border border-zinc-200 bg-white p-5">
                    <h2 className="text-lg font-semibold">Andamento</h2>
                    <div className="mt-5 grid gap-3 md:grid-cols-7">
                        {progressStatuses.map((status, index) => (
                            <ProgressStep
                                key={status}
                                label={statuses[status]}
                                done={currentIndex !== -1 && index <= currentIndex}
                                current={washOrder.status === status}
                            />
                        ))}
                    </div>

                    {washOrder.status === 'cancelado' && (
                        <div className="mt-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">Lavagem cancelada.</div>
                    )}
                    {washOrder.status === 'pronto_para_retirada' && (
                        <div className="mt-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">Veiculo pronto para retirada.</div>
                    )}
                    {washOrder.status === 'entregue' && (
                        <div className="mt-5 rounded-md border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-700">Veiculo entregue.</div>
                    )}
                </section>

                <div className="mt-5 grid gap-5 lg:grid-cols-[1fr_380px]">
                    <section className="rounded-lg border border-zinc-200 bg-white p-5">
                        <h2 className="text-lg font-semibold">Servicos</h2>
                        <div className="mt-4 divide-y divide-zinc-100">
                            {washOrder.services.map((service, index) => (
                                <div key={`${service.name}-${index}`} className="flex items-center justify-between gap-4 py-3">
                                    <p className="font-medium">{service.name}</p>
                                    <p className="text-sm text-zinc-500">{service.estimated_minutes} min</p>
                                </div>
                            ))}
                        </div>
                    </section>

                    <section className="rounded-lg border border-zinc-200 bg-white p-5">
                        <h2 className="text-lg font-semibold">Historico</h2>
                        <div className="mt-4 space-y-4">
                            {washOrder.status_histories.map((history) => (
                                <div key={history.id} className="border-l-2 border-cyan-700 pl-3">
                                    <p className="font-medium">{history.label}</p>
                                    <p className="text-sm text-zinc-500">{history.created_at}</p>
                                </div>
                            ))}
                        </div>
                    </section>
                </div>
            </main>
        </>
    );
}
