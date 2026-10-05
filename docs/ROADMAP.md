# Roadmap del front-end

Cada fase deja algo que funciona en producción. Marca las casillas al terminar y mueve la etiqueta **EN CURSO** a la fase activa. Las referencias `D-NNN` están en [DECISIONS.md](DECISIONS.md); el detalle técnico, en [front/ARCHITECTURE.md](front/ARCHITECTURE.md).

Estado: **Fase 0 — EN CURSO** (planificación; todavía sin código).

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
| D-008 | Bandera **oficial de España**, sin connotación política: el mensaje es "producto nacional / Made in Spain". La web es 100 % apolítica. Técnica: shader WebGL + imagen de respaldo. | 2026-10-05 |
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
- [ ] Anuncio de prueba en Meta y Google con la marca, el dominio y la bandera, para comprobar que no los clasifican como contenido político antes de invertir en diseño
- [x] Carpeta `server/` con copias versionadas de la configuración (Postfix, Dovecot, Rspamd, nginx, Roundcube, fail2ban, iptables, scripts `mail-*`, timers). **Sin secretos**: `.gitignore` y lista de exclusiones (`des_key`, contraseñas de BD, `*.env`, `restic.pass`, claves DKIM/TLS, hashes); los valores sensibles se sustituyen por `__REDACTED__`. Revisar con `gitleaks` antes de cada commit (hook pre-commit). Script `server/pull-config.sh` que las actualiza desde el servidor.
- [x] Verificar que Dovecot acepta hashes bcrypt `$2y$` de PHP (`doveadm pw -t`): sí, coste 10 y 12, con prefijo `{BLF-CRYPT}`; también `{ARGON2ID}`
- [x] Pool FPM `portal` (PHP 8.3) y usuario de sistema `portal`
- [x] BD `portal` y usuario MariaDB `portal` con permisos mínimos sobre `mailserver` (comprobado: no puede borrar buzones, tocar dominios ni leer `roundcube`)
- [x] nginx + certificado propio de la web (`unagrandeylibre.es`), separado del del correo
- [x] Registros DNS de `www`, `autoconfig` y `autodiscover` (solo DNS, sin proxy), certificado ampliado, `www` → apex
- [x] Esqueleto Laravel 13 en `portal/`, despliegue (`portal/deploy.sh` → `portal-deploy` en el servidor), `.env` solo en el servidor
- [x] Añadir BD `portal` y `/var/www/portal/shared` al backup, y la web (certificado + `/up`) al `mail-monitor`
- [ ] Worker de colas (systemd) y scheduler de Laravel (timer), cuando haga falta el primero
- [ ] Cuentas: Stripe (modo test), PostHog EU, Cloudflare Turnstile
- [ ] Textos legales base (aviso legal, privacidad, cookies, condiciones, uso aceptable) — borrador para revisar con asesoría

## Fase 1 · Landing + planes desde BD

Objetivo: la página pública, medida, lista para recibir tráfico (aunque el alta aún no esté).

- [ ] Migraciones: `plans`, `plan_offers`, `reserved_names`, `name_rules`, `name_price_tiers`; columnas nuevas en `mailserver.domains`
- [ ] Admin Filament: planes (gratis/pago, activación absoluta o programada, destacado, orden), ofertas programables, dominios, nombres reservados, reglas de nombre y tramos de precio por longitud
- [ ] Hero con la bandera de España ondeando (WebGL + póster + `prefers-reduced-motion`), mensaje "producto nacional", e input `nombre @dominio`
- [ ] Endpoint de disponibilidad: reglas configurables, sobrecoste por longitud ("+X €"), sugerencias, rate limit
- [ ] Sección de planes renderizada desde BD (precio con IVA, tachado si hay oferta)
- [ ] Bloques informativos (estructura; contenido lo pone el usuario) + footer legal
- [ ] Banner de consentimiento + PostHog (eventos del embudo, mapas de calor, grabaciones con inputs enmascarados)
- [ ] Captura de UTM / click IDs en sesión
- [ ] SEO: metadatos, Open Graph, JSON-LD (`Organization`, `Product`/`Offer`, `FAQPage`), `sitemap.xml`, `robots.txt`, `llms.txt`
- [ ] Lighthouse móvil ≥ 90 en rendimiento, SEO y accesibilidad
- [ ] Mientras no haya alta: el CTA puede apuntar a una lista de espera (mide demanda desde ya)

