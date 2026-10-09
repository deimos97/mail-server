// WebMCP: herramientas para que los agentes de IA del navegador usen la web sin "hacer clics".
// Estándar en borrador (W3C WebML CG, 2026): la API está en document.modelContext; los borradores
// antiguos y algunos navegadores la exponen en navigator.modelContext. Si no existe, no hace nada.

const text = (data) => ({ content: [{ type: 'text', text: typeof data === 'string' ? data : JSON.stringify(data) }] });

async function getJson(url) {
    const response = await fetch(url, { headers: { Accept: 'application/json' } });
    return response.json();
}

const tools = [
    {
        name: 'comprobar_disponibilidad',
        description: 'Comprueba si una dirección de correo @unagrandeylibre.es está libre. Devuelve si está disponible, la dirección normalizada, el motivo si no lo está, el suplemento si es un nombre corto y sugerencias.',
        inputSchema: {
            type: 'object',
            properties: {
                nombre: { type: 'string', description: 'La parte antes de la @ (por ejemplo "lucia.martin")' },
            },
            required: ['nombre'],
        },
        annotations: { readOnlyHint: true },
        execute: async ({ nombre }) => text(await getJson(`/api/availability?local=${encodeURIComponent(nombre)}`)),
    },
    {
        name: 'listar_planes',
        description: 'Lista los planes de correo de unagrandeylibre.es con su precio (IVA incluido), espacio, envíos por hora y ofertas vigentes.',
        inputSchema: { type: 'object', properties: {} },
        annotations: { readOnlyHint: true },
        execute: async () => text(await getJson('/api/plans')),
    },
    {
        name: 'empezar_alta',
        description: 'Abre el alta de una cuenta de correo con el nombre (y opcionalmente el plan) ya elegidos. El usuario termina el alta él mismo: email de recuperación, contraseña y, si es de pago, el pago.',
        inputSchema: {
            type: 'object',
            properties: {
                nombre: { type: 'string', description: 'La parte antes de la @' },
                plan: { type: 'string', description: 'Identificador del plan (de listar_planes), por ejemplo "gratis"' },
            },
            required: ['nombre'],
        },
        execute: async ({ nombre, plan }) => {
            const url = new URL('/alta', location.origin);
            url.searchParams.set('nombre', nombre);
            if (plan) url.searchParams.set('plan', plan);
            setTimeout(() => location.assign(url), 50);
            return text(`Abriendo el alta para ${nombre}@unagrandeylibre.es: ${url}`);
        },
    },
];

export function registerWebMcpTools() {
    const context = document.modelContext ?? navigator.modelContext;
    if (!context?.registerTool) return;
    for (const tool of tools) {
        try {
            context.registerTool(tool);
        } catch (error) {
            console.warn('WebMCP: no se pudo registrar', tool.name, error);
        }
    }
}
