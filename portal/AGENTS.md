# portal/ — la web (Laravel 13)

Antes de nada, lee el [AGENTS.md de la raíz](../AGENTS.md): reglas del proyecto, roadmap y decisiones.

- Qué construir: [docs/front/PRODUCT.md](../docs/front/PRODUCT.md). Cómo: [docs/front/ARCHITECTURE.md](../docs/front/ARCHITECTURE.md).
- Stack: Laravel 13 (PHP 8.3 en producción), Cashier/Stripe, Filament, Blade + Alpine.
- **`.env` nunca va al repo** (es público). El de producción vive solo en el servidor: `/var/www/portal/shared/.env`. Las claves nuevas se añaden también a `.env.example` sin valores reales.
- Desplegar: `portal/deploy.sh` desde tu máquina (ver comentario al principio del script).
- BD en producción: `portal` (propia) + `mailserver` con permisos mínimos (`SELECT` en `domains`; `SELECT/INSERT/UPDATE` en `mailboxes` y `aliases`; sin `DELETE`). Para borrar buzones, el script privilegiado `mail-provision` (pendiente).
- En local: `composer install`, `cp .env.example .env`, `php artisan key:generate`, `php artisan migrate`, `php artisan serve` (SQLite por defecto).
