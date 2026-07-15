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

const maskIndividualDocument = (value) => onlyDigits(value)
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
        cnpj: maskCnpj,
        document: (value) => (onlyDigits(value).length > 11 ? maskCnpj(value) : maskIndividualDocument(value)),
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

const setupGuidedTours = () => {
    const tourScripts = [...document.querySelectorAll('script[data-onboarding-tour]')];

    if (tourScripts.length === 0) {
        return;
    }

    const readCompleted = (key) => {
        try {
            return window.localStorage.getItem(`autoflow.tour.${key}.completed`) === '1';
        } catch (error) {
            return false;
        }
    };

    const markCompleted = (key) => {
        try {
            window.localStorage.setItem(`autoflow.tour.${key}.completed`, '1');
        } catch (error) {
            // Tours remain usable even when localStorage is unavailable.
        }
    };

    const createButton = (tour, start) => {
        if (tour.launcher === false || document.querySelector(`[data-onboarding-tour-launch="${tour.key}"]`)) {
            return;
        }

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'af-tour-launcher';
        button.dataset.onboardingTourLaunch = tour.key;
        button.textContent = 'Ajuda';
        button.addEventListener('click', start);
        document.body.appendChild(button);
    };

    const setupTour = (tour) => {
        if (!tour.key || !Array.isArray(tour.steps)) {
            return;
        }

        const steps = tour.steps
            .map((step) => ({
                ...step,
                element: document.querySelector(step.target),
            }))
            .filter((step) => step.element && step.element.getClientRects().length > 0);

        if (steps.length === 0) {
            return;
        }

        let currentIndex = 0;
        let spotlight = null;
        let tooltip = null;

        const cleanup = () => {
            spotlight?.remove();
            tooltip?.remove();
            spotlight = null;
            tooltip = null;
            document.body.classList.remove('af-tour-active');
            window.removeEventListener('resize', positionCurrentStep);
            window.removeEventListener('scroll', positionCurrentStep, true);
            document.removeEventListener('keydown', handleKeydown);
        };

        const finish = () => {
            markCompleted(tour.key);
            cleanup();
        };

        const handleKeydown = (event) => {
            if (event.key === 'Escape') {
                cleanup();
            }

            if (event.key === 'ArrowRight') {
                event.preventDefault();
                goTo(currentIndex + 1);
            }

            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                goTo(currentIndex - 1);
            }
        };

        const ensureVisible = (element) => {
            element.scrollIntoView({
                behavior: 'smooth',
                block: 'center',
                inline: 'center',
            });
        };

        const positionCurrentStep = () => {
            if (!spotlight || !tooltip) {
                return;
            }

            const step = steps[currentIndex];
            const rect = step.element.getBoundingClientRect();
            const padding = 10;
            const left = Math.max(12, rect.left - padding);
            const top = Math.max(12, rect.top - padding);
            const width = Math.min(window.innerWidth - left - 12, rect.width + padding * 2);
            const height = Math.min(window.innerHeight - top - 12, rect.height + padding * 2);

            spotlight.style.left = `${left}px`;
            spotlight.style.top = `${top}px`;
            spotlight.style.width = `${width}px`;
            spotlight.style.height = `${height}px`;

            const tooltipRect = tooltip.getBoundingClientRect();
            const preferredTop = rect.bottom + 18;
            const fallbackTop = rect.top - tooltipRect.height - 18;
            const tooltipTop = preferredTop + tooltipRect.height + 16 < window.innerHeight
                ? preferredTop
                : Math.max(16, fallbackTop);
            const tooltipLeft = Math.min(
                Math.max(16, rect.left),
                window.innerWidth - tooltipRect.width - 16,
            );

            tooltip.style.left = `${tooltipLeft}px`;
            tooltip.style.top = `${tooltipTop}px`;
        };

        const render = () => {
            const step = steps[currentIndex];
            ensureVisible(step.element);

            if (!spotlight) {
                spotlight = document.createElement('div');
                spotlight.className = 'af-tour-spotlight';
                document.body.appendChild(spotlight);
            }

            if (!tooltip) {
                tooltip = document.createElement('div');
                tooltip.className = 'af-tour-tooltip';
                tooltip.setAttribute('role', 'dialog');
                tooltip.setAttribute('aria-live', 'polite');
                document.body.appendChild(tooltip);
            }

            const isLast = currentIndex === steps.length - 1;

            tooltip.innerHTML = `
                <div class="af-tour-progress">Passo ${currentIndex + 1} de ${steps.length}</div>
                <h2>${step.title || tour.title || 'Conheça esta tela'}</h2>
                <p>${step.body || ''}</p>
                <div class="af-tour-actions">
                    <button type="button" data-tour-prev ${currentIndex === 0 ? 'disabled' : ''}>Anterior</button>
                    <button type="button" data-tour-next>${isLast ? 'Concluir' : 'Próximo'}</button>
                    <button type="button" data-tour-skip>Pular</button>
                </div>
            `;

            tooltip.querySelector('[data-tour-prev]').addEventListener('click', () => goTo(currentIndex - 1));
            tooltip.querySelector('[data-tour-next]').addEventListener('click', () => {
                if (isLast) {
                    finish();
                    return;
                }

                goTo(currentIndex + 1);
            });
            tooltip.querySelector('[data-tour-skip]').addEventListener('click', finish);

            window.setTimeout(positionCurrentStep, 220);
        };

        const goTo = (nextIndex) => {
            if (nextIndex < 0 || nextIndex >= steps.length) {
                return;
            }

            currentIndex = nextIndex;
            render();
        };

        const start = () => {
            cleanup();
            currentIndex = 0;
            document.body.classList.add('af-tour-active');
            window.addEventListener('resize', positionCurrentStep);
            window.addEventListener('scroll', positionCurrentStep, true);
            document.addEventListener('keydown', handleKeydown);
            render();
        };

        createButton(tour, start);

        if (!readCompleted(tour.key) && tour.autoStart !== false) {
            window.setTimeout(start, 650);
        }
    };

    tourScripts.forEach((script) => {
        if (script.dataset.tourInitialized === '1') {
            return;
        }

        script.dataset.tourInitialized = '1';

        try {
            setupTour(JSON.parse(script.textContent));
        } catch (error) {
            // Invalid tour definitions should never break the page itself.
        }
    });
};

document.addEventListener('DOMContentLoaded', () => {
    setupRealtimeKanban();
    setupRealtimeTracking();
    setupInputMasks();
    setupViaCep();
    setupGuidedTours();
});

window.addEventListener('autoflow:tours-ready', setupGuidedTours);

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
