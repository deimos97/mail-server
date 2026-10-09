import Alpine from 'alpinejs';
import { mountFlag } from './flag';
import { getConsent, setConsent, startAnalytics, track } from './analytics';

/**
 * Estado compartido de la landing: el nombre elegido en el hero lo leen las tarjetas de planes.
 * `check` es la última respuesta de /api/availability.
 */
Alpine.store('signup', {
    localPart: '',
    domain: null,
    check: null,

    get email() {
        return this.check?.available ? this.check.email : null;
    },
    get needsPaidPlan() {
        return Boolean(this.check?.available && this.check.requires_paid_plan);
    },
});

/**
 * Input nombre@dominio: comprueba disponibilidad mientras se escribe.
 * En la landing, al enviar baja a los planes; con { native: true } (en el alta) envía el formulario.
 */
Alpine.data('nameField', (domains, options = {}) => ({
    local: options.initial ?? '',
    domain: domains[0] ?? null,
    domains,
    state: 'idle',          // idle · checking · done · error · limited
    result: null,
    controller: null,
    timer: null,

    init() {
        this.$watch('local', () => this.schedule());
        this.$watch('domain', () => this.schedule());
        if (this.local) this.schedule();
    },

    schedule() {
        clearTimeout(this.timer);
        this.controller?.abort();
        const store = Alpine.store('signup');
        store.localPart = this.local;
        store.domain = this.domain;
        store.check = null;

        if (this.local.trim() === '') {
            this.state = 'idle';
            this.result = null;
            return;
        }
        this.state = 'checking';
        this.timer = setTimeout(() => this.check(), 300);
    },

    async check() {
        this.controller = new AbortController();
        const params = new URLSearchParams({ local: this.local, domain: this.domain ?? '' });
        try {
            const response = await fetch(`/api/availability?${params}`, {
                headers: { Accept: 'application/json' },
                signal: this.controller.signal,
            });
            if (response.status === 429) {
                this.state = 'limited';
                return;
            }
            if (!response.ok) throw new Error(response.status);
            const previous = this.result?.status;
            this.result = await response.json();
            this.state = 'done';
            if (this.result.status !== previous) {
                track('name_checked', { status: this.result.status, requires_paid_plan: this.result.requires_paid_plan });
            }
            Alpine.store('signup').check = this.result;
            window.dispatchEvent(new CustomEvent('ugl:name-checked', { detail: this.result }));
        } catch (error) {
            if (error.name !== 'AbortError') this.state = 'error';
        }
    },

    pick(suggestion) {
        track('name_suggestion_used');
        this.local = suggestion;
        this.$refs.input.focus();
    },

    submit() {
        if (this.result?.available) {
            track('name_chosen', { requires_paid_plan: this.result.requires_paid_plan });
            if (options.native) {
                this.$root.submit();
                return;
            }
            document.getElementById('planes')?.scrollIntoView({ behavior: 'smooth' });
            window.dispatchEvent(new CustomEvent('ugl:name-chosen', { detail: this.result }));
        } else {
            this.$refs.input.focus();
        }
    },

    euros(cents) {
        return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(cents / 100);
    },
}));

/** Banner de cookies. Se abre si no hay elección y se puede reabrir desde el footer. */
Alpine.data('consentBanner', () => ({
    open: getConsent() === null,
    accept() {
        setConsent('accepted');
        this.open = false;
    },
    reject() {
        setConsent('rejected');
        this.open = false;
    },
}));

// Clic en un plan (enlaces con data-plan)
document.addEventListener('click', (event) => {
    const link = event.target.closest('[data-plan]');
    if (!link) return;
    track('plan_selected', {
        plan: link.dataset.plan,
        is_free: link.dataset.free === '1',
        has_offer: link.dataset.offer === '1',
        has_name: Boolean(Alpine.store('signup').email),
    });
});

// "Pídele ayuda a una IA" en la configuración de apps (solo qué asistente; nunca los datos)
window.addEventListener('ugl:ai-help', (event) => track('ai_help_clicked', { provider: event.detail.provider }));

window.Alpine = Alpine;
Alpine.start();
startAnalytics();

// La bandera arranca después del primer pintado: el texto y el input son lo que cuenta para el LCP.
const flag = document.querySelector('[data-flag]');
if (flag) {
    const start = () => mountFlag(flag).catch(() => {});
    'requestIdleCallback' in window ? requestIdleCallback(start, { timeout: 1500 }) : setTimeout(start, 200);
}
