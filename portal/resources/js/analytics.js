// Consentimiento de cookies y analítica (PostHog).
//
// Reglas (LSSI / guía de cookies de la AEPD):
// - Nada de analítica hasta que el usuario acepta: PostHog ni siquiera se descarga antes.
// - "Rechazar" está al mismo nivel que "Aceptar" y la elección se puede cambiar desde el footer.
// - La elección se guarda 12 meses en la cookie técnica `ugl_consent` (exenta de consentimiento).
// - Grabaciones con todos los campos enmascarados; los eventos nunca llevan el nombre que se escribe.
//
// PostHog va por /ingest (proxy en nginx hacia eu.i.posthog.com) para que no lo bloqueen los adblockers.

const COOKIE = 'ugl_consent';
const MAX_AGE = 60 * 60 * 24 * 365;

let posthog = null;
const queue = [];

export function getConsent() {
    const match = document.cookie.match(/(?:^|;\s*)ugl_consent=(accepted|rejected)/);
    return match ? match[1] : null;
}

export function setConsent(value) {
    const secure = location.protocol === 'https:' ? '; Secure' : '';
    document.cookie = `${COOKIE}=${value}; Max-Age=${MAX_AGE}; Path=/; SameSite=Lax${secure}`;

    if (value === 'accepted') {
        startAnalytics();
    } else if (posthog) {
        // Si retira el consentimiento: deja de capturar y borra lo que PostHog guardó en el navegador
        posthog.opt_out_capturing();
        posthog.reset();
    }
}

/** Evento de producto. Si no hay consentimiento, se descarta; si PostHog aún carga, espera. */
export function track(event, properties = {}) {
    if (getConsent() !== 'accepted') return;
    posthog ? posthog.capture(event, properties) : queue.push([event, properties]);
}

export async function startAnalytics() {
    const config = window.UGL?.posthog;
    if (posthog || !config?.key || getConsent() !== 'accepted') return;

    const { default: client } = await import('posthog-js');
    client.init(config.key, {
        api_host: '/ingest',
        ui_host: 'https://eu.posthog.com',
        defaults: '2026-08-30',
        person_profiles: 'identified_only',
        persistence: 'localStorage+cookie',
        autocapture: true,
        capture_pageview: true,
        capture_pageleave: true,
        enable_heatmaps: true,
        session_recording: {
            maskAllInputs: true,
            maskTextSelector: '[data-ph-mask]',
        },
    });
    client.opt_in_capturing();
    posthog = client;

    while (queue.length) client.capture(...queue.shift());
}
