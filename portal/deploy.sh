#!/usr/bin/env bash
# Despliega portal/ en producción. El servidor descarga el código de GitHub,
# así que lo que despliegas tiene que estar ya subido.
#
# Uso: portal/deploy.sh             despliega origin/main
#      portal/deploy.sh <sha>       despliega un commit concreto
#      portal/deploy.sh --rollback  vuelve a la release anterior
set -euo pipefail
HOST="${MAIL_HOST:-root@128.140.5.221}"

if [ "${1:-}" != "--rollback" ]; then
    # El servidor no tiene Node: el CSS/JS se compila aquí y public/build va en el repo.
    # Se recompila y, si cambia algo, es que falta hacer commit de la compilación.
    (cd "$(dirname "$0")" && npm run build --silent >/dev/null)
    if [ -n "$(git status --porcelain -- portal/public/build)" ]; then
        echo "public/build no está al día: haz commit de la compilación (npm run build) y vuelve a desplegar." >&2; exit 1
    fi
    git fetch --quiet origin
    if [ -n "$(git status --porcelain -- portal)" ]; then
        echo "Hay cambios sin commit en portal/. Súbelos antes de desplegar." >&2; exit 1
    fi
    if [ -z "${1:-}" ] && [ "$(git rev-parse HEAD)" != "$(git rev-parse origin/main)" ]; then
        echo "Tu HEAD no coincide con origin/main (¿falta un push?)." >&2; exit 1
    fi
fi
ssh -o BatchMode=yes "$HOST" /usr/local/sbin/portal-deploy "$@"
