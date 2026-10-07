# Roadmap del front-end

Cada fase deja algo que funciona en producción. Marca las casillas al terminar y mueve la etiqueta **EN CURSO** a la fase activa. Las referencias `D-NNN` están en [DECISIONS.md](DECISIONS.md); el detalle técnico, en [front/ARCHITECTURE.md](front/ARCHITECTURE.md).

Estado: **Fase 2 — EN CURSO**. Fase 0 cerrada el 2026-10-05; Fase 1 cerrada en lo técnico el 2026-10-06 (quedan los textos definitivos y el calentamiento del dominio, a cargo del usuario).

## Decisiones tomadas

| # | Decisión | Fecha |
|---|---|---|
| D-001 | Stack: **Laravel 13 + Cashier (Stripe) + Filament**, para simplificar el despliegue. Se eligió la 13 frente a la 12 (que solo tiene parches de seguridad hasta 02/2027); la 13 pide PHP 8.3, el del servidor. | 2026-10-05 |
| D-002 | La web va en el **mismo VPS** que el correo, aislada (usuario, pool FPM y usuario de BD propios). Lo de separarla se estudiará en el futuro. | 2026-10-05 |
| D-003 | **Monorepo**: `portal/`, `server/` (configuración versionada del servidor **sin secretos**), `docs/`. | 2026-10-05 |
| D-004 | **PostHog Cloud EU** mientras sea gratis; el objetivo a largo plazo es autoalojarlo. | 2026-10-05 |
| D-005 | Reglas de nombre iniciales `a-z 0-9 . - _`, sin punto al principio/final ni `..`, reservados + ofensivos, **todo configurable desde el admin**. Nombres cortos con **sobrecoste periódico** por tramos (inicial: 1–2 y 3–4 caracteres; 5+ sin sobrecoste; importes en el admin), **solo con plan de pago**: siempre se paga más por ese nombre. Mínimo de longitud 1. Lista `reserved_names` con nombres premium (`hola@`, `madrid@`…) guardados para la futura tienda. | 2026-10-05 |
| D-006 | Gratis sin verificar: **recibe y lee, pero no envía**. De pago: todo activo desde que paga. | 2026-10-05 |
| D-007 | Si cambia el precio de un plan, los clientes existentes **conservan su precio**; las migraciones se hacen a mano y con aviso. | 2026-10-05 |
| D-008 | Bandera **oficial de España**, sin connotación política: el mensaje es "producto nacional / Made in Spain". La web es 100 % apolítica. Técnica: shader WebGL + imagen de respaldo. Sin escudo de momento (más minimalista; 2026-10-06). | 2026-10-05 |
| D-010 | Login único por **OAuth2/OIDC**, con la web como proveedor de identidad. | 2026-10-05 |
| D-009 | Ciclo de vida (plazos configurables). Impago: suspensión el día 10 (sin acceso ni envío, sigue recibiendo), borrado del contenido el día 30 con aviso previo, nombre libre el día 90. Cancelación: activo hasta fin de periodo; después pasa a gratis si cabe en la cuota, si no, como un impago. Gratis inactiva: 6 meses sin entrar → aviso → +30 días: suspensión y borrado → +60 días: nombre libre. Borrado voluntario: inmediato, nombre libre a los 90 días. **Nunca** se entrega un nombre sin borrar antes el contenido y pasar la cuarentena. | 2026-10-05 |
| D-011 | En la web se entra con el **email de recuperación o con cualquiera de sus direcciones**. Para las apps de correo, **contraseñas de aplicación por dispositivo desde el principio**: el usuario solo recuerda la contraseña de la web. | 2026-10-05 |
| D-012 | El correo transaccional sale por el **propio Postfix** con `noreply@`. | 2026-10-05 |
| D-013 | **Actualizar ya el servidor a Ubuntu 24.04** (`do-release-upgrade` en el mismo servidor), con lo que llegan Roundcube 1.6 y PHP 8.3 de serie. Se hace antes de construir nada, para empezar sobre una base sólida. | 2026-10-05 |

