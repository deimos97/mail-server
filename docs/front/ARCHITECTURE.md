# Arquitectura del front-end

Diseño técnico. Las decisiones que lo sostienen están en "Decisiones tomadas" de [../ROADMAP.md](../ROADMAP.md); si algo depende de una decisión pendiente, se marca **[abierta, D-NNN]** y la decisión está en [../DECISIONS.md](../DECISIONS.md).

## Estado de partida (verificado en el servidor, 2026-10-05, tras actualizar a 24.04)

- Hetzner VPS, Ubuntu 24.04, **3 vCPU / 3,7 GB RAM / 75 GB** (7 % usado). Sin Docker.
- PHP **8.3** (pool `webmail` para Roundcube 1.6). nginx sirve `mail.` y `webmail.unagrandeylibre.es`; el apex `unagrandeylibre.es` ya apunta a la IP del servidor pero no tiene web.
- Certificado Let's Encrypt con SAN `mail.` + `webmail.` (no incluye el apex, `www`, `autoconfig` ni `autodiscover`).
- BD `mailserver`:
  - `domains(id, name, active, created_at)` → 1 fila: `unagrandeylibre.es`.
  - `mailboxes(id, domain_id, local_part, email, password, quota_bytes, tier, active, created_at)` → 1 buzón, `tier=free`, cuota por defecto 1 GiB.
  - `aliases(id, domain_id, source, destination, active)`.
- Dovecot lee `password` (esquema por defecto `BLF-CRYPT`) y `quota_bytes` de `mailboxes`; filtra por `mailboxes.active` y `domains.active`. **Dar de alta un buzón = un `INSERT`**; Dovecot crea el Maildir al primer uso.
- Rspamd limita el envío a **40 mensajes/h por usuario** (ráfaga 200), igual para todos.
- Roundcube 1.6 sin plugins activos (están disponibles `password`, `managesieve`, `markasjunk`, `autologon`, `new_user_identity`…).
- No hay registros DNS `autoconfig`, `autodiscover` ni SRV.

**Pendiente de verificar:** que un hash bcrypt generado por PHP (`$2y$…`) con prefijo `{BLF-CRYPT}` lo acepta el Dovecot instalado (casi seguro que sí; probar con `doveadm pw -t`).

## Stack

| Pieza | Elección | Por qué |
|---|---|---|
| Framework | **Laravel 12** (PHP 8.3) | El README ya dice "front PHP". Trae auth, colas, correo, rate limiting, validación y migraciones; la landing se renderiza en servidor (bueno para SEO y LCP). |
| Pagos | **Laravel Cashier (Stripe)** + Stripe Checkout + Customer Portal | Suscripciones, webhooks, tarjetas y facturas sin construir formularios de pago. Nada de datos de tarjeta pasa por nuestro servidor (PCI SAQ A). |
| Admin | **Filament** | CRUD de planes, ofertas, dominios, nombres reservados y usuarios en horas, no semanas. |
| Interactividad | Blade + Alpine.js (o Livewire) | Comprobación de disponibilidad y pasos del onboarding sin montar una SPA. |
| Bandera | Shader WebGL pequeño (o three.js) sobre la textura de la bandera oficial de España, con `<img>` de póster | Ligero y nítido; un vídeo penaliza el LCP en móvil. |
| Colas | `database` driver + 1 worker systemd | Redis ya está, pero lo usa Rspamd; la cola en BD basta al principio. |
| Analítica | **PostHog Cloud EU** | Embudos, mapas de calor, grabaciones, A/B y feature flags en una sola herramienta open source. |

PHP 8.3 viene de serie con Ubuntu 24.04 (el servidor se actualiza en la Fase 0, D-013), igual que Roundcube 1.6. Web y webmail usan la misma versión de PHP, cada una con su pool FPM.

## Despliegue

