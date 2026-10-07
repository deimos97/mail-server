# AGENTS.md — punto de entrada para cualquier IA

Lee esto antes de tocar nada. Después, según la tarea:

| Si vas a… | Lee |
|---|---|
| Entender qué es el proyecto y cómo está montado el servidor | [README.md](README.md) |
| Saber qué toca hacer ahora | [docs/ROADMAP.md](docs/ROADMAP.md) (la fase marcada como **EN CURSO**) |
| Saber qué se ha decidido | [docs/ROADMAP.md](docs/ROADMAP.md) § Decisiones tomadas |
| Saber qué está pendiente de decidir | [docs/DECISIONS.md](docs/DECISIONS.md) (solo las abiertas) |
| Trabajar en la landing, el onboarding o la experiencia de usuario | [docs/front/PRODUCT.md](docs/front/PRODUCT.md) |
| Trabajar en BD, Stripe, SSO, autoconfiguración o despliegue | [docs/front/ARCHITECTURE.md](docs/front/ARCHITECTURE.md) |
| Conectarte al servidor | skill [`.claude/skills/mail-server-ops`](.claude/skills/mail-server-ops/SKILL.md) (vale para cualquier IA: es Markdown) |

## Qué es esto

Servicio de correo `@unagrandeylibre.es` (en el futuro, más dominios). El **back-end de correo ya funciona** (Postfix, Dovecot, Rspamd, Roundcube; ver README). Lo que falta es el **front-end**: una web donde la gente se crea una cuenta, elige plan (gratis o de pago con Stripe) y gestiona sus buzones.

## Reglas de trabajo

1. **Idioma:** documentación, commits y UI en español.
2. **Mantén los docs vivos.** Al terminar una tarea: marca la casilla en `docs/ROADMAP.md`. Cuando el usuario cierra una decisión, quítala de `docs/DECISIONS.md`, añade una línea en "Decisiones tomadas" del ROADMAP y refleja sus consecuencias en las tareas y en `docs/front/`. Si descubres un *monkey noise* del servidor, añádelo al README.
3. **Las decisiones abiertas no se toman en silencio.** Si una tarea depende de una decisión que sigue en `DECISIONS.md`, pregunta al usuario o propón y espera.
4. **Servidor de producción.** Solo hay uno. Lectura libre; cualquier cambio, con confirmación. Ver la skill de operaciones.
   - **Todavía no hay usuarios reales** (solo los buzones de prueba del dueño: `test@` y `noreply@`). Un corte breve del correo o de la web **no afecta a nadie**: no hace falta buscar ventanas de mantenimiento ni preocuparse por el tiempo sin servicio. Sí sigue siendo obligatorio: copia de seguridad antes de cambiar algo, no perder datos ni configuración, y dejar el servicio funcionando y probado al terminar.
   - Esta nota se quita al abrir altas (último paso del lanzamiento en `docs/ROADMAP.md`). A partir de ahí, cada corte afecta a usuarios reales.
5. **Nunca** pegues secretos (contraseñas, claves de Stripe, `des_key`, tokens) en docs, commits ni chat.
