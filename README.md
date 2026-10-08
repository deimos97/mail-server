# mail-server

Servicio de hosting de correo con un front PHP + Stripe (pendiente) para el alta y gestión de cuentas.

- **Servidor:** Hetzner VPS, Ubuntu 24.04 LTS (actualizado desde 22.04 el 2026-10-05)
- **DNS:** Cloudflare (MX, SPF, DKIM y DMARC publicados ahí)

## Stack

| Componente | Función |
|---|---|
| **Postfix** | SMTP: recepción en el 25, envío autenticado en el 587 (STARTTLS) y el 465 (TLS implícito). Entrega local por LMTP a Dovecot. |
| **Dovecot** | IMAPS (solo el 993; el 143 está desactivado), autenticación SASL para Postfix, cuotas y Sieve. |
| **MariaDB** | BD `mailserver` (`domains`, `mailboxes`, `aliases`), leída por Dovecot y Postfix. BD `roundcube` para el webmail. |
| **Rspamd + Redis** | Antispam, firma DKIM (selector `mail`) y límite de envío por usuario. |
| **unbound** | Resolver DNS local para Rspamd y las comprobaciones de blacklists. |
| **Roundcube 1.6** | Webmail sobre nginx + PHP-FPM 8.3 (pool `webmail`). |
| **Let's Encrypt** | Un certificado con SAN  |
| **fail2ban** | Jails para SSH, Postfix SASL, Dovecot y Roundcube. |
| **iptables** | Firewall (iptables-persistent). |
| **restic** | Backups diarios en un repositorio local. |
| **mail-monitor** | Comprobaciones de salud cada 10 min con alertas por Telegram, más Healthchecks.io como vigilante externo. |

### Usuarios virtuales

- Los buzones están en `/var/vmail` (uid/gid 5000, usuario `vmail`), en formato Maildir.
- La tabla `mailboxes` tiene una columna `tier`. Está pensada para que el backend de altas cree una cuenta con un simple `INSERT`.

### Correo saliente

- SPF `v=spf1 mx -all`, DKIM firmado por Rspamd y DMARC `p=quarantine`.
- El PTR de la IP apuntado.
- `reject_sender_login_mismatch` impide enviar con un remitente distinto del usuario autenticado.
- Tamaño máximo por mensaje: **35 MB** (`message_size_limit`), en los dos sentidos. Los adjuntos crecen ~37 % al codificarse en base64, así que caben unos 25 MB de adjuntos, como en Gmail. Los límites de PHP del webmail (25 MB por adjunto) van a juego; si se cambia uno, revisa el otro.
- Probado con mail-tester (10/10) y con envíos reales a Gmail y Outlook, que llegan a la bandeja de entrada.

### Spam

- Rspamd rechaza el spam claro (por ejemplo, GTUBE) y marca el dudoso con `X-Spam: yes`.
- Un script Sieve global (`/etc/dovecot/sieve/spam-to-junk.sieve`, configurado como `sieve_before`) mueve el correo marcado a la carpeta **Junk**.

## *Monkey noises* y cosas a tener en cuenta

### Roundcube: nombres de opciones según la versión

Ahora es **Roundcube 1.6**, que usa `imap_host` y `smtp_host` (con el puerto dentro: `tls://127.0.0.1:587`). La 1.5 usaba `default_host`, `smtp_server` y `smtp_port`, e **ignoraba sin avisar** las de la 1.6: se conectaba a `localhost` sin TLS y el envío fallaba con "Ha fallado la autenticación". Al cambiar de versión, revisa siempre estas opciones en `/etc/roundcube/config.inc.php`.

### IMAP `ssl://` frente a SMTP `tls://`

En Roundcube, el 993 va con `ssl://` (TLS desde el primer byte) y el 587 con `tls://` (STARTTLS). Son esquemas distintos y Roundcube no da un error claro si se confunden.

### PHP-FPM del webmail

