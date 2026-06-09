import './bootstrap';

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