- Mismo servidor que el correo (hay recursos de sobra), aislado. Directorio `/var/www/portal`, usuario de sistema `portal`, pool FPM propio con `open_basedir`, igual que se hizo con `webmail`.
- **Usuario de MariaDB `portal`** con:
  - todos los permisos sobre su BD `portal`;
  - en `mailserver`: `SELECT` en `domains`, `SELECT, INSERT, UPDATE` en `mailboxes` y `aliases`. Sin `DELETE` ni `DROP`.
- Lo que necesita root (borrar Maildirs, `doveadm`) **no** lo hace la web: lo hace un script privilegiado acotado (`/usr/local/sbin/mail-provision`) llamado por sudo con argumentos validados, o un timer que procesa una tabla de tareas pendientes.
- nginx: nuevo `server` para `unagrandeylibre.es` y `www` (redirige al apex). Ampliar el certificado con `unagrandeylibre.es`, `www`, `autoconfig`, `autodiscover`. Recordar el *monkey noise* del reto ACME (README).
- Cloudflare: el apex/`www` pueden ir con proxy (naranja). `mail.` **nunca** con proxy.
- Código: monorepo. `portal/` (la app), `server/` (copia versionada de la configuración del servidor, **sin secretos**: se sustituyen por `__REDACTED__` y se comprueba con `gitleaks` en un hook pre-commit), `docs/`. Despliegue con un script `deploy.sh` (git pull + composer + migrate + restart worker) o GitHub Actions por SSH.
- Backups: añadir la BD `portal` al `mail-backup` existente.
- Monitorización: añadir al `mail-monitor` la web (HTTP 200), el worker de colas y la recepción de webhooks de Stripe.

## Modelo de datos

BD `portal` (la de la web) y BD `mailserver` (la que leen Postfix y Dovecot). La web es dueña de la verdad comercial; `mailserver` solo tiene lo que el correo necesita.

```
portal.users                       el cliente (1 persona)
  id, email (recuperación, único), email_verified_at, password,
  stripe_customer_id, locale, two_factor_*, created_at, deleted_at
  utm_source/medium/campaign/content/term, click_id, landing_variant, referrer

portal.plans
  id, slug, name, description, features (json, lista para la tarjeta),
  is_free, price_cents, currency, interval (month|year),
  quota_bytes, max_aliases, send_limit_per_hour,
  stripe_product_id, stripe_price_id,
  is_active, available_from, available_until,      ← activación absoluta o programada
  is_highlighted, sort_order, created_at

portal.plan_offers                 descuentos programables
  id, plan_id, label ("-50 % lanzamiento"), type (percent|amount), value,
  duration (once|repeating|forever), duration_months,
  starts_at, ends_at, max_redemptions, stripe_coupon_id, is_active

portal.subscriptions / subscription_items      (tablas de Cashier)
  una suscripción por buzón de pago: type = "mailbox:{mailbox_id}"

portal.mailbox_reservations        nombre retenido durante el onboarding
  id, domain_id, local_part, session_id/user_id, expires_at

portal.reserved_names              nombres que no se pueden coger
  id, pattern, reason (sistema|marca|ofensivo|premium), price_cents (futura tienda)

portal.name_rules                  reglas de nombre editables desde el admin
  min_length, max_length, allowed_chars, forbid_edge_dot, forbid_double_dot …

portal.name_price_tiers            sobrecoste por longitud (p. ej. 1–2, 3–4)
  id, min_length, max_length, price_cents (mensual), requires_paid_plan (siempre 1),
  stripe_price_id, is_active
  → se añade como línea periódica extra a la suscripción del buzón

portal.app_passwords               contraseñas por dispositivo (ver SSO)
  id, mailbox_id, name, hash, last_used_at, created_at

portal.audit_log                   quién hizo qué (altas, cambios de plan, admin)

mailserver.domains   + public_signup (bool), sort_order
mailserver.mailboxes + user_id, plan_id, status (pending|active|suspended|deleted),
                       can_send (0 hasta verificar el email en cuentas gratis),
                       suspended_at, last_login_at

portal.name_quarantine             nombres liberados que aún no se pueden coger (D-009)
  domain_id, local_part, released_at, available_at
```

Notas:

