# Decisiones abiertas

Aquí solo están las decisiones **pendientes**. Cuando se toma una, se quita de aquí y pasa como una línea a la sección "Decisiones tomadas" de [ROADMAP.md](ROADMAP.md), y se refleja en las tareas y en [front/ARCHITECTURE.md](front/ARCHITECTURE.md).

Una IA que retome el proyecto **no debe tomar en silencio ninguna de estas decisiones**: la propone y pregunta.

Formato: `## D-NNN · Título` → contexto, opciones, recomendación, y una línea `Decisión:` que rellena el usuario. La siguiente libre es **D-031**.

---

## D-030 · Dónde y cómo nos anunciamos

**Contexto.** El público es gente de derechas politizada, a la que le hace gracia (o le gusta) tener un `@unagrandeylibre.es`. El dueño se inclina por X: es donde está ese público, se comparte por la gracia y puede generar movimiento solo. La web ya mide altas y pagos (PostHog) y tiene preparado el envío de conversiones a Meta y Google (`config/ads.php`), sin activar.

**Límites a tener en cuenta.**
- En la UE, las plataformas no pueden mostrar anuncios perfilando por opiniones políticas (Ley de Servicios Digitales, art. 26.3). No se puede segmentar por "de derechas" tal cual: hay que tirar de intereses, palabras clave o seguidores de cuentas afines, según lo que permita cada plataforma.
- Desde octubre de 2025 rige el reglamento europeo de publicidad política; Meta y Google dejaron de aceptar anuncios políticos y de temas sociales en la UE. Lo nuestro es un producto, no propaganda, pero la bandera y el tono pueden hacer que lo clasifiquen como político y lo rechacen. Por eso hace falta un anuncio de prueba.

**Opciones.**
- **A · X, orgánico primero:** cuenta propia con contenido con gracia, y menciones o colaboraciones con cuentas afines (pagadas o no; si son pagadas, se marcan como publicidad). Barato y es donde puede hacerse viral.
- **B · X Ads:** anuncios de pago segmentados por intereses, palabras clave y seguidores parecidos a los de cuentas afines. La web necesitaría el píxel y la API de conversiones de X (trabajo de la IA).
- **C · Meta y Google:** más alcance general y el código ya está, pero peor segmentación para este público y más riesgo de que lo rechacen por político.
- Se pueden combinar.

**Recomendación.** A + B: empezar en X con orgánico y colaboraciones para tantear el mensaje, y un anuncio de prueba en X Ads para ver si lo aceptan y cuánto cuesta un alta. Meta y Google, después y solo si X se queda corto.

Decisión:
