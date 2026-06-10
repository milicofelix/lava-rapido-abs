import './bootstrap';
import { createInertiaApp } from '@inertiajs/react';
import { createElement } from 'react';
import { createRoot } from 'react-dom/client';

const reloadWithRealtimeNotice = () => {
    const url = new URL(window.location.href);
    url.searchParams.set('realtime', '1');
    window.location.replace(url.toString());
};

const setupRealtimeKanban = () => {
    if (!document.querySelector('[data-realtime-kanban]') || !window.Echo) {
        return;
    }

    window.Echo.channel('wash-orders')
        .listen('.WashOrderStatusChanged', reloadWithRealtimeNotice);
};

const setupRealtimeTracking = () => {
    const tracking = document.querySelector('[data-realtime-tracking]');

    if (!tracking || !window.Echo) {
        return;
    }

    window.Echo.channel(`wash-order.${tracking.dataset.washOrderId}`)
        .listen('.WashOrderStatusChanged', reloadWithRealtimeNotice);
};

document.addEventListener('DOMContentLoaded', () => {
    setupRealtimeKanban();
    setupRealtimeTracking();
});

const inertiaRoot = document.getElementById('app');
const inertiaPage = document.querySelector('script[data-page="app"][type="application/json"]');

if (inertiaRoot && inertiaPage) {
    const pages = import.meta.glob('./Pages/**/*.jsx');

    createInertiaApp({
        resolve: async (name) => (await pages[`./Pages/${name}.jsx`]()).default,
        setup({ el, App, props }) {
            createRoot(el).render(createElement(App, props));
        },
        progress: {
            color: '#0e7490',
        },
    });
}
