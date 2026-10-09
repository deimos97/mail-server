#!/bin/bash
# Prepara el VPS de copias (37.27.5.15) para recibir las copias externas del servidor de correo.
# Se ejecuta UNA vez desde tu máquina: bash server/backup-vps/setup.sh
#
# Crea solo esto en el VPS (no toca nada más: ni root, ni cron, ni nginx, ni iptables, ni paquetes):
#   1. usuario de sistema ugl-backup, sin contraseña y sin consola
#   2. /mnt/HC_Volume_35438240/unagrandeylibre/ (raíz de su encierro, de root) con restic/ (suya)
#   3. /etc/ssh/sshd_config.d/ugl-backup.conf: solo SFTP, encerrado en esa carpeta, sin túneles
#   4. /etc/ssh/authorized_keys/ugl-backup: la clave del servidor de correo, válida solo desde su IP
# La configuración de SSH se valida con `sshd -t` antes de recargar (recargar no corta sesiones).
# Es idempotente: se puede repetir sin efectos raros.
set -euo pipefail
MAIL=root@128.140.5.221
VPS=root@37.27.5.15
MAIL_IP=128.140.5.221

PUB=$(ssh "$MAIL" 'cat /etc/mail-backup/offsite_ed25519.pub')
case "$PUB" in ssh-ed25519\ *) ;; *) echo "No encuentro la clave pública en el servidor de correo" >&2; exit 1 ;; esac

# Una sola conexión al VPS (y reutilizada si ya hay una abierta): su cortafuegos bloquea la 3.ª en 5 minutos
ssh -o ControlMaster=auto -o ControlPath=/tmp/ugl-bk-%C -o ControlPersist=60 "$VPS" PUB="'$PUB'" MAIL_IP="$MAIL_IP" bash -s <<'REMOTE'
set -euo pipefail
D=/mnt/HC_Volume_35438240/unagrandeylibre

id ugl-backup >/dev/null 2>&1 || useradd --system --no-create-home --home-dir "$D" --shell /usr/sbin/nologin ugl-backup
install -d -o root -g root -m 755 "$D"
install -d -o ugl-backup -g ugl-backup -m 700 "$D/restic"

install -d -o root -g root -m 755 /etc/ssh/authorized_keys
echo "from=\"$MAIL_IP\",restrict $PUB" > /etc/ssh/authorized_keys/ugl-backup
chmod 644 /etc/ssh/authorized_keys/ugl-backup

cat > /etc/ssh/sshd_config.d/ugl-backup.conf <<'EOF'
# Copias externas de unagrandeylibre.es: solo SFTP, encerrado en su carpeta y solo desde el servidor de correo.
Match User ugl-backup
    AuthorizedKeysFile /etc/ssh/authorized_keys/%u
    ChrootDirectory /mnt/HC_Volume_35438240/unagrandeylibre
    ForceCommand internal-sftp
    PasswordAuthentication no
    AllowTcpForwarding no
    AllowAgentForwarding no
    X11Forwarding no
    PermitTTY no
# Cierra el bloque: este fichero se incluye al principio de sshd_config y sin esto el Match se tragaría el resto
Match all
EOF

if ! sshd -t; then
    rm -f /etc/ssh/sshd_config.d/ugl-backup.conf
    echo "Configuración de SSH no válida: deshecha, SSH sin cambios" >&2
    exit 1
fi
# Comprobación: la regla aplica a ugl-backup y a nadie más (si no, se deshace sin recargar).
# Salida a variables: con pipefail, `sshd -T | grep -q` falla por SIGPIPE aunque haya coincidencia.
for_ugl=$(sshd -T -C user=ugl-backup,host=x,addr=$MAIL_IP)
for_root=$(sshd -T -C user=root,host=x,addr=$MAIL_IP)
if ! grep -qx 'forcecommand internal-sftp' <<<"$for_ugl" \
   || grep -qx 'forcecommand internal-sftp' <<<"$for_root" \
   || ! grep -qx 'permitrootlogin without-password' <<<"$for_root"; then
    rm -f /etc/ssh/sshd_config.d/ugl-backup.conf
    echo "La regla no aplica como se esperaba: deshecha, SSH sin cambios" >&2
    exit 1
fi
systemctl reload ssh
echo "VPS listo: $(getent passwd ugl-backup)"
ls -la "$D"
REMOTE