No hay decisiones abiertas ahora mismo ([DECISIONS.md](DECISIONS.md)).

---

## Fase 0 · Decisiones y cimientos

Objetivo: poder desplegar un "hola mundo" en `https://unagrandeylibre.es` con todo lo aburrido resuelto.

- [x] Documentar visión, arquitectura, decisiones y roadmap (`docs/`, `AGENTS.md`)
- [x] Cerrar todas las decisiones de partida (D-001 a D-013)
- [x] Limpiar restos de Sapphira (servicio `whatsapp-statics`, `nginx/conf.d/wordpress.inc`, preferencias apt de Node/N|Solid)
- [x] Actualizar a Ubuntu 24.04 (2026-10-05). Roundcube 1.6.6, PHP 8.3, Rspamd `noble`; detalles y trampas en el README
- [x] Prueba real después del upgrade: login en el webmail, envío a Gmail y respuesta desde Gmail
- [x] Carpeta `server/` con copias versionadas de la configuración (Postfix, Dovecot, Rspamd, nginx, Roundcube, fail2ban, iptables, scripts `mail-*`, timers). **Sin secretos**: `.gitignore` y lista de exclusiones (`des_key`, contraseñas de BD, `*.env`, `restic.pass`, claves DKIM/TLS, hashes); los valores sensibles se sustituyen por `__REDACTED__`. Revisar con `gitleaks` antes de cada commit (hook pre-commit). Script `server/pull-config.sh` que las actualiza desde el servidor.
- [x] Verificar que Dovecot acepta hashes bcrypt `$2y$` de PHP (`doveadm pw -t`): sí, coste 10 y 12, con prefijo `{BLF-CRYPT}`; también `{ARGON2ID}`
- [x] Pool FPM `portal` (PHP 8.3) y usuario de sistema `portal`
- [x] BD `portal` y usuario MariaDB `portal` con permisos mínimos sobre `mailserver` (comprobado: no puede borrar buzones, tocar dominios ni leer `roundcube`)
- [x] nginx + certificado propio de la web (`unagrandeylibre.es`), separado del del correo
- [x] Registros DNS de `www`, `autoconfig` y `autodiscover` (solo DNS, sin proxy), certificado ampliado, `www` → apex
- [x] Esqueleto Laravel 13 en `portal/`, despliegue (`portal/deploy.sh` → `portal-deploy` en el servidor), `.env` solo en el servidor
- [x] Añadir BD `portal` y `/var/www/portal/shared` al backup, y la web (certificado + `/up`) al `mail-monitor`
- [x] Worker de colas (`portal-queue.service`) y scheduler de Laravel (`portal-schedule.timer`, cada minuto); el deploy reinicia el worker y el monitor vigila ambos
- [x] Cuentas: Stripe (modo test, clave comprobada contra la API), PostHog EU (clave de proyecto `phc_`, la única que puede ir al navegador), Cloudflare Turnstile. Claves solo en `/var/www/portal/shared/.env`; qué es cada una en `portal/.env.example`. Tras editar el `.env`: `sudo -u portal php8.3 artisan optimize` en `current`

## Fase 1 · Landing + planes desde BD

Objetivo: la página pública, medida, lista para recibir tráfico (aunque el alta aún no esté).

### Calentamiento del dominio (empieza ya, en paralelo)

Con SPF, DKIM y DMARC en PASS y un 10/10 en mail-tester, Gmail seguía mandando los correos a spam el 2026-10-05: el dominio y la IP no tienen reputación todavía. **No se lanza publicidad ni se abren altas hasta que Gmail entregue en la bandeja de entrada**: si los primeros usuarios ven sus correos en spam, se van.

