# Decisiones abiertas

Aquí solo están las decisiones **pendientes**. Cuando se toma una, se quita de aquí y pasa como una línea a la sección "Decisiones tomadas" de [ROADMAP.md](ROADMAP.md), y se refleja en las tareas y en [front/ARCHITECTURE.md](front/ARCHITECTURE.md).

Una IA que retome el proyecto **no debe tomar en silencio ninguna de estas decisiones**: la propone y pregunta.

Formato: `## D-NNN · Título` → contexto, opciones, recomendación, y una línea `Decisión:` que rellena el usuario. La siguiente libre es **D-032**.

---

## D-031 · Moderación de nombres ofensivos o problemáticos

**Contexto.** Con la primera campaña en X (2026-10-19) entraron 42 buzones en un día. La mayoría son nombres normales, pero hay algunos que chocan con la política de uso aceptable (suplantación, incitación al odio): un insulto sexual a una persona real con nombre y apellido, un código neonazi (`…1488`), el nombre del dictador y el de un político conocido. Ninguna regla de nombres los bloquea hoy.

**Decisión del dueño (2026-10-19): esperar y recoger más datos** antes de actuar, para ver patrones. Los casos están localizados en `mailserver.mailboxes` (alta del 2026-10-19).

**Opciones cuando se decida.**
- **Para el futuro:** reglas de nombres en el admin que bloqueen insultos combinados con nombres propios, códigos de odio (1488, sieg, hitler…) y nombres de dictadores; y nombres de políticos y personajes públicos como reservados.
- **Para los existentes:** suspender los que incumplen claramente y avisar a su email de recuperación; para los dudosos (un nombre que puede ser real), dejarlos o pedir que cambien.

Decisión: pendiente (esperar a tener más datos).
