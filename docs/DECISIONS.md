# Decisiones abiertas

Aquí solo están las decisiones **pendientes**. Cuando se toma una, se quita de aquí y pasa como una línea a la sección "Decisiones tomadas" de [ROADMAP.md](ROADMAP.md), y se refleja en las tareas y en [front/ARCHITECTURE.md](front/ARCHITECTURE.md).

Una IA que retome el proyecto **no debe tomar en silencio ninguna de estas decisiones**: la propone y pregunta.

Formato: `## D-NNN · Título` → contexto, opciones, recomendación, y una línea `Decisión:` que rellena el usuario. La siguiente libre es **D-015**.

---

## D-014 · Varios buzones por usuario: cuántos y con qué plan

Contexto: la Fase 3 trae "Añadir otro buzón". Hoy cada usuario tiene uno, gratis. Sin límite, una sola cuenta podría acaparar nombres gratis (y el límite de altas por IP no lo frena, porque ya está dentro).

Opciones:
- **A.** Un buzón gratis por usuario; los demás, solo con plan de pago (cada uno con su plan o todos bajo uno).
- **B.** Hasta N gratis por usuario (p. ej. 3), configurable desde el admin.
- **C.** Sin buzones extra hasta la Fase 4 (Stripe): ahora solo se prepara la pantalla.

Recomendación: **A**, y mientras no haya pagos, **C** en la práctica (la pantalla existe pero dice "con los planes de pago, muy pronto"). Evita el acaparamiento y encaja con D-005 (los nombres valiosos se pagan).

Decisión: _pendiente_
