<?php

// Página de estado (/estado). mail-monitor (root, cada 10 min) escribe qué comprobaciones fallan en `file`;
// aquí se traducen a componentes que entiende cualquiera. Ver server/config/usr/local/sbin/mail-monitor.
return [
    'file' => env('STATUS_FILE', storage_path('app/status.json')),   // producción: /var/www/portal/shared/status.json
    'stale_minutes' => 30,

    // Componente → patrones (fnmatch) de las comprobaciones de mail-monitor que lo afectan
    'components' => [
        'receive' => ['label' => 'Recibir correo', 'checks' => ['service-postfix@-', 'service-rspamd', 'service-redis-server', 'service-dovecot', 'service-mariadb', 'disk-*', 'service-unbound']],
        'send' => ['label' => 'Enviar correo', 'checks' => ['service-postfix@-', 'service-rspamd', 'queue', 'cert-465', 'cert-587', 'bl-*', 'service-mariadb']],
        'apps' => ['label' => 'Apps de correo (IMAP)', 'checks' => ['service-dovecot', 'cert-993', 'service-mariadb']],
        'webmail' => ['label' => 'Webmail', 'checks' => ['service-nginx', 'service-php8.3-fpm', 'service-dovecot', 'cert-443', 'service-mariadb']],
        'web' => ['label' => 'Web y Mi cuenta', 'checks' => ['web', 'cert-web', 'service-nginx', 'service-php8.3-fpm', 'service-portal-queue', 'service-portal-schedule.timer', 'service-mariadb']],
        'payments' => ['label' => 'Pagos', 'checks' => ['stripe-webhooks']],
    ],
];
