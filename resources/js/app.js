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

const onlyDigits = (value) => String(value || '').replace(/\D/g, '');

const maskCep = (value) => onlyDigits(value)
    .slice(0, 8)
    .replace(/^(\d{5})(\d)/, '$1-$2');

const maskCpf = (value) => onlyDigits(value)
    .slice(0, 11)
    .replace(/^(\d{3})(\d)/, '$1.$2')
    .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
    .replace(/^(\d{3})\.(\d{3})\.(\d{3})(\d)/, '$1.$2.$3-$4');

const maskCnpj = (value) => onlyDigits(value)
    .slice(0, 14)
    .replace(/^(\d{2})(\d)/, '$1.$2')
    .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
    .replace(/^(\d{2})\.(\d{3})\.(\d{3})(\d)/, '$1.$2.$3/$4')
    .replace(/^(\d{2})\.(\d{3})\.(\d{3})\/(\d{4})(\d)/, '$1.$2.$3/$4-$5');

const maskPhone = (value) => {
    const digits = onlyDigits(value).slice(0, 11);

    if (digits.length <= 10) {
        return digits
            .replace(/^(\d{2})(\d)/, '($1) $2')
            .replace(/(\d{4})(\d)/, '$1-$2');
    }

    return digits
        .replace(/^(\d{2})(\d)/, '($1) $2')
        .replace(/(\d{5})(\d)/, '$1-$2');
};

const applyInputMask = (input) => {
    const masks = {
        cep: maskCep,
        cpf: maskCpf,
        cnpj: maskCnpj,
        document: (value) => (onlyDigits(value).length > 11 ? maskCnpj(value) : maskCpf(value)),
        phone: maskPhone,
    };
    const mask = masks[input.dataset.mask];

    if (!mask) {
        return;
    }

    input.value = mask(input.value);
};

const setupInputMasks = () => {
    document.querySelectorAll('[data-mask]').forEach((input) => {
        applyInputMask(input);
        input.addEventListener('input', () => applyInputMask(input));
    });
};

const setupViaCep = () => {
    document.querySelectorAll('[data-viacep-trigger]').forEach((input) => {
        input.addEventListener('blur', async () => {
            const cep = onlyDigits(input.value);

            if (cep.length !== 8) {
                return;
            }

            try {
                const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`, {
                    headers: { Accept: 'application/json' },
                });
                const payload = await response.json();

                if (!response.ok || payload.erro) {
                    return;
                }

                const form = input.closest('form') || document;
                const fields = {
                    address: payload.logradouro,
                    district: payload.bairro,
                    city: payload.localidade,
                    state: payload.uf,
                };

                Object.entries(fields).forEach(([field, value]) => {
                    const target = form.querySelector(`[data-address-field="${field}"]`);

                    if (target && value) {
                        target.value = value;
                        target.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                });
            } catch (error) {
                // Keep manual entry available if ViaCEP is unavailable.
            }
        });
    });
};

document.addEventListener('DOMContentLoaded', () => {
    setupRealtimeKanban();
    setupRealtimeTracking();
    setupInputMasks();
    setupViaCep();
});

const inertiaRoot = document.getElementById('app');
const inertiaPage = document.querySelector('script[data-page="app"][type="application/json"]');

if (inertiaRoot && inertiaPage) {
    const pages = {
        Kanban: () => import('./Pages/Kanban.jsx'),
        Tracking: () => import('./Pages/Tracking.jsx'),
    };

    createInertiaApp({
        resolve: async (name) => {
            const page = pages[name];

            if (!page) {
                throw new Error(`Página Inertia não encontrada: ${name}`);
            }

            const module = await page();

            return module.default;
        },
        setup({ el, App, props }) {
            createRoot(el).render(createElement(App, props));
        },
        progress: {
            color: '#0e7490',
        },
    });
}
