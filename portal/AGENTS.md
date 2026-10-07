# portal/ — la web (Laravel 13)

Antes de nada, lee el [AGENTS.md de la raíz](../AGENTS.md): reglas del proyecto, roadmap y decisiones.

- Qué construir: [docs/front/PRODUCT.md](../docs/front/PRODUCT.md). Cómo: [docs/front/ARCHITECTURE.md](../docs/front/ARCHITECTURE.md).
- Stack: Laravel 13 (PHP 8.3 en producción), Cashier/Stripe, Filament, Blade + Alpine.
- **`.env` nunca va al repo** (es público). El de producción vive solo en el servidor: `/var/www/portal/shared/.env`. Las claves nuevas se añaden también a `.env.example` sin valores reales.
- Desplegar: `portal/deploy.sh` desde tu máquina (ver comentario al principio del script).
- BD en producción: `portal` (propia) + `mailserver` (conexión `mailserver`, modelo `Domain`) con permisos mínimos: `SELECT` y `UPDATE (public_signup, sort_order)` en `domains`; `SELECT/INSERT/UPDATE` en `mailboxes` y `aliases`; sin `DELETE` ni `ALTER`. Los cambios de esquema en `mailserver` van en `server/sql/` y los aplica root. Para borrar buzones, el script privilegiado `mail-provision` (pendiente).
- Dinero en **céntimos con IVA** y espacio en **bytes** en la BD; el admin muestra € y GB (`App\Filament\Support\Fields`). Fechas en UTC en la BD; el admin trabaja en hora de Madrid.
- Admin: Filament 5 en `/admin`, solo usuarios con `is_admin` (no asignable en masa) y 2FA obligatoria. Crear uno: `php artisan portal:make-admin`.
- Front: Blade + Alpine + Tailwind 4 (Vite). **El servidor no tiene Node**: `npm run build` en local y `public/build` va en el repo; `deploy.sh` recompila y se niega a desplegar si la compilación no está en el commit. Textos de la landing en `config/landing.php`; la bandera es `resources/js/flag.js` (sin GPU no se anima, para no bloquear la página). Tailwind **no** debe leer `storage/framework/views`: el resultado dependería de lo que se haya ejecutado en local y el build dejaría de ser reproducible. Mientras el alta no exista, `APP_INDEXABLE=false` → `noindex`.
- Alta (`SignupController` + `App\Services\Signup`, `EmailVerification`, `Turnstile`): cerrada en producción hasta el lanzamiento (`SIGNUP_OPEN=false`); para probar, `/alta?acceso=<SIGNUP_PREVIEW_TOKEN>` (el token está en el `.env` del servidor). En local, `SIGNUP_OPEN=true`, Turnstile con las claves de prueba de Cloudflare y los correos en `storage/logs/laravel.log` (`MAIL_MAILER=log`; procesar la cola con `php artisan queue:work --once`).
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