- [x] Alta en **Google Postmaster Tools** (dominio `unagrandeylibre.es`) y **Microsoft SNDS** (IP `128.140.5.221`); revisar la reputación cada semana
- [ ] Buzón real para el calentamiento (no `test@`), con nombre visible en la identidad de Roundcube. Se creará como cuenta gratis con el propio front al probar la Fase 2. Al hacerlo, **redirigir `postmaster@` y `abuse@`** (hoy son alias de `test@`) antes de retirar `test@`
- [x] `postmaster@` y `abuse@` operativos, como alias de `test@` (el DMARC ya manda informes a `postmaster@`)
- [ ] 2–4 semanas de uso real: correos normales (varias frases, sin enlaces al principio) a Gmail, Outlook y otros proveedores, **con respuestas** de vuelta; marcar "No es spam" y añadir a contactos cuando caiga en spam
- [ ] Subir el volumen poco a poco; nada de envíos masivos
- [ ] Criterio de salida: Postmaster Tools con reputación de dominio e IP "Media" o mejor, y correos nuevos llegando a la bandeja de entrada de Gmail y Outlook sin intervención

- [x] Migraciones: `plans`, `plan_offers`, `reserved_names`, `name_rules`, `name_price_tiers`; columnas `public_signup` y `sort_order` en `mailserver.domains` (`server/sql/2026-10-06-domains-public-signup.sql`, aplicada como root)
- [x] Admin Filament 5 en `/admin` (2FA obligatoria; solo `is_admin`): planes (gratis/pago, activación absoluta o programada, destacado, orden), ofertas programables, dominios, nombres reservados, reglas de nombre y tramos de precio por longitud. Alta del admin: `portal:make-admin`
- [x] Hero con la bandera de España ondeando (WebGL propio en `resources/js/flag.js`, fondo CSS de respaldo, pausa fuera de pantalla, `prefers-reduced-motion`), mensaje "producto nacional", e input `nombre @dominio` con comprobación en vivo y sugerencias
- [x] Escudo: **sin escudo por ahora** (decisión del usuario, estética minimalista). Si se quiere más adelante, basta con añadir `public/img/escudo-espana.svg`
- [x] Endpoint de disponibilidad `GET /api/availability?local=&domain=` (`App\Services\NameAvailability`): reglas configurables, reservados, buzones y alias, sobrecoste por longitud, sugerencias (incluida la versión sin tildes), 30/min y 500/día por IP
- [x] Sección de planes renderizada desde BD (precio con IVA, tachado si hay oferta, duración de la oferta, destacado; el plan gratis se desactiva si el nombre es corto)
- [x] Bloques informativos y FAQ (textos en `portal/config/landing.php`, **provisionales**) + footer con enlaces legales (páginas "en preparación" hasta la Fase 5)
- [ ] Textos definitivos de la landing (hero, bloques, FAQ) — los pone el usuario
- [x] Banner de consentimiento (Aceptar/Rechazar con la misma relevancia; reabrible desde el footer) + PostHog solo tras aceptar, por `/ingest` (proxy nginx): autocaptura, páginas vistas, mapas de calor, grabaciones con campos y direcciones enmascarados, y eventos `name_checked`, `name_suggestion_used`, `name_chosen`, `plan_selected`
- [x] Captura de UTM y click IDs (`gclid`, `fbclid`, `msclkid`, `ttclid`…) y referrer externo en sesión, primer contacto (`CaptureAttribution`); se guardará con el alta en la Fase 2
- [x] SEO: metadatos, Open Graph con imagen propia (`public/img/og.png`), favicon e icono de iPhone, JSON-LD (`Organization`, `WebSite`, `Product`/`Offer` por plan con el precio de la BD, `FAQPage`), `sitemap.xml`, `robots.txt`, `llms.txt` (los dos últimos generados desde la BD)
- [x] Lighthouse móvil (producción, 2026-10-06): rendimiento 97–98, accesibilidad 100, buenas prácticas 100, SEO 66 **solo por el `noindex`** previo al lanzamiento (pasará a ~100 con `APP_INDEXABLE=true`). LCP 2,2 s, TBT 20–80 ms, CLS 0

