#!/usr/bin/env bash
# Compila la skin de Roundcube (server/roundcube/skins/unagrandeylibre) contra los .less de Elastic
# del servidor (misma versión que se sirve) y la sube. El servidor no tiene Node: se compila aquí.
#
#   server/roundcube/build-skin.sh            compila y sube
#   server/roundcube/build-skin.sh --local    solo compila (en server/roundcube/skins/…/styles/)
set -euo pipefail
cd "$(dirname "$0")"
HOST=root@128.140.5.221
WORK=$(mktemp -d)
# Una sola conexión SSH para todo: el firewall corta a partir de 10 conexiones nuevas por minuto
SSH_OPTS=(-o BatchMode=yes -o ControlMaster=auto -o "ControlPath=/tmp/ugl-ssh-%C" -o ControlPersist=60)
trap 'ssh "${SSH_OPTS[@]}" -O exit "$HOST" 2>/dev/null; rm -rf "$WORK"' EXIT
export RSYNC_RSH="ssh ${SSH_OPTS[*]}"

# Las rutas relativas de Elastic (fuentes, imágenes) deben acabar apuntando a skins/elastic/
mkdir -p "$WORK/skins"
rsync -a --exclude '*.gz' --exclude '*.map' --exclude fonts --exclude deps \
    "$HOST:/usr/share/roundcube/skins/elastic/" "$WORK/skins/elastic/"
cp -R skins/unagrandeylibre "$WORK/skins/"

# Roundcube busca los CSS en la skin donde encuentra templates/includes/layout.html: sin una copia
# propia, cargaría los de Elastic. Se copia del servidor en cada compilación (sigue a las actualizaciones).
mkdir -p skins/unagrandeylibre/templates/includes
cp "$WORK/skins/elastic/templates/includes/layout.html" skins/unagrandeylibre/templates/includes/layout.html
OUT="$WORK/skins/unagrandeylibre/styles"
for f in styles embed; do
    [ $f = embed ] && echo '@import "../../elastic/styles/embed";' > "$OUT/embed.less"
    npx -y less@4 --rewrite-urls=all --include-path="$OUT" "$OUT/$f.less" "$OUT/$f.css"
    npx -y less@4 --rewrite-urls=all --include-path="$OUT" --clean-css "$OUT/$f.less" "$OUT/$f.min.css" 2>/dev/null \
        || cp "$OUT/$f.css" "$OUT/$f.min.css"
done
cp "$OUT"/*.css skins/unagrandeylibre/styles/

[ "${1:-}" = --local ] && exit 0
rsync -a --delete --exclude '*.less' skins/unagrandeylibre/ "$HOST:/var/lib/roundcube/skins/unagrandeylibre/"
ssh "${SSH_OPTS[@]}" "$HOST" 'chown -R root:root /var/lib/roundcube/skins/unagrandeylibre && chmod -R a+rX,go-w /var/lib/roundcube/skins/unagrandeylibre'
echo "Skin subida. Si no se ve, recarga sin caché."
