# Decisiones abiertas

Aquí solo están las decisiones **pendientes**. Cuando se toma una, se quita de aquí y pasa como una línea a la sección "Decisiones tomadas" de [ROADMAP.md](ROADMAP.md), y se refleja en las tareas y en [front/ARCHITECTURE.md](front/ARCHITECTURE.md).

Una IA que retome el proyecto **no debe tomar en silencio ninguna de estas decisiones**: la propone y pregunta.

Formato: `## D-NNN · Título` → contexto, opciones, recomendación, y una línea `Decisión:` que rellena el usuario. La siguiente libre es **D-029**.

---

## D-029 · Planes de lanzamiento: precios, espacio y límites

**Contexto.** Antes de pasar Stripe a live (los productos y precios de live los crea `stripe:sync` desde la tabla `plans`; cambiar un precio después crea uno nuevo y los clientes conservan el suyo, D-007). Hoy: Gratis 1 GB, 20 envíos/h, sin alias, webmail y apps; Básico 1,99 €/mes (−50 % los 3 primeros meses), 10 GB, 5 alias, 100 envíos/h, destacado; Pro 4,99 €/mes, 50 GB, 25 alias, 300 envíos/h. Recargo por nombre corto (1–2: 3 €, 3–4: 1 €, 5–7: 0,50 €). Solo mensual.

El dueño quiere tres planes: gratis lo más limitado posible (que quien lo use de verdad pague), uno medio que es el que se vende (razonable y barato) y un Pro que haga parecer barato al medio. El espacio cuesta poco (céntimos por GB y mes); lo caro del gratis es la reputación (spam saliente) y el soporte.

**Opciones para el gratis:**
- (a) Quitarle las apps de correo (IMAP/SMTP): solo webmail, como Proton. Es la palanca más fuerte para que pague quien lo usa a diario en el móvil. Hay que programarlo (contraseñas de dispositivo y autoconfiguración solo para planes de pago, y el texto de la web).
- (b) Mantener las apps y apretar espacio y envíos: 500 MB y 20 envíos al día (no por hora); aviso por email al 80 % y al 100 % del espacio con el botón de subir de plan.
- (c) Las dos.

**Medio y Pro:** mantener 1,99 € y 4,99 € (el Pro a 2,5× hace de ancla), Básico destacado como «el más elegido» y añadir el pago anual con dos meses gratis (19,90 € y 49,90 €), que fija clientes y adelanta caja. Alias: 0 / 10 / 50.

**Recomendación:** (b) ya para lanzar, con los avisos de espacio; (a) si a las pocas semanas el gratis convierte poco.

Decisión: 
