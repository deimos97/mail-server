#!/usr/bin/env bash
# Descarga del servidor una copia de la configuración, ya sin secretos, a server/config/.
#
# - El saneado se hace EN EL SERVIDOR: los secretos nunca salen de él.
# - Solo se copian ficheros propios o modificados respecto al paquete (los conffiles
#   intactos no aportan nada).
# - Después pasa gitleaks y una lista de patrones prohibidos. Si algo falla, no deja nada.
#
# Uso: server/pull-config.sh            (desde la raíz del repo)
set -euo pipefail

HOST="${MAIL_HOST:-root@128.140.5.221}"
DEST="$(cd "$(dirname "$0")" && pwd)/config"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

ssh -o BatchMode=yes "$HOST" 'bash -s' > "$TMP/config.tar" <<'REMOTE'
set -euo pipefail
OUT="$(mktemp -d)"
trap 'rm -rf "$OUT"' EXIT

# Rutas a revisar. Los directorios se recorren enteros.
PATHS=(
  /etc/postfix/main.cf /etc/postfix/master.cf /etc/postfix/mysql
  /etc/dovecot
  /etc/rspamd/local.d /etc/rspamd/override.d
  /etc/roundcube/config.inc.php
  /etc/nginx/nginx.conf /etc/nginx/sites-available /etc/nginx/conf.d /etc/nginx/snippets
  /etc/fail2ban/jail.local /etc/fail2ban/jail.d /etc/fail2ban/filter.d
  /etc/iptables
  /etc/unbound/unbound.conf.d
  /etc/php/*/fpm/pool.d
  /etc/mysql/mariadb.conf.d
  /etc/redis/redis.conf
  /etc/letsencrypt/renewal-hooks
  /etc/systemd/system/mail-backup.service /etc/systemd/system/mail-backup.timer
  /etc/systemd/system/mail-monitor.service /etc/systemd/system/mail-monitor.timer
  /etc/dovecot/conf.d/auth-oauth2-portal.conf.ext
  /etc/systemd/system/portal-queue.service /etc/systemd/system/portal-schedule.service /etc/systemd/system/portal-schedule.timer
  /etc/systemd/journald.conf.d /etc/tmpfiles.d/roundcube-webmail.conf
  /etc/apt/preferences.d/no-snapd /etc/logrotate.d/roundcube-core
  /usr/local/sbin/mail-alert /usr/local/sbin/mail-backup /usr/local/sbin/mail-monitor
)

# Nunca se copian (secretos puros o basura).
EXCLUDE_RE='(\.env$|\.env\.bak|restic\.pass|\.key$|\.pem$|\.crt$|/dkim/|\.svbin$|\.bak[-.]|\.ucf-|\.dpkg-|\.example$|~$|debian-db\.php$)'

# md5 de los conffiles tal y como vienen del paquete.
# Debian gestiona algunos (p. ej. los conf.d de Dovecot) con ucf en vez de dpkg.
declare -A ORIG
while read -r f sum _; do [ -n "${sum:-}" ] && ORIG["$f"]="$sum"; done < <(
  dpkg-query -W -f='${Conffiles}\n' 2>/dev/null | awk 'NF>=2 {print $1, $2}'
  awk '{print $2, $1}' /var/lib/ucf/hashfile 2>/dev/null)

for p in "${PATHS[@]}"; do
  [ -e "$p" ] || continue
  find "$p" -type f 2>/dev/null
done | sort -u | while read -r f; do
  [[ "$f" =~ $EXCLUDE_RE ]] && continue
  if [ -n "${ORIG[$f]:-}" ] && [ "$(md5sum < "$f" | cut -d' ' -f1)" = "${ORIG[$f]}" ]; then
    continue   # conffile sin tocar
  fi
  mkdir -p "$OUT$(dirname "$f")"
  cp -p "$f" "$OUT$f"
done

# Resúmenes efectivos (lo que de verdad está aplicado).
mkdir -p "$OUT/_effective"
postconf -n                    > "$OUT/_effective/postconf-n.txt"
doveconf -n 2>/dev/null        > "$OUT/_effective/doveconf-n.txt"
mysqldump --no-data --skip-dump-date --skip-comments mailserver \
  | sed -E 's/ AUTO_INCREMENT=[0-9]+//' > "$OUT/_effective/mailserver-schema.sql"
dpkg-query -W -f='${Package} ${Version}\n' \
  postfix dovecot-core rspamd roundcube-core nginx mariadb-server php8.1-fpm php8.3-fpm \
  fail2ban unbound redis-server restic certbot 2>/dev/null > "$OUT/_effective/versions.txt" || true
lsb_release -ds >> "$OUT/_effective/versions.txt"

# ---- Saneado. Se sustituye el valor, se conserva la clave para saber que existe. ----
R='__REDACTED__'
# Valores que no son secretos y se conservan: yes/no y variables (%p, %u, $VAR…).
find "$OUT" -type f -print0 | xargs -0 sed -i -E \
  -e "s#^([[:space:]]*[A-Za-z_]*[[:space:]]*[=:][[:space:]]*)(yes|no)[[:space:]]*\$#\1@@KEEP@@\2#I" \
  -e "s#^([[:space:]]*(password|passwd|pass|dbpass|secret|token|api_key|apikey|enable_password|requirepass|masterauth|ssl_key_password|auth_token)[[:space:]]*[=:][[:space:]]*)([^@%\$'\"[:space:]].*|['\"][^%\$'\"].*)#\1$R#I" \
  -e "s#(password|passwd|dbpass)=[^[:space:]\"';]+#\1=$R#Ig" \
  -e "s#(mysql|mysqli|pgsql|redis)://([^:/@]+):[^@]+@#\1://\2:$R@#g" \
  -e "s#(\\\$config\[['\"](des_key|db_dsnw|db_dsnr|oauth_client_secret)['\"]\][[:space:]]*=[[:space:]]*).*#\1'$R';#" \
  -e "s#(\\\$config\[['\"](smtp_pass|imap_pass)['\"]\][[:space:]]*=[[:space:]]*)'[^%'][^']*'#\1'$R'#" \
  -e "s#(connect[[:space:]]*=.*password=)[^[:space:]]+#\1$R#I" \
  -e "s#\\\$2[aby]\\\$[0-9]{2}\\\$[./A-Za-z0-9]{53}#$R#g" \
  -e "s#\{(BLF-CRYPT|SHA512-CRYPT|SHA256-CRYPT|ARGON2I|ARGON2ID|CRYPT|PLAIN|MD5)\}[^[:space:]'\"]+#{\1}$R#g" \
  -e "s#bot[0-9]{6,}:[A-Za-z0-9_-]{30,}#$R#g" \
  -e "s#https://hc-ping\.com/[A-Za-z0-9-]+#https://hc-ping.com/$R#g" \
  -e "s#@@KEEP@@##g"

tar -C "$OUT" -cf - .
REMOTE

# ---- Comprobaciones locales antes de aceptar nada ----
mkdir -p "$TMP/x"
tar -C "$TMP/x" -xf "$TMP/config.tar"

# Lo que no debería aparecer nunca, aunque gitleaks no lo detecte.
FORBIDDEN='BEGIN [A-Z ]*PRIVATE KEY|\$2[aby]\$[0-9]{2}\$[./A-Za-z0-9]{40}|\{BLF-CRYPT\}\$|bot[0-9]{6,}:[A-Za-z0-9_-]{30}|sk_(live|test)_[A-Za-z0-9]{10}|hc-ping\.com/[0-9a-f]{8}-'
if grep -rEn "$FORBIDDEN" "$TMP/x"; then
  echo "ERROR: patrones prohibidos en la exportación. No se copia nada." >&2
  exit 1
fi
# Líneas con pinta de secreto que no hayan quedado redactadas.
if grep -rEin '^[[:space:]]*[^#;]*(password|passwd|secret|des_key|token)[^=:]*[=:][[:space:]]*["'"'"']?[^_"'"'"'[:space:]$%{]' "$TMP/x" \
   | grep -v "__REDACTED__" | grep -vE '%[uwdn]|\$\{?[A-Za-z_]+|_file|_query|_scheme|password_query|passdb|default_pass|smtpd_sasl|smtp_sasl|args *=|driver *=|auth_mechanisms' ; then
  echo "ERROR: líneas sospechosas sin redactar (arriba). Revisa el saneado en pull-config.sh." >&2
  exit 1
fi
if command -v gitleaks >/dev/null; then
  gitleaks detect --no-git --source "$TMP/x" --no-banner --redact
else
  echo "AVISO: gitleaks no está instalado; solo se han aplicado las comprobaciones propias." >&2
fi

rm -rf "$DEST"
mv "$TMP/x" "$DEST"
echo "Configuración actualizada en $DEST"