- El pool `webmail` corre como el usuario `webmail` (uid 5001), no como `www-data`. Para depurar, usa `sudo -u webmail php8.3 ...`, porque con `www-data` el resultado no es fiable.
- `webmail` está en el grupo `www-data` para poder leer `/etc/roundcube`.
- `/var/lib/roundcube/temp` pertenece a `webmail`. Si no puede escribir ahí, los adjuntos fallan ("unable to create a temporary file" en `/var/log/nginx/error.log`). **El paquete de Roundcube la devuelve a `www-data` al actualizarse**; `/etc/tmpfiles.d/roundcube-webmail.conf` la corrige en cada arranque (junto con `/var/log/roundcube`). Si se actualiza Roundcube sin reiniciar: `systemd-tmpfiles --create /etc/tmpfiles.d/roundcube-webmail.conf`.
- Los límites de subida (25 MB por adjunto, 32 MB por envío, 256 MB de memoria) están en el **pool**, no en `php.ini`: al pasar de PHP 8.1 a 8.3 el `php.ini` nuevo volvió a los 2 MB por defecto.
- El pool tiene `open_basedir` limitado a las rutas de Roundcube. **Roundcube 1.6 de Ubuntu enlaza Bootstrap a `/usr/share/nodejs/bootstrap/`**, que también tiene que estar en la lista: si no, la interfaz carga sin el JS de Bootstrap y enviar, adjuntar o abrir diálogos se queda colgado aunque el correo salga (aviso `realpath(): open_basedir restriction` en `/var/log/nginx/error.log`). Tras actualizar Roundcube, busca enlaces que salgan de las rutas permitidas: `find /usr/share/roundcube -type l -exec readlink -f {} \;`.
- `/var/log/roundcube` y sus logs pertenecen a `webmail`. La rotación diaria (`/etc/logrotate.d/roundcube-core`) los recreaba como `www-data` y el webmail dejaba de poder escribir en ellos; ahora crea con `webmail adm`, y `tmpfiles.d` corrige el propietario de la carpeta al arrancar.
- El pool vive en `/etc/php/8.3/fpm/pool.d/webmail.conf`. El pool `www` por defecto está retirado. El socket (`/run/php/webmail.sock`) no lleva la versión en el nombre: al cambiar de PHP basta con mover el fichero del pool.

### Postfix: en `master.cf`, los `-o` no admiten espacios

`-o smtpd_sender_restrictions=check_sasl_access mysql:…` rompe el servicio: Postfix toma lo que va después del espacio como otro argumento y **el proceso del 587/465 no arranca** (`fatal: unexpected command-line argument`), aunque `postfix check` diga que todo está bien. Pasó el 2026-10-07 (3,5 minutos sin envío). La forma correcta es definir el valor en `main.cf` (`submission_sender_restrictions = …`) y en `master.cf` poner `-o smtpd_sender_restrictions=$submission_sender_restrictions`. **Después de tocar `master.cf`, prueba una conexión real** (`openssl s_client -starttls smtp -connect 127.0.0.1:587`) y mira `mail.log`.

### Contraseñas por dispositivo y bloqueo de envío

- Dovecot tiene dos `passdb`: la contraseña del buzón (`dovecot-sql.conf.ext`, interna; nadie la conoce en los buzones creados por la web) y las contraseñas por dispositivo (`dovecot-sql-app-passwords.conf.ext`, tabla `app_passwords`). Si la primera falla, prueba la segunda. El SMTP autentica por Dovecot, así que vale para los dos.
- Dovecot no puede probar varias filas por usuario: por eso cada contraseña de dispositivo empieza por un **selector** público de 6 caracteres que localiza su fila (`LEFT('%w', 6)`), y luego se compara el hash bcrypt de la contraseña completa.
- Las dos exigen `mailboxes.status = 'active'`. Un buzón `suspended` no entra ni envía, pero **sigue recibiendo** (el `user_query` solo mira `active`).
- `can_send = 0` (falta verificar el email de recuperación): Postfix rechaza el envío en el 587 y el 465 con `check_sasl_access mysql:/etc/postfix/mysql/sasl-can-send.cf` (`submission_sender_restrictions` en `main.cf`).

