# Roadmap del front-end

Cada fase deja algo que funciona en producción. Marca las casillas al terminar y mueve la etiqueta **EN CURSO** a la fase activa. Las referencias `D-NNN` están en [DECISIONS.md](DECISIONS.md); el detalle técnico, en [front/ARCHITECTURE.md](front/ARCHITECTURE.md).

Estado: **Fase 6 — EN CURSO** (desde el 2026-10-09: todo lo que queda es del dueño). **Fase 7 · Soporte por tickets — cerrada el 2026-10-14**. **Altas abiertas e indexación activada el 2026-10-09**, con Stripe aún en sandbox mientras lo prueban amigos. Fases 4 y 5 cerradas el 2026-10-09. Fase 0 cerrada el 2026-10-05; Fase 1, el 2026-10-06; Fases 2 y 3, el 2026-10-08.

**Todo lo que solo puede hacer el dueño está en la [Fase 6 · Pre-lanzamiento](#fase-6--pre-lanzamiento)**, la última antes de abrir altas: la hará cuando el código esté terminado, probado y ajustado con su feedback. Una IA que retome el proyecto no lo hace por él; como mucho, lo prepara y se lo recuerda.

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
| D-010 | Login único por **OAuth2**, con la web como proveedor de identidad. **Revisado el 2026-10-07 tras el spike:** en vez de Passport, un **proveedor propio mínimo** solo para nuestro Roundcube, porque el token tiene que pertenecer a un buzón (Roundcube y Dovecot entran como `buzón@dominio`) y Passport ata los tokens al usuario de la web. | 2026-10-05 |
| D-009 | Ciclo de vida (plazos configurables). Impago: suspensión el día 10 (sin acceso ni envío, sigue recibiendo), borrado del contenido el día 30 con aviso previo, nombre libre el día 90. Cancelación: activo hasta fin de periodo; después pasa a gratis si cabe en la cuota, si no, como un impago. Gratis inactiva: 6 meses sin entrar → aviso → +30 días: suspensión y borrado → +60 días: nombre libre. Borrado voluntario: inmediato, nombre libre a los 90 días. **Nunca** se entrega un nombre sin borrar antes el contenido y pasar la cuarentena. | 2026-10-05 |
| D-011 | En la web se entra con el **email de recuperación o con cualquiera de sus direcciones**. Para las apps de correo, **contraseñas de aplicación por dispositivo desde el principio**: el usuario solo recuerda la contraseña de la web. | 2026-10-05 |
| D-012 | El correo transaccional sale por el **propio Postfix** con `noreply@`. | 2026-10-05 |
| D-014 | **Un buzón gratis por usuario**; los demás, solo con plan de pago. Tiene que quedar claro en la pantalla de planes para que nadie se atasque sin saber por qué no puede elegir el gratis. | 2026-10-08 |
| D-015 | Copias de seguridad: **retención de ~1 mes para todos** (7 diarias + 4 semanales; antes, también 6 mensuales). El correo de una cuenta borrada desaparece de las copias en ese plazo; la política de privacidad lo dirá. Se acepta tener menos margen para recuperar un problema que se descubra tarde. | 2026-10-08 |
| D-016 | Stripe: **cuenta propia "Servicio Correo Minorista"** dentro del mismo login y de la misma empresa (Tibletech), separada de la de tibletech.com: claves, clientes, productos, webhooks y marca pública propios (`unagrandeylibre.es`). Tibletech es quien vende (no Managed Payments / merchant of record de Stripe). Las cifras se pueden juntar luego con una organización de Stripe. Pendiente del usuario: activar la cuenta (datos fiscales) antes de cobrar de verdad. | 2026-10-08 |
| D-017 | IVA: **21 % fijo, incluido en el precio** (un Tax Rate de Stripe `inclusive`), sin Stripe Tax. Si algún día se superan 10.000 €/año de ventas a consumidores de otros países de la UE, pasar a Stripe Tax (OSS). | 2026-10-08 |
| D-013 | **Actualizar ya el servidor a Ubuntu 24.04** (`do-release-upgrade` en el mismo servidor), con lo que llegan Roundcube 1.6 y PHP 8.3 de serie. Se hace antes de construir nada, para empezar sobre una base sólida. | 2026-10-05 |
| D-018 | **Soporte por tickets** propio en la web (`/soporte`), sin herramientas externas. Las respuestas del usuario por email **entran solas en el ticket**: la web lee el buzón de soporte cada minuto y engancha los correos con `[#NNNN]` en el asunto (reabre el ticket si estaba cerrado). | 2026-10-14 |
| D-019 | Respuestas y autoconfirmaciones salen de un buzón propio **`soporte@unagrandeylibre.es`** mostrado como "Javier · Una Grande y Libre" (no de `javier@`): la web solo lee ese buzón, nunca el personal, y sirve igual si un día atiende más gente. | 2026-10-14 |
| D-020 | Adjuntos del ticket: **solo imágenes, hasta 3 y 8 MB en total**, dentro de los límites actuales de nginx/PHP (12 MB por petición). Para otros ficheros o más pesados, el formulario remite a `https://transfer.tibletech.com/`. | 2026-10-14 |
| D-022 | **Avisos a Telegram** (mismo bot y chat que `mail-monitor`): cada mensaje del cliente en soporte (no nuestras respuestas), y un **resumen diario a las 09:00** desde el último enviado: visitas, altas (gratis/de pago y €/mes), ingresos recurrentes, bajas e impagos, soporte, conversión y origen, correo enviado/recibido con top 3 (sin `soporte@`, `javier@` ni los de sistema), cuotas >90 % y >100 %, salud del correo (rechazos, rebotes, cola, fail2ban, copias), disco y CPU/RAM. | 2026-10-14 |
| D-023 | **Sin calentamiento formal del dominio** como requisito para lanzar publicidad: se quitan de la Fase 6 las semanas de uso con `javier@` y el criterio de salida de Postmaster Tools. Postmaster Tools y SNDS siguen dados de alta para vigilar la reputación. | 2026-10-14 |
| D-024 | Consultas de soporte: se guardan **2 años desde el último mensaje** y se borran con la cuenta (validado por la gestoría). | 2026-10-14 |
| D-021 | Abrir tickets **sin sesión: activado** de entrada (con Turnstile y límite por IP), con un interruptor en el admin para cerrarlo. | 2026-10-14 |

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
- [x] Buzón real para el calentamiento: **`javier@unagrandeylibre.es`**, creado con el propio alta (2026-10-07). `postmaster@` y `abuse@` redirigidos a él (reciben los informes DMARC)
- [x] `postmaster@` y `abuse@` operativos (alias de `javier@` desde el 2026-10-07; antes de `test@`)
- El calentamiento en sí (semanas de uso real con `javier@` y su criterio de salida) se quitó del plan el 2026-10-14 (D-023)

- [x] Migraciones: `plans`, `plan_offers`, `reserved_names`, `name_rules`, `name_price_tiers`; columnas `public_signup` y `sort_order` en `mailserver.domains` (`server/sql/2026-10-06-domains-public-signup.sql`, aplicada como root)
- [x] Admin Filament 5 en `/admin` (2FA obligatoria; solo `is_admin`): planes (gratis/pago, activación absoluta o programada, destacado, orden), ofertas programables, dominios, nombres reservados, reglas de nombre y tramos de precio por longitud. Alta del admin: `portal:make-admin`
- [x] Hero con la bandera de España ondeando (WebGL propio en `resources/js/flag.js`, fondo CSS de respaldo, pausa fuera de pantalla, `prefers-reduced-motion`), mensaje "producto nacional", e input `nombre @dominio` con comprobación en vivo y sugerencias
- [x] Escudo: **sin escudo por ahora** (decisión del usuario, estética minimalista). Si se quiere más adelante, basta con añadir `public/img/escudo-espana.svg`
- [x] Endpoint de disponibilidad `GET /api/availability?local=&domain=` (`App\Services\NameAvailability`): reglas configurables, reservados, buzones y alias, sobrecoste por longitud, sugerencias (incluida la versión sin tildes), 30/min y 500/día por IP
- [x] Sección de planes renderizada desde BD (precio con IVA, tachado si hay oferta, duración de la oferta, destacado; el plan gratis se desactiva si el nombre es corto)
- [x] Bloques informativos y FAQ (textos en `portal/config/landing.php`, **provisionales**) + footer con enlaces legales (páginas "en preparación" hasta la Fase 5)
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
- [x] Correo transaccional por el propio Postfix con `noreply@` (buzón con cuota de 100 MB, `tier=system`; recibe los rebotes), fuera del ratelimit de Rspamd (`whitelisted_user`); Laravel envía por SMTP 587 con DKIM. Contraseña solo en el `.env` del servidor

### B · El alta
- [x] Reserva temporal del nombre durante el alta (15 min, se renueva en cada paso; ligada a un token del alta en la sesión, no al id de sesión). Para otros, el nombre reservado sale como cogido
- [x] Paso 1: email de recuperación (con DNS válido, no desechable, no de nuestros dominios) + contraseña (mín. 10, comprobada contra filtraciones) + aceptar condiciones + Turnstile; 5 altas/hora y 20/día por IP
- [x] Verificación del email: un correo con código de 6 cifras y enlace firmado (sirve en otro dispositivo); 1 h de validez, 5 intentos por código, reenvío limitado
- [x] Paso 2: elección de plan (solo gratis; los de pago, "Muy pronto"); un nombre corto lo explica y ofrece cambiar de nombre
- [x] Provisión del buzón (`INSERT` en `mailserver.mailboxes` con `user_id`, `plan_id`, cuota, `tier`, `can_send=0` y contraseña interna aleatoria), volviendo a comprobar todo justo antes
- [x] Guardar con el usuario la atribución de campaña (`users.attribution`)
- [x] Puerta del alta: cerrada (`SIGNUP_OPEN=false`) salvo con `/alta?acceso=<SIGNUP_PREVIEW_TOKEN>` para probar en producción

### C · Cuenta mínima
- [x] Login en la web (`/entrar`, con el email de recuperación o cualquiera de sus direcciones; mismo error exista o no la cuenta; 5 intentos por cuenta e IP), recuperar contraseña (`/recuperar`, el enlace va siempre al email de recuperación; cierra las demás sesiones), cerrar sesión. Los admins no pueden entrar por aquí (saltarían su 2FA)
- [x] "Mi cuenta" mínimo (`/cuenta`): sus buzones y su estado, dispositivos conectados y "Conectar un dispositivo" (16 caracteres sin ambigüedades; se muestra una vez en la propia respuesta, sin pasar por la sesión; máx. 20 por buzón)
- [x] "Revocar" un dispositivo (traído de la Fase 3, 2026-10-07): Dovecot deja de aceptar su contraseña al momento; una conexión ya abierta (IMAP IDLE) puede durar hasta que el móvil reconecte (~30 min)

### D · Login único con el webmail
- [x] *Spike* de OAuth2: Roundcube 1.6.6 (opciones `oauth_*`, refresco de token, XOAUTH2 en IMAP y SMTP) y Dovecot 2.3.21 (`passdb oauth2` con introspección) lo soportan. Passport descartado (D-010 revisado)
- [x] Login único: "Abrir mi correo" entra al webmail sin contraseña. Proveedor OAuth2 propio en la web (`/oauth/authorize`, `/api/oauth/token|userinfo|introspect`), Roundcube con `oauth_*`, Dovecot con `passdb oauth2` (primero y solo para XOAUTH2/OAUTHBEARER) y PATH_INFO en el nginx del webmail. Probado en producción: web → webmail dentro de la bandeja; token válido entra en IMAP y SMTP, token falso no

### E · Clientes de correo
- [x] Pantalla "¡Listo!": "Abrir mi correo" (login único) y "Configura tu móvil" → "Configura un dispositivo" (`/cuenta/configurar/{buzón}`): se elige la app, se crea su contraseña y se muestran sus pasos
- [x] Perfil `.mobileconfig` para iPhone/iPad/Mac con la contraseña dentro (cifrado en caché 10 min, una descarga, solo su dueño; validado con `plutil`)
- [x] `autoconfig` (Thunderbird, apps de Android; en `autoconfig.` y en `/.well-known/autoconfig/`) y Autodiscover POX (Outlook), sin sesión; probados en producción
- [x] Registros SRV en Cloudflare (comprobados el 2026-10-08): `_imaps._tcp` 0 1 993, `_submissions._tcp` 0 1 465 y `_submission._tcp` 0 1 587, todos hacia `mail.unagrandeylibre.es`
- [x] Perfil de Apple firmado (2026-10-08) con el certificado de la web, al descargarlo, por `mail-provision sign-profile` (sudo solo para ese script). Si la firma falla, sale sin firmar
- [x] ~~Enviar la configuración a la ISPDB de Thunderbird~~ — descartado (2026-10-08): la ISPDB es para proveedores grandes que no publican su propio autoconfig, y prefieren que cada proveedor lo sirva él mismo, como ya hacemos
- [x] Guías públicas en `/ayuda/configurar` (iPhone/iPad, Mac, Android/Gmail, Outlook, Thunderbird, otra app), con los mismos pasos que ve el usuario al configurar
- [x] "Pídele ayuda a una IA" en los pasos manuales de "Configura un dispositivo" (2026-10-09; componente `x-ai-help`, `App\Support\MailClients\AiHelpPrompt`): botones con logo de ChatGPT, Claude, Gemini, DeepSeek, Le Chat (Mistral) y Perplexity que abren el chat con un prompt en español con la dirección y los servidores, **sin la contraseña** (se le explica a la IA que el usuario la copia de la web directamente en la app). En los que no admiten el texto en el enlace (Gemini, DeepSeek) se copia al portapapeles y se avisa de pegarlo. Logos de Simple Icons (CC0) en `resources/svg/ai/`; lista en `config/mail_clients.php` (`ai_helpers`). Evento `ai_help_clicked` (solo el asistente)

### F · Medición y lanzamiento
- [x] Evento de servidor `signup_completed` a PostHog al crearse el buzón (`App\Services\ServerAnalytics`, por la cola). Con consentimiento lleva el `distinct_id` del navegador (cierra el embudo del front) y la atribución completa; sin consentimiento, anónimo (id aleatorio, sin perfil, sin click IDs ni referrer completo). Nunca la dirección, el email ni la IP
- Abrir altas y el resto del lanzamiento: Fase 6

## Fase 3 · Área de cliente completa

- [x] Script privilegiado `mail-provision` (`server/bin/`, root; la web lo llama con sudo y solo a él): `sign-profile`, `kick`
- [x] "Mis buzones" en "Mi cuenta": plan, espacio usado (Dovecot `quota_clone` → `mailserver.quota_usage`; la cuota real sigue en maildir) y estado. Un buzón sin fila aún sale vacío hasta su primer cambio (correo nuevo o borrado)
- [x] Dispositivos: renombrar y "último uso" (Dovecot `last_login` → `mailserver.last_logins`, por id de contraseña de dispositivo; 0 = webmail), más "último acceso al webmail"
- [x] Al revocar, se cierran las conexiones abiertas del buzón (`mail-provision kick`, tras responder; no por la cola: el worker corre con `NoNewPrivileges`)
- [x] Añadir otro buzón (D-014): "Añadir otro buzón" en Mi cuenta → `/alta?nuevo=1` → nombre → plan. El gratis sale bloqueado con el motivo ("Tu cuenta ya tiene su buzón gratis…") y el servidor lo impide. Hasta la Fase 4 no se puede completar (los de pago están "Muy pronto")
- [x] "Añadir otro buzón" con plan de pago (Fase 4): el mismo alta de pago con Stripe Checkout; al volver, a Mi cuenta
- [x] Plugin de Roundcube `unagrandeylibre` (fuente en `server/roundcube/`): botón "Mi cuenta" en el menú; logo, nombre y enlace de ayuda en `config.inc.php`. Roundcube no tiene activado el plugin de cambio de contraseña (no hace falta desactivar nada)
- [x] Skin `unagrandeylibre` (Elastic con los colores de la web: rojo, amarillo, tinta y papel; claro y oscuro). En el login, el botón principal es "Iniciar sesión con unagrandeylibre.es" (login único). Fuente en `server/roundcube/skins/`, se compila y sube con `server/roundcube/build-skin.sh`
- [x] Cambiar el email de recuperación (pide la contraseña; código de 6 cifras al nuevo; aviso al anterior con la dirección medio oculta)
- [x] Borrar cuenta (D-009; `/cuenta/borrar`, con contraseña y casilla): buzones marcados como borrados (`status=deleted`, `active=0`, `deleted_at`), dispositivos y tokens revocados, correo borrado del disco al momento (`mail-provision delete-content`), usuario de la web borrado y aviso a su email. El nombre sigue ocupado 90 días; después lo libera el timer `mail-purge` (root, diario 05:15, `mail-provision purge-deleted`), que nunca libera un nombre si aún queda correo en disco
- [x] Copias de seguridad y borrado (D-015): retención de restic reducida a 7 diarias + 4 semanales (~1 mes); el correo de una cuenta borrada sale de las copias en ese plazo
- [x] Exportar el correo: "Descargar una copia" en Mi cuenta → la web deja `exports/requests/<id>.req` → `mail-export.path` (root) lanza `mail-provision process-exports` (`doveadm backup` a mbox + zip, un `.mbox` por carpeta con nombres en español) → `exports/files/<id>.zip`. El scheduler lo recoge cada minuto, avisa por email y la copia se puede descargar 48 h (solo su dueño). Una a la vez por buzón; también se borra al borrar la cuenta

## Fase 4 · Planes de pago (Stripe)

Todo en el **sandbox** de la cuenta "Servicio Correo Minorista" (D-016). Pasar a cobrar de verdad está en la Fase 6.

- [x] Laravel Cashier 16 (`User` es `Billable`; una suscripción por buzón: `type = mailbox:{id}`). Moneda EUR, locale `es_ES`
- [x] IVA del 21 % incluido (D-017): un Tax Rate `inclusive` que se crea solo y se aplica a todas las suscripciones (`User::taxRates()`)
- [x] Catálogo en Stripe desde el admin (`App\Services\StripeCatalog`): al guardar un plan, tramo u oferta se sincroniza (si Stripe falla, se guarda igual y avisa). Plan de pago → Product + Price `inclusive`; cambiar el precio crea un Price nuevo, archiva el viejo y los suscriptores lo conservan (D-007; tabla `plan_prices` para saber de qué plan es cada Price). Oferta → Coupon limitado al producto (si cambian sus condiciones, otro Coupon). Todo de golpe: `php artisan stripe:sync`
- [x] Sobrecoste de nombre corto como línea extra de la suscripción (Price mensual y anual por tramo; solo planes de pago)
- [x] Alta de pago con Stripe Checkout (`App\Services\PaidSignup`): buzón `pending` (ocupa el nombre; ni recibe ni entra) → Checkout (plan + sobrecoste + cupón, 30 min, en español) → al pagar, activo y con envío (D-006). Vuelve antes que el webhook: se confirma consultando la sesión. Cancelar o caducar: el buzón pendiente desaparece (`mail-provision delete-pending`) y el nombre se vuelve a reservar
- [x] Desistimiento: casilla obligatoria "quiero que empiece ya" (fecha guardada en `checkouts.immediate_start_consent_at`); las devoluciones, a mano desde Stripe
- [x] Webhook `POST /stripe/webhook` (firmado; creado en el sandbox con los eventos de `config/cashier.php` y el secreto escrito directamente en el `.env` del servidor) e idempotente (`stripe_events`)
- [x] Plan, impagos y fin de suscripción por webhook (`App\Services\MailboxBilling`): cambiar de plan ajusta plan, cuota y `tier` al momento; impago → aviso y `unpaid_since`; pagar → todo normal (y reactiva si estaba suspendido); fin del plan → a gratis si se puede (no tiene ya uno, cabe en la cuota y no es nombre corto); si no, camino del impago
- [x] Portal de cliente de Stripe ("Gestionar pago y facturas" en Mi cuenta): tarjeta, facturas y cancelar al final del periodo. **Sin cambio de plan** (desactivado en su configuración, que crea `stripe:sync`): los planes se gestionan solo desde Mi cuenta. Si un plan cambiara en Stripe por otra vía, el webhook `customer.subscription.updated` lo aplica igual
- [x] "Cambiar de plan" en todos los buzones, gratis y de pago (`/cuenta/plan/{buzón}`, `App\Services\PlanChange`): gratis → pago por Stripe Checkout (con la casilla de desistimiento); pago → pago al momento (subir cobra ya la diferencia prorrateada y, si el cobro falla, no cambia nada; bajar la deja como saldo); pago → gratis al final del periodo pagado, con "Deshacer" hasta entonces. Explica por qué no se puede pasar a gratis cuando no toca (D-014, nombre corto, espacio). Incluye el sobrecoste de nombre corto en el cambio
- [x] Mi cuenta más compacta: fila "Plan" en cada buzón (estado + "Cambiar de plan" + "Gestionar pago y facturas") y "Copia de tu correo" plegable (se abre sola si hay una copia en marcha o lista)
- [x] Ciclo de vida según D-009 (`App\Services\MailboxLifecycle`, cada día a las 04:45; plazos en `config/lifecycle.php`): impago → suspensión día 10 → aviso día 23 → borrado día 30 → nombre libre día 90 (`mailboxes.release_at`). El correo lo borra root esa misma noche (`mail-purge`), porque el scheduler no puede usar sudo
- [x] Política de inactividad de las cuentas gratis (D-009): 6 meses sin entrar → aviso → 30 días → borrado → nombre libre 60 días después. Cuenta como uso cualquier acceso IMAP o al webmail (`last_logins`)
- [x] Límites de envío por plan en Rspamd: la web sirve la lista de buzones de cada `tier` de pago (`/internal/rspamd/{tier}.map`, solo desde el servidor) y `ratelimit.conf` les aplica su cubo (gratis 20/h, Básico 100/h, Pro 300/h). Probado: el envío sigue funcionando
- [x] Evento de servidor `subscription_started` a PostHog (las conversiones a las plataformas de anuncios, en la Fase 5)
- [x] Vigilancia de los webhooks de Stripe (2026-10-09): `php artisan stripe:webhook-health` comprueba que el endpoint existe y está activo en Stripe y que no hay eventos de las últimas 24 h sin entregar; `mail-monitor` lo ejecuta cada 10 min y avisa por Telegram (y cuando se resuelve). Además, Stripe avisa por email si el endpoint falla varios días

## Fase 5 · Lo que falta de código antes de lanzar

- [x] Textos legales (borrador, 2026-10-09): aviso legal, privacidad, cookies, condiciones y uso aceptable en `/legal/…` (`resources/views/legal/`), con los datos de Tible Technologies, S.L. (de tibletech.com; la marca es el dominio) en `config/legal.php`. Adaptados a un servicio de correo para consumidores: desistimiento de 14 días, plazos de D-009, copias de ~1 mes (D-015), juzgados del domicilio del consumidor. No se publican el DNI del responsable ni datos de la gestoría. Llevan el aviso "Borrador pendiente de revisión" hasta que la asesoría los apruebe (`LEGAL_DRAFT=false`)
- [x] Infraestructura de pruebas A/B (2026-10-09, `App\Services\Experiments`): experimentos en el admin (Web → Pruebas A/B) con variantes, pesos y fechas; la variante se decide en el servidor (sin parpadeo; estable en la sesión) y puede cambiar cualquier texto de `config/landing.php`, o el código puede preguntar `variant('clave')`. Se mide en PostHog con consentimiento: `$feature/<clave>` en todos los eventos, también en `signup_completed` y `subscription_started` (`users.attribution.experiments`). Para probar: `?variante=clave:b`. Hay un ejemplo desactivado ("Ejemplo: título del hero")
- [x] Conversiones a Meta (Conversions API: `CompleteRegistration` y `Subscribe`) y Google Ads (`uploadClickConversions`) desde el servidor, al completarse el alta y el primer pago (`App\Services\AdConversions`, por la cola). Solo si el usuario aceptó las cookies al darse de alta y vino de un anuncio (fbclid / gclid, gbraid, wbraid); a Meta, el email cifrado con SHA-256. **Apagadas** hasta rellenar las claves (`config/ads.php`, Fase 6)
- [x] WebMCP (2026-10-09): `comprobar_disponibilidad`, `listar_planes`, `empezar_alta` (`resources/js/webmcp.js`), con `document.modelContext` y, si no, `navigator.modelContext` (el estándar es un borrador del W3C WebML CG de 2026 y cambió de sitio). API pública `GET /api/plans` y `llms.txt` ampliado
- [x] Verificación en dos pasos (TOTP, códigos de recuperación de un solo uso, código que no se puede reutilizar) y passkeys (`spatie/laravel-passkeys`, interfaz propia con `@simplewebauthn/browser`) en Mi cuenta → Seguridad; "Entrar con una passkey" en `/entrar`. Los admins siguen entrando solo por `/admin`. Cada cambio se avisa por email
- [x] Página de estado del servicio `/estado` (2026-10-09): `mail-monitor` escribe qué comprobaciones fallan en `/var/www/portal/shared/status.json` y la web lo traduce a componentes (recibir, enviar, apps, webmail, web, pagos) sin detalles técnicos; "sin datos recientes" si tiene más de 30 min. Enlazada en el pie. Al estar en el mismo servidor, si este cae, la página también
- [x] Confirmaciones propias (2026-10-09, `a0bcbb9`): `<dialog>` nativo con la estética de la web en lugar de `confirm()` del navegador (`resources/js/confirm.js`; en un formulario, `data-confirm="…"`)
- [x] Backups fuera del servidor (2026-10-09, `608de96`; probada la restauración desde el VPS): cada noche, tras la copia local, `mail-backup` hace `restic copy` al VPS de copias del dueño (`37.27.5.15`) y aplica la misma retención (D-015); los domingos comprueba 1/8 de los datos. Repositorio cifrado con la misma contraseña (el VPS no puede leerlo) en `/mnt/HC_Volume_35438240/unagrandeylibre/restic`, **separado** de las copias de clientes de ese VPS (fuera de `backup/`, que es lo único que tocan sus scripts y lo que sube a R2). Usuario `ugl-backup` solo SFTP, encerrado en esa carpeta, con una clave válida solo desde la IP del servidor de correo (`server/backup-vps/setup.sh`). `mail-monitor` avisa si la última copia externa tiene más de 36 h. Opción sencilla: si alguien es root en el servidor de correo podría borrarla también

## Fase 6 · Pre-lanzamiento

Lo que solo puede hacer el dueño. Lo hará al final, cuando el código esté terminado, lo haya probado todo y haya pasado su feedback con ajustes. El orden de los bloques es el recomendado; el último bloque abre altas.

### Probar y dar feedback
Probar la web entera (también en un iPhone, Thunderbird y Outlook) y pasar el feedback es continuo y lo lleva el dueño: no se apunta aquí como tarea (2026-10-14).
- [x] (2026-10-14, `eb477e1`…`beaaa12`) Feedback de Mi cuenta: cada buzón en su propia tarjeta con cabecera marcada (@ roja) y título "Tus N buzones"; en móvil la dirección parte por la @; dispositivos plegables (número + "Añadir"); sin dispositivos, "Configura un dispositivo" (recomendado) va arriba e igual de grande que "Abrir mi correo", los dos con icono y subtítulo
- [x] (2026-10-14, `ba6bcd7`) Textos legales: tres frases de condiciones y uso aceptable mostraban `@{{ $l['brand'] }}` en vez de la dirección (en Blade, `@{{` escribe las llaves tal cual). El test de legales ahora falla si aparece `{{`
- [ ] Pagos en el sandbox de Stripe: el alta de pago (hecha el 2026-10-08 con `varela@`, plan Básico); falta cancelar en Checkout, "Cambiar de plan" (subir a Pro, bajar y pasar a gratis, con "Deshacer") y "Gestionar pago y facturas". Tarjeta de prueba `4242 4242 4242 4242`, cualquier fecha futura y CVC

### Contenido
- [x] Gmail web (2026-10-14): gmail.com en el ordenador ya no puede leer otras cuentas (Google lo quitó en enero de 2026). Explicado en la FAQ de la landing ("¿Puedo leerlo dentro de gmail.com?") y en una nota de la guía de Gmail (`setup/steps/android.blade.php`): en el ordenador, webmail, Outlook o Thunderbird
- [ ] Textos definitivos de la landing: hero, bloques informativos y FAQ (también cuentan para el SEO). Van en `portal/config/landing.php`, o pasárselos a la IA
- [x] Textos legales validados por la gestoría (2026-10-09); aviso de borrador quitado (`LEGAL_DRAFT=false`)

### Correo y reputación
- [x] (2026-10-14) Nombre visible "Javier · Una Grande y Libre" en la identidad de Roundcube de `javier@`. `test@` retirado (con copia restic antes): marcado como borrado, correo borrado con `mail-provision delete-content`, contraseñas de aplicación revocadas y su usuario de Roundcube quitado; el nombre queda en cuarentena 90 días
- Calentamiento del dominio y su criterio de salida: quitados del plan (D-023)

### Cobrar de verdad (Stripe)
- [ ] Activar la cuenta "Servicio Correo Minorista" (datos de Tibletech, NIF, banco) y su información pública: nombre `unagrandeylibre.es`, descriptor `UNAGRANDEYLIBRE.ES`, web, email de soporte
- [ ] Recibos por email (Settings → Customer emails) y marca (logo y `#AA151B`), en el sandbox y en live
- [ ] Gestoría: si valen las facturas de Stripe, la serie de facturas propia de esta cuenta y Verifactu
- [ ] Pasar las claves `live` a la IA (en el `.env` del servidor): ella vacía los IDs de Stripe del sandbox, ejecuta `php artisan stripe:sync` y crea el webhook de producción. **Antes**, decidir qué pasa con los buzones que los amigos pagaron con la tarjeta de prueba: sus suscripciones del sandbox no existen en live (la IA puede listarlas y pasarlos a gratis o regalarles un tiempo)

### Analítica y anuncios
- [ ] PostHog, juntos: activar Session replay y Heatmaps, añadir `https://unagrandeylibre.es` a Authorized URLs y crear el embudo `$pageview → name_checked → name_chosen → plan_selected → … → signup_completed` (Product analytics → New insight → Funnel)
- [ ] Cuentas de anuncios en Meta y Google. Para activar las conversiones de servidor, pasar a la IA las claves de `config/ads.php` (píxel y token de Meta; cliente, developer token, OAuth y acciones de conversión de Google). Al activarlas, la IA añade a la política de privacidad que, con tu consentimiento, comunicamos el alta y el pago a Meta/Google (email cifrado, IP, navegador e identificador del clic)
- [ ] Anuncio de prueba en Meta y Google con la marca, el dominio y la bandera, para comprobar que no los clasifican como contenido político. Si hay problemas, valorar una comunicación más neutra

### Abrir altas (hecho el 2026-10-09, `7326012`)
- [x] `SIGNUP_OPEN=true`, `APP_INDEXABLE=true` y sitemap con la ayuda, los textos legales y `/estado`. Se abrió antes de terminar el calentamiento: los amigos que prueban ayudan a calentar el dominio
- [x] Quitada de `AGENTS.md` (regla 4) y de la skill `mail-server-ops` la nota de "todavía no hay usuarios reales": **en producción con usuarios reales desde el 2026-10-09**

## Fase 7 · Soporte por tickets (cerrada el 2026-10-14)

Pedido el 2026-10-14. Decisiones D-018 a D-021. Cada parte se puede subir por separado.

### A · Formulario y tickets
- [x] (2026-10-14, `d5b22dc`) Tablas `support_tickets` (número `#NNNN`, usuario o email, asunto, estado abierto/cerrado), `support_messages` (hilo: del cliente o nuestro, origen web/email) y adjuntos en disco privado
- [x] Página `/soporte` (contacto y soporte son lo mismo): email si no hay sesión (con sesión, el de recuperación), asunto, descripción, hasta 3 imágenes (8 MB en total; JPG, PNG, WebP, HEIC) con el aviso de `transfer.tibletech.com` para lo demás, Turnstile y límite por IP
- [x] Interruptor "Tickets sin sesión" en el admin (activado de entrada); apagado, `/soporte` pide entrar
- [x] Autoconfirmación al cliente con `[#NNNN]` en el asunto
- [x] Botones de ayuda: icono arriba a la derecha (cabecera de la web y de Mi cuenta), enlace en el footer y botón flotante en la home al hacer scroll

### B · Admin
- [x] Recurso de Filament "Tickets": lista con estado y filtros, contador de abiertos en el menú, hilo con las imágenes
- [x] Responder desde el admin (sale de `soporte@` con `[#NNNN]` en el asunto), abrir y cerrar
- [x] Aviso de ticket nuevo a `javier@`

### C · Respuestas por email
- [x] (2026-10-14, desplegado `b66e816`) Buzón `soporte@` en el servidor (id 10, 1 GB) (`tier=system`, como `noreply@`) y un mailer propio en Laravel con sus credenciales (Postfix no deja enviar como otro: `reject_sender_login_mismatch`). Cambio en producción: con confirmación
- [x] (2026-10-14, `ecadc9d`) Lector IMAP cada minuto (`support:fetch`): los correos con `[#NNNN]` del cliente se añaden al hilo (sin la cita del correo anterior, con sus imágenes) y reabren el ticket; los demás se quedan en el buzón y se avisa a `javier@` con "Responder a" al remitente
- [x] Desplegado el 2026-10-14 (`b66e816`); `support:fetch` entra en `soporte@` por IMAP
- [x] (2026-10-14) Probado en producción de punta a punta con los tickets #0002 y #0003 (formulario → confirmación → respuesta desde el admin → respuesta del cliente por email). Hubo que arreglar tres cosas al estrenarlo (las tres en el README):
  - `storage/app` es de root y la web no podía crear `app/private` para las imágenes: 500 al adjuntar. Creada a mano
  - Los correos al cliente salían autenticados como `noreply@` y Postfix los rechazaba: Laravel pisa el mailer del Mailable al encolar (`c87cc04`)
  - `support:fetch` con `runInBackground` moría al terminar el scheduler oneshot y no entraban las respuestas (`03cb69b`)

### D · Privacidad
- [x] (2026-10-14) Borrador de los cambios en la política de privacidad (ya aplicado y borrado). Proponía guardar las consultas 2 años desde el último mensaje y borrarlas con la cuenta
- [x] (2026-10-14) **Dueño:** validado por la gestoría, con el plazo de 2 años (D-024)
- [x] (2026-10-14) Al borrar la cuenta se borran sus tickets con sus imágenes, también los que abrió sin sesión con su email de recuperación (`Support::deleteForUser`, desde `AccountDeletion`). Así se cumple lo que ya promete la política. La página de borrar cuenta lo avisa
- [x] (2026-10-14) Texto aplicado en `privacidad.blade.php` (fila de soporte, Turnstile y Cloudflare también en el formulario, plazo en el punto 3; fecha en `config/legal.php`) y tarea diaria `limpiar-soporte` (04:50) que borra las consultas caducadas con sus imágenes (`Support::deleteExpired`, `support.retention_months`)

### E · Avisos a Telegram (D-022)
- [x] (2026-10-14) Aviso de cada ticket nuevo y de cada respuesta del cliente: número, asunto, quién, adjuntos y el mensaje recortado (`Support::telegramText`, por la cola)
- [x] (2026-10-14) Resumen diario: `report:daily-stats` (web) + `mail-daily-report` y su timer (servidor) + muestras de CPU/RAM en `mail-monitor`. Probado con `--dry-run` en producción
- [x] (2026-10-14) Resumen de prueba enviado y recibido (17:06); el primero de verdad, el 2026-10-15 a las 09:00
- [x] (2026-10-14) **Dueño:** comprobado que llega el aviso de Telegram al abrir un ticket o responder a uno por email
- [x] (2026-10-14, `e74c5b3`) Instalado en el servidor (script, timer a las 09:00 y `mail-monitor`), token del bot en el `.env` de la web y desplegado

## Después del lanzamiento

- [ ] Dashboard de embudo en PostHog; revisar mapas de calor y grabaciones de las primeras campañas
- [ ] Primeros A/B: texto del hero, orden/precio de planes, CTA

## Futuro (sin fecha)

- [ ] Varios dominios en el alta (`domains.public_signup`), con su autoconfig, SRV y DKIM
- [ ] Store interna de nombres premium y/o dominios (`reserved_names.price_cents`)
- [ ] Dominios propios del cliente
- [ ] Alias, reenvíos, respuesta automática y filtros (managesieve) desde el portal
- [ ] Separar la web a otro servidor
- [ ] Autoalojar PostHog cuando deje de ser gratis o haya máquina
- [ ] Programa de referidos