- **Varios buzones por usuario** desde el día 0 (`mailboxes.user_id`), aunque el 90 % tenga uno.
- **Varios dominios** desde el día 0: el alta siempre trabaja con `domain_id`; `domains.public_signup` decide cuáles se ofrecen.
- `mailboxes.tier` existente se mantiene en sincronía con el plan (Rspamd u otros scripts pueden usarlo para límites por plan).
- `password_query` de Dovecot debe pasar a filtrar por `status = 'active'` (o seguir con `active`, mantenido por la web). Decidir al implementar; es un cambio en el servidor de correo.
- Las cuentas de usuario no se borran en duro inmediatamente: `deleted_at` + purga programada.

### Planes y Stripe

- En Stripe un `Price` es inmutable. Cambiar el precio de un plan = crear un `Price` nuevo y apuntar el plan a él. Los suscriptores existentes **conservan su precio** salvo que se migren a propósito (decidido: *grandfathering*).
- Ofertas → `Coupon` de Stripe aplicado en el Checkout. El precio tachado de la landing se calcula desde `plan_offers`.
- El admin de Filament sincroniza con Stripe al guardar (crea producto/precio/cupón). La landing **solo lee de nuestra BD**, nunca de la API de Stripe en cada visita.
- "Plan visible ahora" = `is_active AND (available_from IS NULL OR available_from <= now()) AND (available_until IS NULL OR available_until > now())`. Un plan que deja de estar visible no afecta a quien ya lo tiene.

## Flujos

### Comprobar disponibilidad

`GET /api/availability?local=…&domain=…` → `{available, reason, suggestions}`.

Reglas del nombre (en `name_rules`, editables sin desplegar): minúsculas, `a-z 0-9 . - _`, sin empezar/terminar en punto ni dos puntos seguidos; normalizar antes de comparar; no disponible si existe en `mailboxes`, en `aliases.source`, en `reserved_names`, en `name_quarantine` o en una reserva vigente. Si la longitud cae en un tramo de `name_price_tiers`, la respuesta incluye el sobrecoste (`{available: true, surcharge_cents: …}`) y la UI lo muestra ("+X €"). Rate limit por IP (evita que enumeren buzones).

Lista de reservados mínima: `postmaster, abuse, admin, administrator, root, hostmaster, webmaster, noreply, no-reply, mailer-daemon, security, support, soporte, info, contacto, billing, facturacion, legal, privacy, privacidad, dpo, ventas, sales`, nombres de la marca y lista de palabras ofensivas.

### Alta gratis

1. Reserva del nombre (15–30 min).
2. Crear `users` (o reutilizar si ya existe y está logueado) → enviar verificación.
3. Crear `mailboxes` activo con `can_send=0`: ya **recibe y se puede leer**, pero no envía. Evento `signup_completed`.
4. Al verificar el email → `can_send=1`. Evento `email_verified`.

El bloqueo de envío se aplica en Postfix (mapa MySQL en `smtpd_sender_restrictions` que rechaza si `can_send=0`) o en Rspamd; se decide al implementar. Los buzones de pago se crean ya con `can_send=1`.

### Alta de pago

1–2 igual. 3. Stripe Checkout (`mode=subscription`, `customer` = el del usuario, `metadata.mailbox_reservation_id`, cupón si hay oferta). 4. Webhook `checkout.session.completed` → crear buzón y suscripción. La página de vuelta (`success_url`) solo muestra "procesando…" hasta que el webhook llega.

Webhooks a manejar: `checkout.session.completed`, `invoice.paid`, `invoice.payment_failed`, `customer.subscription.updated`, `customer.subscription.deleted`. Idempotentes (guardar `event.id`).

### Ciclo de vida de un buzón

`pago fallido → Stripe reintenta → aviso por email → suspendido → borrado del contenido (con aviso previo para exportar) → cuarentena del nombre → nombre libre`. Plazos (configurables, en `config/lifecycle.php` o en el admin): impago → suspensión día 10 (sigue recibiendo) → borrado día 30 → nombre libre día 90. Cancelación → gratis al fin del periodo si cabe en la cuota. Gratis inactiva 6 meses → aviso → +30 días suspensión y borrado → +60 días nombre libre. Borrado voluntario → inmediato, nombre libre a 90 días. Lo ejecuta un timer diario que llama a `mail-provision`. Regla fija: **nunca** se entrega un nombre a otra persona sin haber borrado antes todo el contenido y pasado la cuarentena.