### Login único con el webmail (OAuth2)

- La web es el proveedor OAuth2 (`portal/app/Services/OAuthServer.php`). Roundcube (`oauth_*` al final de `/etc/roundcube/config.inc.php`) manda al usuario a `https://unagrandeylibre.es/oauth/authorize`, canjea el código en `/api/oauth/token` y entra en IMAP y SMTP con **XOAUTH2**. El secreto del cliente está en ese fichero y en `OAUTH_WEBMAIL_CLIENT_SECRET` del `.env` de la web: si se cambia, en los dos.
- Dovecot valida el token en `/api/oauth/introspect` (`/etc/dovecot/dovecot-oauth2-portal.conf.ext`), que la web solo acepta desde las IPs del servidor (`OAUTH_INTROSPECTION_IPS`).
- La `passdb oauth2` va **antes** que las de contraseña (`conf.d/auth-oauth2-portal.conf.ext`, incluido en `10-auth.conf` delante de `auth-sql.conf.ext`), solo para `xoauth2 oauthbearer` y con `result_failure = return-fail`. Roundcube se autentica en cada clic: si fuera la última, cada clic gastaría dos bcrypt para nada.
- **Roundcube vuelve a `/index.php/login/oauth` y saca la tarea y la acción de `PATH_INFO`.** El nginx del webmail tiene un `location ~ ^/index\.php(/.*)$` con `fastcgi_split_path_info` para eso. Sin él, la vuelta muestra el formulario de login normal (200) y el login único no arranca, sin ningún error.
- Un buzón `suspended` o un cambio de contraseña en la web cortan el webmail al momento (los tokens se revocan o dejan de validar).

### Probar con `artisan tinker` como el usuario `portal`

`sudo -E -u portal php8.3 artisan tinker` falla en silencio: con `-E` se queda el `HOME` de root y Tinker no arranca (solo deja un aviso de psysh). Para pasar variables: `sudo -u portal -H env VAR=valor php8.3 artisan tinker --execute='…getenv("VAR")…'`.

### Roundcube: skin propia sobre Elastic

La skin `unagrandeylibre` (fuente en `server/roundcube/skins/`) es Elastic con nuestros colores: solo cambia variables (`styles/_variables.less`) y se compila en local con `server/roundcube/build-skin.sh`, que la sube a `/var/lib/roundcube/skins/` (el servidor no tiene Node). Trampas:

- Roundcube busca los CSS en la skin donde encuentra `templates/includes/layout.html`. Sin una copia propia de ese fichero, una skin que extiende Elastic **sigue cargando los CSS de Elastic** aunque esté activa. El script la copia del servidor en cada compilación.
- El firewall corta SSH a partir de 10 conexiones nuevas por minuto desde la misma IP (`-m recent`, 60 s): los scripts que hacen varias llamadas usan una sola conexión (`ControlMaster`).
- Tras actualizar Roundcube, vuelve a ejecutar `build-skin.sh`: compila contra los `.less` y la plantilla de la versión instalada.
- `skins_allowed` y `dont_override` fijan la skin para todos (si no, un usuario con la preferencia guardada seguiría en Elastic).

### Scripts privilegiados para la web (`mail-provision`)

La web no corre como root. Lo que lo necesita (firmar el perfil de Apple con la clave del certificado, `doveadm kick`, borrar el correo de un buzón) va por `/usr/local/sbin/mail-provision` (fuente: `server/bin/`), que `portal` puede ejecutar con `sudo` y nada más (`/etc/sudoers.d/portal-mail-provision`). El script valida todo lo que recibe y comprueba en la BD que el buzón exista (y, para borrar, que la web ya lo haya marcado como borrado; los de `tier=system` nunca).