## Fase 2 · Alta gratuita de punta a punta (incluye cuenta mínima y login único)

Objetivo: alguien llega por un anuncio y sale con una cuenta completa: buzón, webmail sin volver a loguearse y el móvil configurado; y puede volver otro día a conectar más dispositivos. **No se abren altas hasta acabar esta fase.** (Decisión 2026-10-07: se traen aquí el login, un "Mi cuenta" mínimo y el login único, que estaban en la Fase 3; con contraseñas por dispositivo, sin ellos no habría forma de entrar al webmail ni de volver.)

### A · Cimientos en el servidor de correo
- [x] `mailserver.mailboxes`: columnas `user_id`, `plan_id`, `status` y `can_send`; `password_query` de Dovecot exige `status = 'active'` (suspendido = recibe pero no entra) — `server/sql/2026-10-07-mailboxes-portal-y-app-passwords.sql`
- [x] Contraseñas por dispositivo: tabla `mailserver.app_passwords` (selector de 6 caracteres + hash) + segundo `passdb` en Dovecot; probado en IMAP y SMTP, con revocadas, incorrectas y suspendidos
- [x] Bloqueo de envío hasta verificar el email de recuperación (`can_send`), en Postfix (`check_sasl_access`, 587 y 465)
- [ ] Correo transaccional por el propio Postfix con `noreply@`, con excepción en el ratelimit de Rspamd; Laravel enviando por SMTP

### B · El alta
- [ ] Reserva temporal del nombre durante el onboarding
- [ ] Paso 1: email de recuperación + contraseña + Turnstile; bloqueo de emails desechables; límites por IP
- [ ] Verificación del email (enlace + código)
- [ ] Paso 2: elección de plan (solo gratis habilitado en esta fase)
- [ ] Provisión del buzón (`INSERT` en `mailserver.mailboxes` con `user_id`, `plan_id`, cuota, `tier`, `can_send=0` y contraseña interna aleatoria)
- [ ] Guardar con el usuario la atribución de campaña (`CaptureAttribution`)

### C · Cuenta mínima
- [ ] Login en la web (email de recuperación o cualquiera de sus direcciones), recuperar contraseña, cerrar sesión
- [ ] "Mi cuenta" mínimo: su buzón y "Conectar un dispositivo" (contraseña generada, se muestra una vez)

### D · Login único con el webmail
- [ ] *Spike* de OAuth2 (Laravel Passport como proveedor + Roundcube 1.6 + Dovecot `passdb oauth2`)
- [ ] Implementar el login único: "Abrir mi correo" entra al webmail sin contraseña

### E · Clientes de correo
- [ ] Pantalla "¡Listo!": "Abrir mi correo" (login único) y "Configura tu móvil" (contraseña del dispositivo o perfil `.mobileconfig` que la lleva dentro)
- [ ] Autoconfiguración: `autoconfig` XML, Autodiscover, registros SRV, perfil `.mobileconfig`
- [ ] Tutoriales con capturas: iPhone, Android/Gmail, Outlook, Thunderbird

### F · Medición y lanzamiento
- [ ] Evento de servidor `signup_completed` a PostHog (para todos; las conversiones a Meta/Google, solo con consentimiento)
- [ ] Confirmar que el calentamiento del dominio (Fase 1) ha cumplido su criterio de salida antes de abrir altas
- [ ] Al abrir altas: `APP_INDEXABLE=true` en el `.env` del servidor (hasta entonces la web lleva `noindex`) y quitar la página provisional de `/alta`

## Fase 3 · Área de cliente completa

- [ ] "Mis buzones": plan, uso de cuota (dict de cuota de Dovecot en MariaDB), estado
- [ ] Gestión de dispositivos conectados: listar, renombrar, revocar, último uso
- [ ] Añadir otro buzón (varios por usuario)
- [ ] Plugin/skin de Roundcube: logo, enlace "Mi cuenta"; desactivar cambio de contraseña en Roundcube
- [ ] Cambiar email de recuperación, exportar correo, borrar cuenta (vía script privilegiado `mail-provision`)