Bajar de plan con más correo que la nueva cuota: no se borra nada; se bloquea la recepción hasta que libere espacio (comportamiento estándar de la cuota de Dovecot), con aviso.

### Correo transaccional de la web

Desde `noreply@unagrandeylibre.es` por el propio Postfix (submission autenticado). **Ojo:** el límite de Rspamd de 40/h por usuario también se aplica a `noreply`; hay que excluirlo o darle su propio límite. Si en el futuro la reputación se resiente, se puede pasar a Postmark/SES/Brevo.

### Cuota y uso

Para mostrar "usados 120 MB de 1 GB" sin root, configurar en Dovecot el **dict de cuota en MariaDB** (tabla `quota` con `username, bytes, messages`) además del límite. La web la lee con `SELECT`.

## Login único (SSO con el webmail)

Hay dos "logins": el de la web (persona) y el del buzón (IMAP/SMTP, lo que usa el webmail y el móvil). Objetivo: loguearse una vez en la web y entrar al webmail sin contraseña.

**Decidido: OAuth2/OIDC.** La web actúa de proveedor de identidad (Laravel Passport). Roundcube 1.6 soporta login OAuth2 de serie (`oauth_provider`, `oauth_auth_uri`…) y Dovecot 2.3 tiene `passdb oauth2` (valida el token por introspección contra la web). Postfix autentica vía Dovecot SASL, así que el envío desde el webmail también funciona con el token. Es el camino estándar (lo usan Gmail/Outlook con IMAP) y escala a varios buzones: la web emite un token para el buzón concreto que el usuario elige.
- Hacer un *spike* antes de construir el resto, ya sobre Roundcube 1.6.

**Plan B, si OAuth2 se atasca — token de un solo uso + plugin de Roundcube.** La web genera un token aleatorio de 60 s guardado en BD; redirige a `webmail.…/?_sso=TOKEN`; un plugin propio de Roundcube lo valida y hace login contra Dovecot usando un **master user** de Dovecot (`buzon*master`). Más simple, pero el master user es una llave maestra (si se filtra, acceso a todos los buzones) y hay que comprobar que `reject_sender_login_mismatch` sigue funcionando con él.

**Contraseñas de los buzones (para móvil/escritorio). Decidido: contraseñas de aplicación desde el principio:** generadas en "Mi cuenta" por dispositivo (revocables, se muestran una vez). Dovecot comprueba contra `mailboxes.password` **o** `app_passwords` (segundo `passdb`). Así el usuario solo recuerda la contraseña de la web y perder el móvil no compromete la cuenta. `mailboxes.password` queda como contraseña interna aleatoria que nadie usa.

**Enlace de vuelta desde el webmail:** plugin mínimo de Roundcube (o personalización del skin Elastic) que añade "Mi cuenta" al menú y pone el logo. Desactivar el plugin `password` de Roundcube: las contraseñas se gestionan en el portal.

## Autoconfiguración de clientes de correo

Sí existen, pero ninguno cubre todo:

| Cliente | Mecanismo | Qué hay que hacer |
|---|---|---|
| Thunderbird (escritorio y Android, antes K-9), FairEmail, Evolution, KMail, Spark… | **Autoconfig** `https://autoconfig.<dominio>/mail/config-v1.1.xml` | Servir el XML (estático con `%EMAILADDRESS%`). Opcional: enviarlo a la ISPDB de Thunderbird para que funcione sin DNS. |
| Outlook clásico de Windows | **Autodiscover POX** `https://autodiscover.<dominio>/autodiscover/autodiscover.xml` | Endpoint que responde al POST con IMAP/SMTP. |
| Outlook nuevo / Outlook móvil | Detección propia, poco fiable para IMAP genérico | Tutorial con capturas. |
| iPhone / iPad / Mac (Mail) | **Perfil `.mobileconfig`** | Generarlo desde "Mi cuenta" por buzón (puede llevar la contraseña de aplicación dentro → instalación en 3 toques). Firmarlo con el certificado TLS para que salga como "verificado". |
| Gmail app (Android/iOS) con "Otra cuenta" | Heurísticas propias (prueba `mail.<dominio>`, `imap.<dominio>`…) | Registros SRV + tutorial. |
| Genérico | **SRV RFC 6186/8314**: `_imaps._tcp`, `_submission._tcp`, `_submissions._tcp` | Publicar en Cloudflare. |

Datos a publicar en todos: IMAP `mail.unagrandeylibre.es:993` SSL/TLS, SMTP `mail.unagrandeylibre.es:465` SSL/TLS (o 587 STARTTLS), usuario = dirección completa.

Con varios dominios: cada dominio necesita sus `autoconfig.`/`autodiscover.` y SRV (todos apuntando al mismo servidor y al mismo nombre `mail.unagrandeylibre.es`).

Tutoriales: página `/ayuda/configurar/<cliente>` con capturas para iPhone, Android (Gmail), Outlook, Thunderbird. Cuentan también como contenido SEO.

## Antiabuso (crítico con cuentas gratis)

Un proveedor de correo gratuito atrae a spammers el primer día. Si una cuenta manda spam, la IP entra en listas negras y **el correo de todos** deja de llegar a Gmail/Outlook.

- Captcha en el alta: **Cloudflare Turnstile** (el DNS ya está en Cloudflare, gratis, sin cookies) o **ALTCHA** (open source, autoalojado, proof-of-work).
- Bloquear emails de recuperación desechables (lista `disposable-email-domains`).
- Límite de altas por IP y por email de recuperación.
- Límite de envío por plan (`send_limit_per_hour`) aplicado en Rspamd por `tier`; las cuentas gratis y nuevas, más bajo.
- Las cuentas gratis no pueden enviar hasta verificar el email de recuperación.
- Buzones `abuse@` y `postmaster@` operativos (son obligatorios de facto) y alta en los *feedback loops* (Microsoft SNDS/JMRP, Google Postmaster Tools).
- Política de uso aceptable en las condiciones y botón de suspensión rápida en el admin.

## Analítica y consentimiento

- **PostHog Cloud EU** (autoalojarlo más adelante): eventos del embudo (frontend + backend para los que importan: `signup_completed`, `subscription_started` se envían desde el servidor, así no se pierden con bloqueadores), mapas de calor, grabaciones, experimentos A/B, feature flags.
- **RGPD/LSSI (España):** mapas de calor, grabaciones y píxeles de anuncios necesitan **consentimiento previo**. Banner de cookies con "Rechazar" igual de visible que "Aceptar". Sin consentimiento: PostHog en modo sin cookies (`persistence: 'memory'`) solo con métricas agregadas, sin grabación.
- Las grabaciones enmascaran todos los inputs (contraseñas y emails nunca se graban).
- Conversiones para campañas: Meta Conversions API y Google Ads Enhanced Conversions enviadas **desde el servidor** al completarse el alta/pago, con el click ID guardado en `users`.
- PostHog Cloud EU (Frankfurt) o autoalojado en otra máquina; **no en este servidor** (pide ~16 GB de RAM).

## Legal y facturación (España)

- Páginas: aviso legal (LSSI), privacidad (RGPD), cookies, condiciones del servicio, uso aceptable.
- Derecho de desistimiento de 14 días en servicios digitales: casilla de "quiero empezar ya y entiendo que pierdo el desistimiento" en el checkout, o devolver si lo piden.
- IVA: Stripe Tax o tipo fijo del 21 % según a quién se venda. Facturas: las de Stripe valen como base, pero **revisar con la gestoría** la obligación de Verifactu y si las facturas tienen que salir de un software homologado.
- Conservación de datos de tráfico y requerimientos judiciales: definir procedimiento con asesoría.