- El worker de colas (`portal-queue.service`) tiene `NoNewPrivileges=yes`, así que **no puede usar sudo**: estas llamadas se hacen en la propia petición (o con `dispatchAfterResponse`), no en la cola.
- `doveadm backup` a mbox falla con "Mail locations must use the same hierarchy separator": hay que pasar `-o namespace/inbox/separator=/` (Maildir usa `.`, el mbox con `LAYOUT=fs`, `/`).
- `openssl smime -verify` dice "unsuitable certificate purpose" con el perfil firmado: los certificados de Let's Encrypt no llevan el uso S/MIME. A iOS le da igual; para comprobarlo, `openssl cms -verify -purpose any`.

### Uso de buzones y último acceso (Dovecot → MariaDB)

`quota_clone` copia el uso de cada buzón a `mailserver.quota_usage` y `last_login` guarda el último acceso IMAP en `mailserver.last_logins`, los dos por el servicio dict (usuario MariaDB `dovecot_dict`, `/etc/dovecot/dovecot-dict-sql.conf.ext`). Trampas:

- `quota_clone` solo escribe cuando cambia el buzón (llega o se borra un correo). `doveadm quota recalc` **no** lo dispara: un buzón sin movimiento no tiene fila (la web lo muestra vacío).
- Para saber qué dispositivo entra, la passdb de contraseñas de dispositivo devuelve `a.id AS userdb_app_password_id` y la clave es `last-login/%u/%{userdb:app_password_id:0}` (0 = sin contraseña de dispositivo, o sea, el webmail con login único).
- El socket del dict tiene que ser del usuario `vmail` (`service dict { unix_listener dict { user = vmail } }`); si no, los procesos IMAP no pueden escribir.

### Pagos con Stripe (Fase 4)

- El webhook es `https://unagrandeylibre.es/stripe/webhook`, con los eventos de `portal/config/cashier.php`. Si se añade un evento, hay que añadirlo también al endpoint en Stripe (Developers → Webhooks). El secreto (`STRIPE_WEBHOOK_SECRET`) es distinto en sandbox y en live.
- Tras cambiar de claves (p. ej. de sandbox a live): `php artisan stripe:sync` crea en la cuenta nueva los productos, precios, cupones, el IVA y la configuración del portal de cliente. Los IDs viejos de la BD dejan de valer: limpiar antes `stripe_*` de `plans`, `plan_offers` y `name_price_tiers`.
- El scheduler y el worker corren con `NoNewPrivileges` (no pueden usar sudo): el ciclo de vida solo **marca** los buzones como borrados y el correo lo borra `mail-purge` (root) esa noche.
- Rspamd lee de la web las listas de buzones de pago (`/etc/rspamd/rspamd.local.lua`, mapas `ugl_*`). Si la web no responde, se queda con la última lista que bajó; si nunca la bajó, todos van al cubo del plan gratis. Si un tier no tiene cubo en `ratelimit.conf`, también va al gratis.

### Correo de la web (`noreply@`)

La web envía como `noreply@unagrandeylibre.es` por el 587 (la contraseña solo está en `/var/www/portal/shared/.env`, `MAIL_PASSWORD`). Está en `whitelisted_user` de `/etc/rspamd/local.d/ratelimit.conf` para que el límite de 40/h por usuario no frene las verificaciones. Los rebotes llegan a ese buzón.

### Dovecot: orden de configuración

La configuración global está en `/etc/dovecot/local.conf`, que se carga **después** de `conf.d/`. Cualquier bloque `protocol lmtp { mail_plugins = ... }` tiene que ir en `local.conf` detrás de la línea global `mail_plugins = $mail_plugins quota`; si no, LMTP pierde el plugin de cuota sin avisar.

### Firewall: iptables-persistent, no ufw

- ufw está **desactivado**. Mostraba reglas "ALLOW" que no estaban cargadas en el kernel. Comprueba siempre con `iptables -S INPUT`.
- Las reglas se editan a mano en `/etc/iptables/rules.v4` y `rules.v6`, y se aplican con `iptables-restore`.
- **No ejecutar nunca `netfilter-persistent save`**: guardaría también las cadenas temporales `f2b-*` de fail2ban.
- **Al actualizar `iptables-persistent`** (por ejemplo en un `do-release-upgrade`), el paquete vuelve a guardar las reglas cargadas en ese momento y machaca los ficheros escritos a mano. En el upgrade a 24.04 pasó y no se colaron cadenas `f2b-*`, pero se perdieron los comentarios. Después de cualquier upgrade, revisa `rules.v4|v6` (con `server/pull-config.sh` se ve en el diff).

### fail2ban ignora la IP del propio servidor

Roundcube se autentica contra Dovecot y Postfix desde la IP del servidor. Esa IP está en `ignoreip` para que los errores de contraseña en el webmail no bloqueen el webmail entero.

### Spamhaus necesita resolver propio

Spamhaus rechaza las consultas que llegan por los resolvers compartidos de Hetzner: responde `127.255.255.254` y los filtros RBL dejan de funcionar sin dar ningún aviso. Por eso Rspamd usa **unbound** en `127.0.0.1` (`/etc/rspamd/local.d/options.inc`).

### nginx y el reto ACME

Un `return 301` suelto en un bloque `server {}` se ejecuta antes que cualquier `location`, incluso una `^~`, y rompe la validación de certbot. La redirección de HTTP a HTTPS tiene que ir dentro de `location / { return 301 ...; }`.

### Certificados

El hook `/etc/letsencrypt/renewal-hooks/deploy/reload-mail.sh` recarga nginx, Dovecot y Postfix tras cada renovación. La monitorización comprueba el certificado que **sirve** cada puerto, no el del disco, así que también detecta si algún servicio no se recargó.

### Rspamd viene de su propio repositorio, compilado para cada versión de Ubuntu

El repositorio de rspamd.com lleva el nombre de la versión (`noble`). Un `do-release-upgrade` lo desactiva (lo renombra a `rspamd.list.distUpgrade`) y deja instalado el paquete de la versión anterior, que **no arranca**. Postfix tiene `milter_default_action = tempfail`, así que mientras Rspamd está caído todo el correo entrante recibe un `451` (los remitentes reintentan; no se pierde nada, pero tampoco entra). Tras un upgrade: reactivar el repositorio con el nombre nuevo, `apt install rspamd`, `rspamadm configtest` y `systemctl enable --now rspamd`.

### apache2 instalado pero enmascarado

`roundcube-core` arrastra `apache2` como dependencia. Si arranca, choca con nginx por el puerto 80 y queda en estado fallido. Está **enmascarado** (`systemctl mask apache2`), no desinstalado, porque quitarlo se llevaría Roundcube por delante.

### Actualizar Ubuntu (`do-release-upgrade`)

- Hetzner usa su propio mirror (`mirror.hetzner.com`). El asistente pregunta si reescribir `sources.list`: hay que contestar **sí**.
- Ficheros de configuración modificados: contestar siempre **mantener la versión actual** y ajustar después.
- La BD de Roundcube se migra sola con dbconfig-common, que deja una copia en `/var/cache/dbconfig-common/backups`.
- Durante el upgrade, SSH rechaza conexiones nuevas un rato (se actualiza `openssh-server`). No cierres la sesión abierta hasta comprobar que una conexión nueva entra.
- Después: Rspamd, el pool PHP, las opciones de Roundcube, las reglas de iptables (ver arriba) y `server/pull-config.sh` para ver el diff.
- Hazlo siempre con un **snapshot de Hetzner** recién hecho.

### nginx: `gzip_types` y caché de la web

- En `/etc/nginx/nginx.conf` había una errata (`application/x-javascriptapplication/xml`, sin espacio) que dejaba el XML sin comprimir. Corregida el 2026-10-06.
- La web sirve `/build/` (CSS, JS y fuentes con hash en el nombre) con `expires max`. Se usa `expires` y no `add_header Cache-Control` porque un `add_header` dentro de un `location` anula todos los del `server` (HSTS, nosniff…).

### Paquetes eliminados a propósito

