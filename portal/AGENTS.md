# portal/ — la web (Laravel 13)

Antes de nada, lee el [AGENTS.md de la raíz](../AGENTS.md): reglas del proyecto, roadmap y decisiones.

- Qué construir: [docs/front/PRODUCT.md](../docs/front/PRODUCT.md). Cómo: [docs/front/ARCHITECTURE.md](../docs/front/ARCHITECTURE.md).
- Stack: Laravel 13 (PHP 8.3 en producción), Cashier/Stripe, Filament, Blade + Alpine.
- **`.env` nunca va al repo** (es público). El de producción vive solo en el servidor: `/var/www/portal/shared/.env`. Las claves nuevas se añaden también a `.env.example` sin valores reales.
- Desplegar: `portal/deploy.sh` desde tu máquina (ver comentario al principio del script).
- BD en producción: `portal` (propia) + `mailserver` (conexión `mailserver`, modelo `Domain`) con permisos mínimos: `SELECT` y `UPDATE (public_signup, sort_order)` en `domains`; `SELECT/INSERT/UPDATE` en `mailboxes` y `aliases`; sin `DELETE` ni `ALTER`. Los cambios de esquema en `mailserver` van en `server/sql/` y los aplica root. Lo que necesita root (firmar perfiles de Apple, `doveadm kick`, y más adelante borrar buzones) va por el script privilegiado `mail-provision` (`server/bin/`), que la web llama con sudo (`App\Services\MailProvision`, `MAIL_PROVISION_COMMAND`).
- Dinero en **céntimos con IVA** y espacio en **bytes** en la BD; el admin muestra € y GB (`App\Filament\Support\Fields`). Fechas en UTC en la BD; el admin trabaja en hora de Madrid.
- Admin: Filament 5 en `/admin`, solo usuarios con `is_admin` (no asignable en masa) y 2FA obligatoria. Crear uno: `php artisan portal:make-admin`.
- Front: Blade + Alpine + Tailwind 4 (Vite). **El servidor no tiene Node**: `npm run build` en local y `public/build` va en el repo; `deploy.sh` recompila y se niega a desplegar si la compilación no está en el commit. Textos de la landing en `config/landing.php`; la bandera es `resources/js/flag.js` (sin GPU no se anima, para no bloquear la página). Tailwind **no** debe leer `storage/framework/views`: el resultado dependería de lo que se haya ejecutado en local y el build dejaría de ser reproducible. `APP_INDEXABLE=true` en producción desde el 2026-10-09 (con `false`, todas las páginas llevan `noindex`); el sitemap (`SeoController`) incluye la portada, la ayuda de cada app, los textos legales, `/estado` y `/soporte`. Confirmaciones: nunca `confirm()`/`alert()` del navegador; en un formulario, `data-confirm="…"` (+ `data-confirm-body`, `data-confirm-button`) abre el `<dialog>` propio (`resources/js/confirm.js`), y desde Alpine, `await $confirm({ title, body, confirm })`.
- Alta (`SignupController` + `App\Services\Signup`, `EmailVerification`, `Turnstile`): abierta en producción desde el 2026-10-09 (`SIGNUP_OPEN=true`). Con `false` se cierra, y entonces solo se entra con `/alta?acceso=<SIGNUP_PREVIEW_TOKEN>` (el token está en el `.env` del servidor). Textos legales sin aviso de borrador (`LEGAL_DRAFT=false`, validados por la gestoría). En local, `SIGNUP_OPEN=true`, Turnstile con las claves de prueba de Cloudflare y los correos en `storage/logs/laravel.log` (`MAIL_MAILER=log`; procesar la cola con `php artisan queue:work --once`).
- Soporte (Fase 7, `App\Services\Support` y `SupportInbox`, `config/support.php`): `/soporte` abre un ticket (Turnstile, `throttle:support`, hasta 3 imágenes y 8 MB en total en el disco privado `support/`); el admin responde desde el recurso "Tickets" y las imágenes se sirven por una ruta del panel (`filament.admin.support.attachment`, con su login y 2FA). Los correos al cliente salen de `soporte@` con `[#NNNN]` en el asunto y `support:fetch` (cada minuto) engancha las respuestas. Sin `SUPPORT_MAILER`/`SUPPORT_IMAP_*` (local, tests) sale del remitente general y no se lee ningún buzón. El interruptor de tickets sin sesión es un ajuste de la tabla `settings` (`App\Models\Setting`), no del `.env`.
- Modelos de las dos BD: los de `mailserver` (`Mailbox`, `AppPassword`, `Domain`) declaran `$connection = 'mailserver'`; los de `portal` que se cargan como relación desde ellos (`Plan`, `User`) usan el trait `OnPortalDatabase`, porque si no Eloquent los buscaría en la conexión del padre.
- Tests: `php artisan test`. Los modelos declaran en `$attributes` los mismos defaults que la migración (si no, un modelo recién creado los tiene a `null`).
- En local (SQLite):
  ```bash
  composer install && cp .env.example .env && php artisan key:generate
  touch database/mailserver.sqlite
  php artisan migrate
  php artisan migrate --database=mailserver --path=database/migrations-mailserver-local
  php artisan db:seed        # reservados, reglas, dominio y planes de ejemplo
  php artisan portal:make-admin
  php artisan serve
  ```
  En la 2FA local, el código actual sale con `php artisan tinker --execute='echo Filament\Auth\MultiFactor\App\AppAuthentication::make()->getCurrentCode(App\Models\User::where("is_admin",true)->first());'`.
