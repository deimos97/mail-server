# Borrador para la gestoría: política de privacidad y soporte por tickets

**Estado:** pendiente de validar por la gestoría (2026-10-14). Cuando lo validen, se aplica en `portal/resources/views/legal/privacidad.blade.php`, se cambia la fecha de `config/legal.php` (`updated`) y se borra este fichero.

## Contexto

Desde el 2026-10-14 la web tiene un formulario de **ayuda y contacto** (`unagrandeylibre.es/soporte`). Quien escribe, tenga cuenta o no, nos deja:

- su **email** (si tiene cuenta, usamos el de recuperación);
- **asunto y descripción** de la consulta;
- hasta **3 imágenes** (capturas de pantalla, 8 MB en total);
- su **IP** (para limitar envíos y evitar abusos);
- la verificación anti-robots de **Cloudflare Turnstile**.

Respondemos por email desde `soporte@unagrandeylibre.es`. Si el usuario contesta a ese correo, su respuesta (texto e imágenes) se guarda en la misma consulta. Todo se guarda en nuestro servidor de Hetzner (UE); solo lo ve el personal de soporte desde el panel de administración.

Para otros ficheros, el formulario remite a `transfer.tibletech.com`, el servicio de envío de archivos de Tibletech. La subida la hace el propio usuario y solo nos llega el enlace dentro del mensaje.

## Propuesta de plazo de conservación

**2 años desde el último mensaje** de la consulta, para tener el historial si la persona vuelve a escribir por lo mismo. **Si alguien borra su cuenta, borramos también sus consultas.** Pasado el plazo, se borran solas (texto e imágenes).

> Pregunta para la gestoría: ¿os parece bien 2 años, o preferís otro plazo (p. ej. 1 año)? El texto de abajo usa 2 años.

## Cambios en el texto

### Punto 2, tabla "Qué datos tratamos y para qué": fila nueva

| Datos | Para qué | Base legal |
|---|---|---|
| Lo que nos escribes en el formulario de ayuda o por email a soporte: tu email, el asunto, el mensaje, las imágenes que adjuntes y la IP desde la que escribes | Responder a tu consulta y, si tienes cuenta, resolver la incidencia en ella | Contrato, si tienes cuenta; si no la tienes, tu consentimiento al escribirnos |

### Punto 2, fila de Turnstile (cambia "Para qué")

- Antes: «Evitar altas automáticas»
- Después: «Evitar altas y mensajes automáticos (alta y formulario de ayuda)»

### Punto 3, "Cuánto tiempo los guardamos": viñeta nueva (después de "Cuenta y buzón")

> **Consultas de soporte:** hasta 2 años desde el último mensaje, para tener el historial si vuelves a escribirnos por lo mismo. Si borras tu cuenta, borramos también tus consultas.

### Punto 4, "Con quién los compartimos": viñeta de Cloudflare

- Antes: «**Cloudflare, Inc.**: DNS del dominio y verificación anti-robots en el alta.»
- Después: «**Cloudflare, Inc.**: DNS del dominio y verificación anti-robots en el alta y en el formulario de ayuda.»

### Punto 5, "Tu correo es tuyo": sin cambios

Ya dice que solo accederíamos a un buzón si nos lo pides para resolver una incidencia. Una consulta de soporte no da acceso al buzón por sí sola.

## Qué hay que programar cuando se valide

- Al borrar una cuenta, borrar sus tickets y sus imágenes (`App\Services\AccountDeletion`).
- Tarea diaria que borre los tickets cuyo último mensaje tenga más del plazo validado, con sus imágenes.