- snapd está desinstalado y bloqueado en `/etc/apt/preferences.d/no-snapd`.
- El journal de systemd está limitado a 200 MB (`/etc/systemd/journald.conf.d/size.conf`).
- El servidor solo ejecuta el correo. La aplicación antigua que también vivía aquí (Sapphira) se movió a otro servidor. Sus últimos restos (servicio `whatsapp-statics`, `nginx/conf.d/wordpress.inc`, preferencias apt de Node/N|Solid) se retiraron el 2026-10-05 y están guardados en `/root/sapphira-leftovers` por si acaso.

## Operación

| Qué | Dónde |
|---|---|
| Backup | `/usr/local/sbin/mail-backup`, timer `mail-backup.timer` (diario a las 03:30). Repositorio restic en `/var/backups/mail-restic`, con retención de 7 diarios y 4 semanales (~1 mes, D-015: así el correo de las cuentas borradas sale de las copias en ese plazo). Incluye `/var/vmail`, volcados de las dos BD, claves DKIM y la configuración de los servicios. |
| Contraseña de restic | `/etc/mail-backup/restic.pass` (también guardada fuera del servidor) |
| Monitorización | `/usr/local/sbin/mail-monitor`, timer `mail-monitor.timer` (cada 10 min). Comprueba servicios, cola de Postfix (> 50), disco (> 85 %), caducidad de certificados por puerto (< 14 días), antigüedad del backup (> 26 h) y blacklists de la IP y del dominio. Los umbrales están al principio del script. |
| Alertas | `/usr/local/sbin/mail-alert` → Telegram (`/etc/mail-monitor/telegram.env`). Avisa una vez por problema, lo repite cada 6 h mientras siga y avisa cuando se resuelve. |
| Vigilante externo | Healthchecks.io (`/etc/mail-monitor/healthchecks.env`). Recibe un ping en cada pasada del monitor y avisa por Telegram si deja de recibirlos. |
| Web (`portal/`) | `https://unagrandeylibre.es` (`www` redirige al apex; `autoconfig.` y `autodiscover.` tienen certificado y devuelven 404 hasta la Fase 2). Releases en `/var/www/portal/releases`, la activa en `current`; `.env` y `storage/` en `/var/www/portal/shared`. Usuario y pool FPM `portal`. Certificado propio (`--cert-name unagrandeylibre.es`), independiente del del correo. |
| Analítica (PostHog) | La web solo carga PostHog si el visitante acepta cookies. Va por `https://unagrandeylibre.es/ingest/…`, un proxy en el nginx de la web hacia `eu.i.posthog.com` y `eu-assets.i.posthog.com` (resolución con unbound; sin reenviar cookies). Si PostHog deja de recibir datos, revisar ese bloque. |
| Colas y tareas de la web | Worker `portal-queue.service` (`queue:work` como `portal`, se recicla cada hora; el deploy lo reinicia con `queue:restart`). Scheduler `portal-schedule.timer` → `schedule:run` cada minuto. Ver: `journalctl -u portal-queue`, `journalctl -u portal-schedule`. Trabajos fallidos: tabla `portal.failed_jobs`. |
| Autoconfiguración de apps | `autoconfig.` y `autodiscover.unagrandeylibre.es` solo exponen `/mail/config-v1.1.xml` y `/autodiscover/autodiscover.xml` (sin distinguir mayúsculas), servidos por la web sin sesión (`routes/mail-clients.php`); el resto da 404. También `/.well-known/autoconfig/…` en el dominio principal. Datos de conexión en `portal/config/mail_clients.php`. |
| Desplegar la web | `portal/deploy.sh` desde tu máquina, con todo ya subido a GitHub (el servidor clona el repo público). `portal/deploy.sh --rollback` vuelve a la release anterior. El script del servidor es `server/bin/portal-deploy` → `/usr/local/sbin/portal-deploy`. |
| Borrado de buzones | La web marca el buzón como borrado y borra su correo (`mail-provision delete-content`); la fila se queda 90 días para que nadie coja el nombre. El timer `mail-purge.timer` (05:15) quita las filas caducadas (`journalctl -t mail-provision`). Nunca libera un nombre si queda correo en `/var/vmail`. |
| Copias del correo (exportar) | La web deja la petición en `/var/www/portal/shared/exports/requests/`; `mail-export.path` (root) lanza `mail-provision process-exports`, que deja el zip en `exports/files/` (como `portal`). Necesita el doble del buzón libre en `/var/tmp` más 2 GB; si no, falla y la web lo dice. `journalctl -t mail-provision`. |
| Pagos (Stripe) | Cuenta "Servicio Correo Minorista" (sandbox por ahora). Webhook `/stripe/webhook`; eventos procesados en `portal.stripe_events`; fallos en `storage/logs/laravel.log`. Catálogo: `php artisan stripe:sync`. Ciclo de vida diario a las 04:45 (`ciclo-de-vida` del scheduler). |
| Límites de envío por plan | `/etc/rspamd/local.d/ratelimit.conf` + `/etc/rspamd/rspamd.local.lua` (mapas desde `https://unagrandeylibre.es/internal/rspamd/{paid,basic,pro}.map`, solo desde el servidor). |
| Logs útiles | `/var/log/mail.log`, `/var/log/roundcube/`, `journalctl -t mail-alert`, `fail2ban-client status <jail>`, `/var/www/portal/shared/storage/logs/` |