## Fase 2 · Alta gratuita de punta a punta

Objetivo: alguien llega por un anuncio y sale con un buzón funcionando en el móvil.

- [ ] Reserva temporal del nombre durante el onboarding
- [ ] Paso 1: email de recuperación + contraseña + Turnstile; bloqueo de emails desechables; límites por IP
- [ ] Verificación del email (enlace + código)
- [ ] Paso 2: elección de plan (solo gratis habilitado en esta fase)
- [ ] Provisión del buzón (`INSERT` en `mailserver.mailboxes` con `user_id`, `plan_id`, cuota, `tier` y `can_send=0`)
- [ ] Bloqueo de envío hasta verificar (`can_send`), leído por Postfix (mapa MySQL en `smtpd_sender_restrictions`) o por Rspamd; al verificar → `can_send=1`
- [ ] Correo transaccional por el propio Postfix con `noreply@`, con excepción en el ratelimit de Rspamd
- [ ] Límites de envío por plan en Rspamd según `tier`
- [ ] Contraseñas de aplicación: tabla `app_passwords` + segundo `passdb` en Dovecot (y en la autenticación SMTP)
- [ ] Pantalla "¡Listo!" con acceso al webmail y "Configura tu móvil" (genera la contraseña del dispositivo o el perfil `.mobileconfig` que la lleva dentro)
- [ ] Autoconfiguración: `autoconfig` XML, Autodiscover, registros SRV, perfil `.mobileconfig`
- [ ] Tutoriales con capturas: iPhone, Android/Gmail, Outlook, Thunderbird
- [ ] `abuse@` y `postmaster@` operativos; alta en Google Postmaster Tools y Microsoft SNDS
- [ ] Eventos de servidor `signup_completed` a PostHog y conversiones a Meta/Google Ads

## Fase 3 · Área de cliente + login único

- [ ] Login (email de recuperación o dirección), recuperar contraseña, sesiones
- [ ] "Mis buzones": plan, uso de cuota (dict de cuota de Dovecot en MariaDB), estado
- [ ] Gestión de dispositivos conectados: listar, renombrar, revocar, último uso
- [ ] Añadir otro buzón (varios por usuario)
- [ ] *Spike* de OAuth2 (Laravel Passport como proveedor + Roundcube + Dovecot `passdb oauth2`) → implementar el login único
- [ ] Plugin/skin de Roundcube: logo, enlace "Mi cuenta"; desactivar cambio de contraseña en Roundcube
- [ ] Cambiar email de recuperación, exportar correo, borrar cuenta (vía script privilegiado `mail-provision`)

## Fase 4 · Planes de pago (Stripe)

- [ ] Sincronización planes/ofertas ↔ Stripe desde el admin (Product, Price, Coupon); cada cambio de precio crea un `Price` nuevo y los clientes existentes conservan el suyo
- [ ] Sobrecoste de nombre corto como línea periódica extra en la suscripción (solo planes de pago)
- [ ] Stripe Checkout en el onboarding y al cambiar de plan; desistimiento de 14 días
- [ ] Webhooks idempotentes (`checkout.session.completed`, `invoice.*`, `customer.subscription.*`)
- [ ] Stripe Customer Portal: tarjetas, facturas, cancelación
- [ ] Ciclo de vida según D-009: impago → aviso → suspensión → borrado → cuarentena del nombre → nombre libre (timer que ejecuta `mail-provision`)
- [ ] Política de inactividad de cuentas gratis (según D-009)
- [ ] Subir/bajar de plan ajusta cuota, `tier` y límites al momento
- [ ] IVA (Stripe Tax o fijo) y facturas; revisar Verifactu con la gestoría
- [ ] Evento de servidor `subscription_started` + conversión de pago a las plataformas de anuncios
- [ ] Monitorizar webhooks fallidos

## Fase 5 · Optimización y crecimiento

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
