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

### Paquetes eliminados a propósito

- snapd está desinstalado y bloqueado en `/etc/apt/preferences.d/no-snapd`.
- El journal de systemd está limitado a 200 MB (`/etc/systemd/journald.conf.d/size.conf`).
- El servidor solo ejecuta el correo. La aplicación antigua que también vivía aquí (Sapphira) se movió a otro servidor. Sus últimos restos (servicio `whatsapp-statics`, `nginx/conf.d/wordpress.inc`, preferencias apt de Node/N|Solid) se retiraron el 2026-10-05 y están guardados en `/root/sapphira-leftovers` por si acaso.

## Operación

| Qué | Dónde |
|---|---|
| Backup | `/usr/local/sbin/mail-backup`, timer `mail-backup.timer` (diario a las 03:30). Repositorio restic en `/var/backups/mail-restic`, con retención de 7 diarios, 4 semanales y 6 mensuales. Incluye `/var/vmail`, volcados de las dos BD, claves DKIM y la configuración de los servicios. |
| Contraseña de restic | `/etc/mail-backup/restic.pass` (también guardada fuera del servidor) |
| Monitorización | `/usr/local/sbin/mail-monitor`, timer `mail-monitor.timer` (cada 10 min). Comprueba servicios, cola de Postfix (> 50), disco (> 85 %), caducidad de certificados por puerto (< 14 días), antigüedad del backup (> 26 h) y blacklists de la IP y del dominio. Los umbrales están al principio del script. |
| Alertas | `/usr/local/sbin/mail-alert` → Telegram (`/etc/mail-monitor/telegram.env`). Avisa una vez por problema, lo repite cada 6 h mientras siga y avisa cuando se resuelve. |
| Vigilante externo | Healthchecks.io (`/etc/mail-monitor/healthchecks.env`). Recibe un ping en cada pasada del monitor y avisa por Telegram si deja de recibirlos. |
| Web (`portal/`) | `https://unagrandeylibre.es` (`www` redirige al apex; `autoconfig.` y `autodiscover.` tienen certificado y devuelven 404 hasta la Fase 2). Releases en `/var/www/portal/releases`, la activa en `current`; `.env` y `storage/` en `/var/www/portal/shared`. Usuario y pool FPM `portal`. Certificado propio (`--cert-name unagrandeylibre.es`), independiente del del correo. |
| Colas y tareas de la web | Worker `portal-queue.service` (`queue:work` como `portal`, se recicla cada hora; el deploy lo reinicia con `queue:restart`). Scheduler `portal-schedule.timer` → `schedule:run` cada minuto. Ver: `journalctl -u portal-queue`, `journalctl -u portal-schedule`. Trabajos fallidos: tabla `portal.failed_jobs`. |
| Desplegar la web | `portal/deploy.sh` desde tu máquina, con todo ya subido a GitHub (el servidor clona el repo público). `portal/deploy.sh --rollback` vuelve a la release anterior. El script del servidor es `server/bin/portal-deploy` → `/usr/local/sbin/portal-deploy`. |
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