## TODO back-end

- [ ] **Backups fuera del servidor.** Ahora el repositorio restic está en el mismo disco: protege contra borrados y errores, pero no si se pierde el servidor. Cuando haya usuarios, añadir un destino externo (por ejemplo, un Hetzner Storage Box) con `restic copy`. No hay que cambiar nada más.
- [ ] **Claves DKIM por dominio** cuando se admitan dominios propios de clientes: generar la clave en `/var/lib/rspamd/dkim/<dominio>.mail.key` y publicar el registro DNS.
- [x] **Actualizar a Ubuntu 24.04** (Roundcube 1.6, PHP 8.3, Postfix 3.8, Dovecot 2.3.21, MariaDB 10.11). Hecho el 2026-10-05.
- [ ] *(Opcional)* Purgar los restos de configuración de paquetes ya desinstalados (`dpkg -l | grep ^rc`: PHP 8.1, MariaDB 10.6, kernels 5.15, ufw).

## TODO front-end

Web en `unagrandeylibre.es` para darse de alta, elegir plan (gratis o de pago con Stripe) y gestionar los buzones. Visión en [docs/front/PRODUCT.md](docs/front/PRODUCT.md), diseño técnico en [docs/front/ARCHITECTURE.md](docs/front/ARCHITECTURE.md), decisiones pendientes en [docs/DECISIONS.md](docs/DECISIONS.md) y el detalle por tareas en [docs/ROADMAP.md](docs/ROADMAP.md).

- [ ] **Fase 0 · Cimientos:** actualizar el sistema operativo, anuncio de prueba, copia versionada y sin secretos de la configuración en `server/`, BD y usuario `portal`, nginx y certificado para el apex. Stack decidido: Laravel + Cashier + Filament en el mismo VPS; analítica con PostHog.
- [ ] **Fase 1 · Landing:** hero con bandera e input de nombre, disponibilidad en vivo, planes desde BD (gratis/pago, programables, ofertas), admin de planes, analítica con consentimiento, SEO.
- [ ] **Fase 2 · Alta gratis:** email de recuperación verificado, provisión del buzón, antiabuso, autoconfiguración de clientes de correo y tutoriales.
- [ ] **Fase 3 · Área de cliente:** varios buzones por usuario, cuota, contraseñas de aplicación, login único con el webmail.
- [ ] **Fase 4 · Pago:** Stripe Checkout, webhooks, Customer Portal, impagos, IVA y facturas.
- [ ] **Fase 5 · Optimización:** A/B, mapas de calor, contenido, WebMCP, 2FA.
- [ ] **Futuro:** más dominios, store de nombres/dominios, dominios propios.
