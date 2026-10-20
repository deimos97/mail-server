// Consentimiento de cookies, analítica (PostHog) y medición de campañas (píxel de X).
//
// Reglas (LSSI / guía de cookies de la AEPD):
// - Nada de analítica hasta que el usuario acepta: PostHog ni siquiera se descarga antes.
// - "Rechazar" está al mismo nivel que "Aceptar" y la elección se puede cambiar desde el footer.
// - La elección se guarda 12 meses en la cookie técnica `ugl_consent2` (exenta de consentimiento). La v1
//   (`ugl_consent`) solo cubría análisis: desde que hay píxel de X se vuelve a preguntar.
// - El píxel de X (D-030) tampoco se descarga sin consentimiento; los eventos de alta y compra los deja el
//   servidor en `window.UGL.x.events` y se envían al cargarlo.
// - Grabaciones con todos los campos enmascarados; los eventos nunca llevan el nombre que se escribe.
//
// PostHog va por /ingest (proxy en nginx hacia eu.i.posthog.com) para que no lo bloqueen los adblockers.

const COOKIE = 'ugl_consent2';
const MAX_AGE = 60 * 60 * 24 * 365;

let posthog = null;
const queue = [];

export function getConsent() {
    const match = document.cookie.match(/(?:^|;\s*)ugl_consent2=(accepted|rejected)/);
    return match ? match[1] : null;
}

export function setConsent(value) {
    const secure = location.protocol === 'https:' ? '; Secure' : '';
    document.cookie = `${COOKIE}=${value}; Max-Age=${MAX_AGE}; Path=/; SameSite=Lax${secure}`;

    if (value === 'accepted') {
        startAnalytics();
        startAdPixel();
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
        // Los clics automáticos guardan el texto del botón: los que muestran nombres o direcciones llevan
        // `ph-no-capture` (y `data-ph-mask` para las grabaciones). Por si se escapa alguno, fuera todo texto con @.
        before_send: (event) => {
            if (event?.event === '$autocapture') {
                const hasEmail = (text) => typeof text === 'string' && text.includes('@');
                if (hasEmail(event.properties?.$el_text)) delete event.properties.$el_text;
                for (const element of event.properties?.$elements ?? []) {
                    if (hasEmail(element.$el_text)) delete element.$el_text;
                }
                if (typeof event.properties?.$elements_chain === 'string') {
                    event.properties.$elements_chain = event.properties.$elements_chain.replace(/text="[^"]*@[^"]*"/g, '');
                }
            }
            return event;
        },
    });
    client.opt_in_capturing();
    // Pruebas A/B: la variante de cada experimento va en todos los eventos ($feature/<clave>)
    const experiments = window.UGL?.experiments ?? {};
    if (Object.keys(experiments).length) {
        client.register(Object.fromEntries(Object.entries(experiments).map(([key, variant]) => [`$feature/${key}`, variant])));
    }
    posthog = client;

    while (queue.length) client.capture(...queue.shift());
}

/** Píxel de X: solo con consentimiento y si el servidor lo ha configurado (window.UGL.x). Envía los eventos pendientes. */
let adPixelStarted = false;
export function startAdPixel() {
    const config = window.UGL?.x;
    if (adPixelStarted || !config?.pixel || getConsent() !== 'accepted') return;
    adPixelStarted = true;

    // Código base de X (static.ads-twitter.com/uwt.js), el mismo que da el Gestor de eventos
    !function (e, t, n, s, u, a) {
        e.twq || (s = e.twq = function () { s.exe ? s.exe.apply(s, arguments) : s.queue.push(arguments); },
            s.version = '1.1', s.queue = [], u = t.createElement(n), u.async = !0, u.src = 'https://static.ads-twitter.com/uwt.js',
            a = t.getElementsByTagName(n)[0], a.parentNode.insertBefore(u, a));
    }(window, document, 'script');
    window.twq('config', config.pixel);
    for (const [eventId, params] of config.events ?? []) {
        window.twq('event', eventId, params ?? {});
    }
}
