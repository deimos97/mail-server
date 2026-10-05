# Producto: landing, onboarding y área de cliente

Qué quiere el usuario que sea la web, recogido de sus propias ideas (octubre 2026). La parte técnica está en [ARCHITECTURE.md](ARCHITECTURE.md); las dudas sin cerrar, en [../DECISIONS.md](../DECISIONS.md).

## Objetivo

Una sola página que se entienda en dos segundos: **consigue tu cuenta `@unagrandeylibre.es`**. Correo hecho en España. Va a recibir tráfico de publicidad, así que tiene que estar medida al detalle (embudos, mapas de calor, grabaciones, A/B).

## Landing (single page)

De arriba abajo:

1. **Hero a pantalla completa.**
   - Fondo: la **bandera oficial de España** ondeando, sin connotación política: el mensaje es "producto nacional / Made in Spain" (la web es 100 % apolítica). Animación WebGL. Tiene que ser bonita *y* ligera: imagen fija de respaldo, se pausa fuera de pantalla y respeta `prefers-reduced-motion`. No puede hundir el LCP.
   - Encima, el input protagonista: `[ tunombre ] @unagrandeylibre.es` + botón **Consigue tu cuenta**.
   - Comprobación de disponibilidad en vivo mientras escribe (✓ disponible / ✗ cogido / ✗ no permitido), con sugerencias si está cogido (`tunombre1`, `tu.nombre`…).
   - **Nombres cortos con sobrecoste:** si el nombre es corto (1–2 o 3–4 caracteres), sale disponible con "+X €/mes" y **solo con planes de pago**. Es una cuota periódica: siempre se paga más por ese nombre. Tramos, precios y reglas se cambian desde el admin. Algunos nombres premium (`hola@`, `madrid@`…) quedan reservados para una futura tienda.
   - El sufijo `@unagrandeylibre.es` es un selector cuando haya más de un dominio; mientras haya uno, se ve como texto fijo.
2. **Planes** (al hacer scroll). Tarjetas `x,xx €/mes` con sus características. **Salen de la BD**, nada escrito a mano en la plantilla. Cada plan puede ser:
   - gratis o de pago;
   - activo o inactivo, de forma absoluta o programada (desde/hasta);
   - con descuento (oferta), también programable: precio tachado + etiqueta ("-50 % lanzamiento", "hasta el 31/12").
   - Un plan puede marcarse como destacado ("el más elegido").
   - Precios siempre **con IVA incluido** (obligatorio en B2C en España).
3. **Bloques informativos** (contenido pendiente): por qué nosotros, privacidad, sin publicidad, servidores en la UE, FAQ… El contenido lo pone el usuario más adelante; la estructura debe permitir añadir bloques fácilmente.
4. **Footer:** aviso legal, privacidad, cookies, condiciones, política de uso aceptable, contacto, estado del servicio (opcional).

## Onboarding (lo mínimo posible)

```
Landing: elige nombre ──▶ 1. Email de recuperación ──▶ 2. Plan ──┬─ gratis ──▶ 3. Confirmar email ──▶ ¡Listo!
                          + contraseña                          └─ pago ───▶ Stripe Checkout ──▶ 3. Confirmar email ──▶ ¡Listo!
```

1. **Email de recuperación + contraseña.** Sirve para recuperar la cuenta y para confirmarla (enlace o código de 6 dígitos). Captcha invisible aquí. El nombre elegido queda **reservado unos minutos** mientras termina el flujo.
2. **Plan.** Se preselecciona el que eligió en la landing, si eligió uno.
   - Gratis → se crea ya.
   - Pago → Stripe Checkout (página alojada por Stripe: tarjeta, Apple Pay, Google Pay, SEPA si se quiere). El buzón se crea cuando Stripe confirma el pago (webhook), no al volver del redirect.
3. **Confirmar email de recuperación.** En las cuentas gratis el buzón ya recibe y se puede leer, pero **no envía** hasta confirmar. Las de pago tienen todo activo desde el pago.
4. **Pantalla "¡Listo!":** botón **Abrir mi correo** (entra al webmail sin volver a loguearse) + **Configúralo en tu móvil** (iPhone / Android / Outlook / Thunderbird: genera una contraseña para ese dispositivo o, en iPhone, un perfil que la lleva dentro) + **Ir a mi cuenta**. El usuario solo tiene que recordar una contraseña: la de la web.

Nada más en el onboarding: ni nombre, ni dirección, ni teléfono. Los datos de facturación los pide Stripe solo si hacen falta.

## Área de cliente ("Mi cuenta")

Una vez dentro:

- **Mis buzones:** lista (normalmente uno), con plan, uso de cuota y estado. Por buzón: abrir webmail (SSO), dispositivos conectados (añadir, revocar), configuración de clientes, cambiar de plan, cancelar.
- **Añadir otro buzón:** mismo flujo que el onboarding, sin el paso de email.
- **Facturación:** tarjetas, facturas y suscripciones → **Stripe Customer Portal** (lo da Stripe hecho).
- **Seguridad:** contraseña del portal, 2FA (TOTP) y passkeys (fase posterior), sesiones abiertas.
- **Datos:** cambiar email de recuperación, exportar correo, borrar cuenta.

## Login único

El usuario se loguea **una vez** en la web y desde ahí entra a cualquiera de sus buzones en el webmail sin volver a escribir contraseña. En el webmail habrá un enlace de vuelta a "Mi cuenta". Detalle en [ARCHITECTURE.md § Login único](ARCHITECTURE.md#login-único-sso-con-el-webmail).

## Configuración en el móvil

Lo más cómodo posible: autoconfiguración donde exista y tutorial con capturas donde no. Detalle en [ARCHITECTURE.md § Autoconfiguración](ARCHITECTURE.md#autoconfiguración-de-clientes-de-correo).

## Medición

- Embudo principal: `visita → escribe nombre → nombre disponible → click CTA → email puesto → plan elegido → (checkout iniciado → pagado) → email confirmado → primer login en webmail/IMAP`.
- Mapas de calor, grabaciones de sesión, A/B tests del hero y de los planes.
- Atribución: UTM y click IDs (`gclid`, `fbclid`…) guardados con el alta, para saber qué campaña trae altas **de pago**, no solo visitas.
- Herramienta: PostHog Cloud EU.

## SEO y agentes de IA

- HTML renderizado en servidor, rápido (Core Web Vitals en verde), metadatos, Open Graph, `sitemap.xml`, `robots.txt`.
- Datos estructurados JSON-LD: `Organization`, `WebSite`, `Product`/`Offer` por plan (precio real desde BD), `FAQPage`.
- `llms.txt` describiendo el servicio y los planes.
- **WebMCP** (`navigator.modelContext`): exponer como herramientas para agentes de IA las acciones de la página (comprobar disponibilidad de un nombre, listar planes, empezar el alta). Es un estándar en propuesta: comprobar su estado al implementarlo.

## Futuro (no ahora)

- Varios dominios (`@otrodominio.es`) para elegir en el alta.
- Store interna de nombres premium y/o dominios.
- Dominios propios del cliente (requiere DKIM por dominio, ver README).
- Alias, reenvíos, respuesta automática desde el portal.
