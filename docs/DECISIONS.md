# Decisiones abiertas

Aquí solo están las decisiones **pendientes**. Cuando se toma una, se quita de aquí y pasa como una línea a la sección "Decisiones tomadas" de [ROADMAP.md](ROADMAP.md), y se refleja en las tareas y en [front/ARCHITECTURE.md](front/ARCHITECTURE.md).

Una IA que retome el proyecto **no debe tomar en silencio ninguna de estas decisiones**: la propone y pregunta.

Formato: `## D-NNN · Título` → contexto, opciones, recomendación, y una línea `Decisión:` que rellena el usuario. La siguiente libre es **D-016**.

---

## D-015 · Copias de seguridad y cuentas borradas

Contexto: al borrar una cuenta, su correo desaparece del servidor al momento, pero sigue dentro de las copias de seguridad (restic, cifradas, en el propio servidor): hoy se guardan 7 diarias, 4 semanales y 6 mensuales, así que puede quedar hasta **6 meses**. El RGPD lo admite si se avisa en la política de privacidad, las copias no se usan para nada más y caducan solas. Lo mismo valdrá para las copias fuera del servidor (Fase 5).

Opciones:
- **A. Avisarlo y ya.** La política de privacidad dice que el correo borrado puede seguir hasta 6 meses en copias cifradas, sin acceso, y que se borra solo. Cero trabajo.
- **B. Acortar la retención** para todos (p. ej. 7 diarias + 4 semanales ≈ 1 mes) y avisarlo. Menos historial para recuperar un desastre que se descubra tarde.
- **C. Borrarlo también de las copias:** cada noche, para los buzones borrados ese día, `restic rewrite --exclude <buzón> --forget` sobre todas las copias y `prune`. Cumple al máximo, pero reescribe el repositorio de copias cada vez que alguien se va (más CPU y algo de riesgo de estropear las copias).

Recomendación: **A** ahora (es lo habitual y lo que hacen la mayoría de proveedores) y revisar **C** si llega una petición expresa de borrado o cuando haya copias fuera del servidor.

Decisión: _pendiente_
