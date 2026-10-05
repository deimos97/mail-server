# server/

Copia versionada de la configuración del servidor de correo, **sin secretos**. Es de referencia: la verdad sigue estando en el servidor; esto sirve para ver diffs, recuperar algo tras un error y dar contexto a cualquier IA.

## Actualizar

```bash
server/pull-config.sh
git diff server/config     # revisa qué ha cambiado en el servidor
```

Hazlo después de cada cambio en el servidor y súbelo en el mismo commit que la documentación del cambio.

## Cómo protege los secretos (el repo es público)

1. El saneado se hace **en el servidor**: los valores de contraseñas, `des_key`, DSN, hashes, tokens de Telegram y URLs de Healthchecks se sustituyen por `__REDACTED__` antes de salir de él.
2. No se copian nunca: `*.env`, `restic.pass`, claves (`*.key`, `*.pem`, DKIM), copias `.bak`.
3. En local, antes de aceptar la exportación: lista de patrones prohibidos + líneas con pinta de secreto sin redactar + `gitleaks`. Si algo falla, no se escribe nada.
4. Hook `pre-commit` (`.githooks/`) con `gitleaks protect --staged`. Actívalo en cada clon:
   ```bash
   git config core.hooksPath .githooks
   ```

Si añades al servidor un fichero con un secreto de un tipo nuevo, **añade su patrón al saneado** de `pull-config.sh` antes de exportar.

## Qué hay

- `config/etc/…`, `config/usr/…`: solo ficheros propios o modificados respecto al paquete (los conffiles intactos se omiten).
- `config/_effective/`: `postconf -n`, `doveconf -n`, esquema de la BD `mailserver` (sin datos) y versiones de los paquetes.
