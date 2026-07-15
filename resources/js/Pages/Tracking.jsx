import { Head, useForm, usePage } from '@inertiajs/react';
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

export default function Tracking({ washOrder: initialWashOrder, loyalty: initialLoyalty, statuses, progressStatuses, feedUrl, reviewUrl, logoUrl, onboardingTour }) {
    const [washOrder, setWashOrder] = useState(initialWashOrder);
    const [loyalty, setLoyalty] = useState(initialLoyalty);
    const [realtimeUpdated, setRealtimeUpdated] = useState(false);
    const { props } = usePage();
    const reviewForm = useForm({
        rating: '',
        comment: '',
        publish_consent: false,
    });

    const refreshFeed = async () => {
        const { data } = await window.axios.get(feedUrl);
        setWashOrder(data.washOrder);
        setLoyalty(data.loyalty);
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

    const currentIndex = progressStatuses.indexOf(washOrder.status);
    const hasReviewErrors = Boolean(reviewForm.errors.review || reviewForm.errors.rating || reviewForm.errors.comment || reviewForm.errors.publish_consent);

    const submitReview = (event) => {
        event.preventDefault();

        reviewForm.post(reviewUrl, {
            preserveScroll: true,
            onSuccess: () => {
                reviewForm.reset();
                refreshFeed();
            },
        });
    };

    return (
        <>
            <Head title={`Acompanhamento ${washOrder.code} · AutoFlow`} />
            <main className="mx-auto min-h-screen max-w-5xl bg-zinc-50 px-4 py-6 text-zinc-950 sm:px-6 lg:px-8">
                <header data-tour="tracking-header" className="flex flex-wrap items-start justify-between gap-4 border-b border-zinc-200 bg-white px-4 py-5 sm:rounded-lg sm:border">
                    <div>
                        <img src={logoUrl || '/images/autoflow-logo.png'} alt="AutoFlow" className="max-h-24 w-40 object-contain" />
                        <h1 className="mt-4 text-3xl font-bold sm:text-4xl">
                            {washOrder.vehicle.model} {washOrder.vehicle.color} - {washOrder.vehicle.plate}
                        </h1>
                        <p className="mt-2 text-sm text-zinc-500">Código {washOrder.code}</p>
                        {realtimeUpdated && <p className="mt-1 text-xs font-medium text-cyan-700">Atualizado em tempo real.</p>}
                    </div>
                    <div className="rounded-lg border border-cyan-200 bg-cyan-50 px-4 py-3 text-right">
                        <p className="text-sm text-cyan-800">Status atual</p>
                        <p className="mt-1 text-xl font-semibold text-cyan-950">{washOrder.status_label}</p>
                    </div>
                </header>

                <section data-tour="tracking-summary" className="grid gap-4 py-6 md:grid-cols-3">
                    <div className="rounded-lg border border-zinc-200 bg-white p-5">
                        <p className="text-sm text-zinc-500">Previsão</p>
                        <p className="mt-2 text-2xl font-semibold">{washOrder.estimated_completion_at}</p>
                    </div>
                    <div className="rounded-lg border border-zinc-200 bg-white p-5">
                        <p className="text-sm text-zinc-500">Entrada</p>
                        <p className="mt-2 text-2xl font-semibold">{washOrder.entered_at}</p>
                    </div>
                    <div className="rounded-lg border border-zinc-200 bg-white p-5">
                        <p className="text-sm text-zinc-500">Serviços</p>
                        <p className="mt-2 text-2xl font-semibold">{washOrder.services.length}</p>
                    </div>
                </section>

                {loyalty?.enabled && (
                    <section data-tour="tracking-loyalty" className="mb-5 rounded-lg border border-fuchsia-200 bg-white p-5">
                        <div className="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p className="text-xs font-black uppercase tracking-[0.18em] text-fuchsia-700">Programa de fidelidade</p>
                                <h2 className="mt-1 text-lg font-semibold text-zinc-950">Seu progresso</h2>
                                <p className="mt-1 text-sm text-zinc-500">{loyalty.label}</p>
                            </div>
                            {loyalty.has_active_coupon ? (
                                <span className="rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-700">Cupom disponível</span>
                            ) : (
                                <span className="rounded-full bg-fuchsia-100 px-3 py-1 text-xs font-black text-fuchsia-700">
                                    Faltam {loyalty.remaining}
                                </span>
                            )}
                        </div>

                        <div className="mt-4 rounded-2xl bg-fuchsia-50 p-4">
                            <div className="flex items-center justify-between gap-3">
                                <p className="text-sm font-bold text-fuchsia-950">
                                    {loyalty.current}/{loyalty.threshold} lavagens válidas
                                </p>
                                <p className="text-sm font-black text-fuchsia-700">
                                    {loyalty.remaining === 0 ? 'Benefício conquistado' : `${loyalty.remaining} para o próximo cupom`}
                                </p>
                            </div>
                            <div className="mt-3 h-3 overflow-hidden rounded-full bg-white">
                                <div className="h-full rounded-full bg-fuchsia-600" style={{ width: `${Math.min(100, loyalty.percent)}%` }} />
                            </div>
                        </div>

                        {loyalty.coupons?.length > 0 && (
                            <div className="mt-4 grid gap-3 md:grid-cols-3">
                                {loyalty.coupons.map((coupon) => (
                                    <div key={coupon.id} className="rounded-2xl border border-fuchsia-100 bg-fuchsia-50 px-4 py-3">
                                        <p className="text-xs font-black uppercase tracking-[0.14em] text-fuchsia-700">Cupom ativo</p>
                                        <p className="mt-1 font-black text-zinc-950">{coupon.code}</p>
                                        <p className="mt-1 text-sm font-semibold text-zinc-600">{coupon.benefit}</p>
                                        {coupon.expires_at && <p className="mt-1 text-xs text-zinc-500">Vence em {coupon.expires_at}</p>}
                                    </div>
                                ))}
                            </div>
                        )}
                    </section>
                )}

                <section data-tour="tracking-progress" className="rounded-lg border border-zinc-200 bg-white p-5">
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
                        <div className="mt-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">Veículo pronto para retirada.</div>
                    )}
                    {washOrder.status === 'entregue' && (
                        <div className="mt-5 rounded-md border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-700">Veículo entregue.</div>
                    )}
                </section>

                {props.flash?.status && (
                    <div className="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                        {props.flash.status}
                    </div>
                )}

                {(washOrder.review?.can_review || washOrder.review?.submitted) && (
                    <section data-tour="tracking-review" className="mt-5 rounded-lg border border-zinc-200 bg-white p-5">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p className="text-xs font-black uppercase tracking-[0.18em] text-cyan-700">Avaliação</p>
                                <h2 className="mt-1 text-lg font-semibold">Como foi sua experiência?</h2>
                                <p className="mt-1 text-sm text-zinc-500">Seu depoimento ajuda outros clientes a escolherem o lava-rápido com mais confiança.</p>
                            </div>
                            {washOrder.review?.submitted && (
                                <span className="rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-700">Depoimento enviado</span>
                            )}
                        </div>

                        {washOrder.review?.submitted ? (
                            <div className="mt-4 rounded-2xl border border-emerald-100 bg-emerald-50 p-4">
                                <p className="text-sm font-black text-emerald-900">{'★'.repeat(washOrder.review.rating || 0)}{'☆'.repeat(5 - (washOrder.review.rating || 0))}</p>
                                <p className="mt-2 text-sm leading-6 text-emerald-900">{washOrder.review.comment}</p>
                            </div>
                        ) : (
                            <form onSubmit={submitReview} className="mt-4 space-y-4">
                                {hasReviewErrors && (
                                    <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
                                        {reviewForm.errors.review || reviewForm.errors.rating || reviewForm.errors.comment || reviewForm.errors.publish_consent}
                                    </div>
                                )}

                                <div>
                                    <span className="text-sm font-bold text-zinc-700">Nota</span>
                                    <div className="mt-2 flex flex-wrap gap-2">
                                        {[1, 2, 3, 4, 5].map((rating) => (
                                            <button
                                                key={rating}
                                                type="button"
                                                onClick={() => reviewForm.setData('rating', rating)}
                                                className={`rounded-xl border px-3 py-2 text-2xl font-black transition ${
                                                    Number(reviewForm.data.rating) >= rating
                                                        ? 'border-amber-300 bg-amber-50 text-amber-500 shadow-sm'
                                                        : 'border-zinc-200 bg-white text-zinc-300 hover:border-amber-200 hover:text-amber-300'
                                                }`}
                                                aria-label={`Nota ${rating}`}
                                                aria-pressed={Number(reviewForm.data.rating) >= rating}
                                            >
                                                ★
                                            </button>
                                        ))}
                                    </div>
                                </div>

                                <label className="block">
                                    <span className="text-sm font-bold text-zinc-700">Depoimento</span>
                                    <textarea
                                        value={reviewForm.data.comment}
                                        onChange={(event) => reviewForm.setData('comment', event.target.value)}
                                        rows="4"
                                        maxLength="500"
                                        className="mt-2 w-full rounded-xl border border-zinc-200 px-3 py-2 text-sm shadow-sm focus:border-cyan-600 focus:outline-none focus:ring-2 focus:ring-cyan-100"
                                        placeholder="Conte rapidamente como foi o atendimento."
                                    />
                                </label>

                                <label className="flex items-start gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 p-4 text-sm text-zinc-700">
                                    <input
                                        type="checkbox"
                                        checked={reviewForm.data.publish_consent}
                                        onChange={(event) => reviewForm.setData('publish_consent', event.target.checked)}
                                        className="mt-1 rounded border-zinc-300 text-cyan-700"
                                    />
                                    <span>Autorizo publicar meu depoimento na página pública desta unidade.</span>
                                </label>

                                <button
                                    type="submit"
                                    disabled={reviewForm.processing}
                                    className="rounded-xl bg-cyan-700 px-5 py-3 text-sm font-black text-white shadow-lg shadow-cyan-900/20 hover:bg-cyan-800 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    {reviewForm.processing ? 'Enviando...' : 'Enviar avaliação'}
                                </button>
                            </form>
                        )}
                    </section>
                )}

                <div className="mt-5 grid gap-5 lg:grid-cols-[1fr_380px]">
                    <section data-tour="tracking-services" className="rounded-lg border border-zinc-200 bg-white p-5">
                        <h2 className="text-lg font-semibold">Serviços</h2>
                        <div className="mt-4 divide-y divide-zinc-100">
                            {washOrder.services.map((service, index) => (
                                <div key={`${service.name}-${index}`} className="flex items-center justify-between gap-4 py-3">
                                    <p className="font-medium">{service.name}</p>
                                    <p className="text-sm text-zinc-500">{service.estimated_minutes} min</p>
                                </div>
                            ))}
                        </div>
                    </section>

                    <section data-tour="tracking-history" className="rounded-lg border border-zinc-200 bg-white p-5">
                        <h2 className="text-lg font-semibold">Histórico</h2>
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
