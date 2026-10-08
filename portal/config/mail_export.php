<?php

// Copias del correo (Mi cuenta → "Descargar una copia"). La web deja la petición en requests/ y root
// (mail-provision process-exports, lanzado por mail-export.path) deja el zip en files/.
return [
    'dir' => env('MAIL_EXPORT_DIR', storage_path('app/exports')),   // producción: /var/www/portal/shared/exports
    'hours' => 48,          // cuánto tiempo se puede descargar
    'timeout_hours' => 3,   // una petición sin respuesta en este tiempo se da por fallida
];
