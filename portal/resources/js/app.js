import Alpine from 'alpinejs';
import { mountFlag } from './flag';

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

/** El input del hero: comprueba disponibilidad mientras se escribe. */
Alpine.data('nameField', (domains) => ({
    local: '',
    domain: domains[0] ?? null,
    domains,
    state: 'idle',          // idle · checking · done · error · limited
    result: null,
    controller: null,
    timer: null,

    init() {
        this.$watch('local', () => this.schedule());
        this.$watch('domain', () => this.schedule());
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
            this.result = await response.json();
            this.state = 'done';
            Alpine.store('signup').check = this.result;
            window.dispatchEvent(new CustomEvent('ugl:name-checked', { detail: this.result }));
        } catch (error) {
            if (error.name !== 'AbortError') this.state = 'error';
        }
    },

    pick(suggestion) {
        this.local = suggestion;
        this.$refs.input.focus();
    },

    submit() {
        if (this.result?.available) {
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

window.Alpine = Alpine;
Alpine.start();

// La bandera arranca después del primer pintado: el texto y el input son lo que cuenta para el LCP.
const flag = document.querySelector('[data-flag]');
if (flag) {
    const start = () => mountFlag(flag).catch(() => {});
    'requestIdleCallback' in window ? requestIdleCallback(start, { timeout: 1500 }) : setTimeout(start, 200);
}
