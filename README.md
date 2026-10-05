# mail-server

Servicio de hosting de correo con un front PHP + Stripe (pendiente) para el alta y gestión de cuentas.

- **Servidor:** Hetzner VPS, Ubuntu 22.04 LTS
- **DNS:** Cloudflare (MX, SPF, DKIM y DMARC publicados ahí)

## Stack

| Componente | Función |
|---|---|
| **Postfix** | SMTP: recepción en el 25, envío autenticado en el 587 (STARTTLS) y el 465 (TLS implícito). Entrega local por LMTP a Dovecot. |
| **Dovecot** | IMAPS (solo el 993; el 143 está desactivado), autenticación SASL para Postfix, cuotas y Sieve. |
| **MariaDB** | BD `mailserver` (`domains`, `mailboxes`, `aliases`), leída por Dovecot y Postfix. BD `roundcube` para el webmail. |
| **Rspamd + Redis** | Antispam, firma DKIM (selector `mail`) y límite de envío por usuario. |
| **unbound** | Resolver DNS local para Rspamd y las comprobaciones de blacklists. |
| **Roundcube 1.5** | Webmail sobre nginx + PHP-FPM 8.1 (pool `webmail`). |
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
- Probado con mail-tester (10/10) y con envíos reales a Gmail y Outlook, que llegan a la bandeja de entrada.

### Spam

- Rspamd rechaza el spam claro (por ejemplo, GTUBE) y marca el dudoso con `X-Spam: yes`.
- Un script Sieve global (`/etc/dovecot/sieve/spam-to-junk.sieve`, configurado como `sieve_before`) mueve el correo marcado a la carpeta **Junk**.

## *Monkey noises* y cosas a tener en cuenta

### Roundcube 1.5: nombres de opciones

El paquete de Ubuntu es **Roundcube 1.5.0**, que usa `default_host`, `smtp_server` y `smtp_port`. Las opciones `imap_host` y `smtp_host` son de la **1.6** y la 1.5 las **ignora sin avisar**: se conecta a `localhost` sin TLS y el envío falla con "Ha fallado la autenticación". La configuración está en `/etc/roundcube/config.inc.php`. Si se actualiza a 1.6, hay que renombrar las opciones.

### IMAP `ssl://` frente a SMTP `tls://`

En Roundcube, el 993 va con `ssl://` (TLS desde el primer byte) y el 587 con `tls://` (STARTTLS). Son esquemas distintos y Roundcube no da un error claro si se confunden.

### PHP-FPM del webmail

- El pool `webmail` corre como el usuario `webmail` (uid 5001), no como `www-data`. Para depurar, usa `sudo -u webmail php ...`, porque con `www-data` el resultado no es fiable.
- `webmail` está en el grupo `www-data` para poder leer `/etc/roundcube`.
- `/var/lib/roundcube/temp` pertenece a `webmail`. Si no puede escribir ahí, los adjuntos fallan.
- El pool tiene `open_basedir` limitado a las rutas de Roundcube.

### Dovecot: orden de configuración

La configuración global está en `/etc/dovecot/local.conf`, que se carga **después** de `conf.d/`. Cualquier bloque `protocol lmtp { mail_plugins = ... }` tiene que ir en `local.conf` detrás de la línea global `mail_plugins = $mail_plugins quota`; si no, LMTP pierde el plugin de cuota sin avisar.

### Firewall: iptables-persistent, no ufw

- ufw está **desactivado**. Mostraba reglas "ALLOW" que no estaban cargadas en el kernel. Comprueba siempre con `iptables -S INPUT`.
- Las reglas se editan a mano en `/etc/iptables/rules.v4` y `rules.v6`, y se aplican con `iptables-restore`.
- **No ejecutar nunca `netfilter-persistent save`**: guardaría también las cadenas temporales `f2b-*` de fail2ban.

### fail2ban ignora la IP del propio servidor

Roundcube se autentica contra Dovecot y Postfix desde la IP del servidor. Esa IP está en `ignoreip` para que los errores de contraseña en el webmail no bloqueen el webmail entero.

### Spamhaus necesita resolver propio

Spamhaus rechaza las consultas que llegan por los resolvers compartidos de Hetzner: responde `127.255.255.254` y los filtros RBL dejan de funcionar sin dar ningún aviso. Por eso Rspamd usa **unbound** en `127.0.0.1` (`/etc/rspamd/local.d/options.inc`).

### nginx y el reto ACME

Un `return 301` suelto en un bloque `server {}` se ejecuta antes que cualquier `location`, incluso una `^~`, y rompe la validación de certbot. La redirección de HTTP a HTTPS tiene que ir dentro de `location / { return 301 ...; }`.

### Certificados

El hook `/etc/letsencrypt/renewal-hooks/deploy/reload-mail.sh` recarga nginx, Dovecot y Postfix tras cada renovación. La monitorización comprueba el certificado que **sirve** cada puerto, no el del disco, así que también detecta si algún servicio no se recargó.

### Paquetes eliminados a propósito

- snapd está desinstalado y bloqueado en `/etc/apt/preferences.d/no-snapd`.
- El journal de systemd está limitado a 200 MB (`/etc/systemd/journald.conf.d/size.conf`).
- El servidor solo ejecuta el correo. La aplicación antigua que también vivía aquí (Sapphira) se movió a otro servidor.

## Operación

| Qué | Dónde |
|---|---|
| Backup | `/usr/local/sbin/mail-backup`, timer `mail-backup.timer` (diario a las 03:30). Repositorio restic en `/var/backups/mail-restic`, con retención de 7 diarios, 4 semanales y 6 mensuales. Incluye `/var/vmail`, volcados de las dos BD, claves DKIM y la configuración de los servicios. |
| Contraseña de restic | `/etc/mail-backup/restic.pass` (también guardada fuera del servidor) |
| Monitorización | `/usr/local/sbin/mail-monitor`, timer `mail-monitor.timer` (cada 10 min). Comprueba servicios, cola de Postfix (> 50), disco (> 85 %), caducidad de certificados por puerto (< 14 días), antigüedad del backup (> 26 h) y blacklists de la IP y del dominio. Los umbrales están al principio del script. |
| Alertas | `/usr/local/sbin/mail-alert` → Telegram (`/etc/mail-monitor/telegram.env`). Avisa una vez por problema, lo repite cada 6 h mientras siga y avisa cuando se resuelve. |
| Vigilante externo | Healthchecks.io (`/etc/mail-monitor/healthchecks.env`). Recibe un ping en cada pasada del monitor y avisa por Telegram si deja de recibirlos. |
| Logs útiles | `/var/log/mail.log`, `/var/log/roundcube/`, `journalctl -t mail-alert`, `fail2ban-client status <jail>` |

## TODO back-end

- [ ] **Backups fuera del servidor.** Ahora el repositorio restic está en el mismo disco: protege contra borrados y errores, pero no si se pierde el servidor. Cuando haya usuarios, añadir un destino externo (por ejemplo, un Hetzner Storage Box) con `restic copy`. No hay que cambiar nada más.
- [ ] **Claves DKIM por dominio** cuando se admitan dominios propios de clientes: generar la clave en `/var/lib/rspamd/dkim/<dominio>.mail.key` y publicar el registro DNS.
- [ ] *(Opcional)* Valorar actualizar Roundcube de 1.5 a 1.6 (ver [Roundcube 1.5: nombres de opciones](#roundcube-15-nombres-de-opciones)).

## TODO front-end

<!-- Pendiente de rellenar -->