## Fase 4 · Planes de pago (Stripe)

- [ ] Sincronización planes/ofertas ↔ Stripe desde el admin (Product, Price, Coupon); cada cambio de precio crea un `Price` nuevo y los clientes existentes conservan el suyo
- [ ] Sobrecoste de nombre corto como línea periódica extra en la suscripción (solo planes de pago)
- [ ] Stripe Checkout en el onboarding y al cambiar de plan; desistimiento de 14 días
- [ ] Crear el webhook con `php artisan cashier:webhook` (URL `https://unagrandeylibre.es/stripe/webhook` y eventos de Cashier) y pegar el `whsec_` en `STRIPE_WEBHOOK_SECRET`; no crearlo a mano antes de tener Cashier desplegado
- [ ] Webhooks idempotentes (`checkout.session.completed`, `invoice.*`, `customer.subscription.*`)
- [ ] Stripe Customer Portal: tarjetas, facturas, cancelación
- [ ] Ciclo de vida según D-009: impago → aviso → suspensión → borrado → cuarentena del nombre → nombre libre (timer que ejecuta `mail-provision`)
- [ ] Política de inactividad de cuentas gratis (según D-009)
- [ ] Límites de envío por plan en Rspamd según `tier` (hasta que haya planes de pago, el límite global de 40/h por usuario es el del plan gratis)
- [ ] Subir/bajar de plan ajusta cuota, `tier` y límites al momento
- [ ] IVA (Stripe Tax o fijo) y facturas; revisar Verifactu con la gestoría
- [ ] Evento de servidor `subscription_started` + conversión de pago a las plataformas de anuncios
- [ ] Monitorizar webhooks fallidos

## Fase 5 · Optimización y crecimiento

- [ ] Textos legales base (aviso legal, privacidad, cookies, condiciones, uso aceptable) — borrador para revisar con asesoría. **Ojo:** la ley (LSSI/RGPD) exige aviso legal, privacidad y cookies publicados antes de recoger datos de usuarios o activar analítica con cookies; tenerlos listos antes de abrir altas al público.
- [ ] Anuncio de prueba en Meta y Google con la marca, el dominio y la bandera, para comprobar que no los clasifican como contenido político antes de lanzar campañas. Si hay problemas, valorar una comunicación más neutra
- [ ] PostHog: activar Session replay y Heatmaps en el proyecto, añadir `https://unagrandeylibre.es` a Authorized URLs y crear el embudo `$pageview → name_checked → name_chosen → plan_selected → … → signup_completed` (Product analytics → New insight → Funnel). Pendiente de hacer juntos
- [ ] Dashboard de embudo en PostHog; revisar mapas de calor y grabaciones de las primeras campañas
- [ ] Primeros A/B: texto del hero, orden/precio de planes, CTA
- [ ] Contenido de los bloques informativos y FAQ (SEO)
- [ ] WebMCP: exponer `comprobar_disponibilidad`, `listar_planes`, `empezar_alta` (comprobar estado del estándar)
- [ ] 2FA (TOTP) y passkeys en la web
- [ ] Backups fuera del servidor (TODO back-end del README) — **antes** de tener clientes de pago
- [ ] Página de estado del servicio

## Futuro (sin fecha)

- [ ] Varios dominios en el alta (`domains.public_signup`), con su autoconfig, SRV y DKIM
- [ ] Store interna de nombres premium y/o dominios (`reserved_names.price_cents`)
- [ ] Dominios propios del cliente
- [ ] Alias, reenvíos, respuesta automática y filtros (managesieve) desde el portal
- [ ] Separar la web a otro servidor
- [ ] Autoalojar PostHog cuando deje de ser gratis o haya máquina
- [ ] Programa de referidos
