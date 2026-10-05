---
name: mail-server-ops
description: Cómo conectarse e inspeccionar o cambiar el servidor de correo de producción (Hetzner, unagrandeylibre.es) de forma segura. Usar antes de cualquier comando SSH contra el servidor, al tocar Postfix/Dovecot/Rspamd/Roundcube/nginx/MariaDB, o al desplegar el portal.
---

# Operar el servidor de correo

Servidor **único y de producción**, con buzones reales. Lee también los *monkey noises* del [README](../../../README.md): casi todos son trampas que fallan sin avisar.

## Acceso

```bash
ssh root@128.140.5.221
```

Usa `-o BatchMode=yes` desde scripts/IA para que falle en vez de quedarse pidiendo contraseña.

## Reglas

1. **Leer es libre; cambiar requiere confirmación del usuario.** Antes de cambiar: enseña el diff/comando y espera el sí.
2. **Nunca leas ni muestres secretos**: hashes de `mailboxes.password`, `des_key` de Roundcube, `/etc/mail-backup/restic.pass`, `/etc/mail-monitor/*.env`, claves DKIM, `.env` del portal.
3. Antes de editar un fichero de configuración, copia de seguridad al lado: `cp f f.bak.$(date +%F)`.
4. Valida antes de recargar: `postfix check`, `doveconf -n >/dev/null`, `nginx -t`, `rspamadm configtest`. Recarga (`reload`), no reinicies, salvo que haga falta.
5. Firewall: editar `/etc/iptables/rules.v4|v6` + `iptables-restore`. **Nunca** `netfilter-persistent save` ni `ufw`.
6. Dovecot: los bloques `protocol lmtp {}` van en `/etc/dovecot/local.conf` detrás de la línea global de `mail_plugins`.
7. Roundcube es la **1.6** (`imap_host`/`smtp_host`) sobre PHP **8.3**. Depurar como `sudo -u webmail php8.3`.
8. Cambios en BD `mailserver`: preferir el usuario `portal` (permisos mínimos) a root. Nunca `DELETE` de buzones a mano sin backup reciente (`restic snapshots`).
9. Si un cambio afecta a cómo llega o sale el correo, pruébalo después: envío a un buzón externo y `tail -f /var/log/mail.log`.
10. Tras cualquier cambio: `server/pull-config.sh` y revisa `git diff server/config`. Se sube junto a la documentación del cambio.
11. Documenta lo aprendido en el README (*monkey noises*) y lo decidido en "Decisiones tomadas" de `docs/ROADMAP.md`.

## Comprobaciones rápidas (solo lectura)

```bash
systemctl --failed
/usr/local/sbin/mail-monitor            # el mismo chequeo que corre cada 10 min
mysql mailserver -e "SELECT d.name, m.local_part, m.tier, m.active FROM mailboxes m JOIN domains d ON d.id=m.domain_id"
mailq | tail -1
iptables -S INPUT
```
