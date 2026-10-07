<?php

/*
 * Datos de conexión que se dan a las apps de correo (autoconfig, Autodiscover, perfil de Apple,
 * guías y "Mi cuenta"). Todos los dominios comparten el mismo servidor.
 */
return [

    'host' => env('MAIL_SERVER_HOST', 'mail.unagrandeylibre.es'),

    'imap' => ['port' => 993, 'security' => 'SSL'],          // TLS desde el primer byte
    'smtp' => ['port' => 465, 'security' => 'SSL'],
    'smtp_alt' => ['port' => 587, 'security' => 'STARTTLS'],  // alternativa si el 465 está bloqueado

    'provider_name' => 'Una Grande y Libre',

    // Apps con guía y flujo propios en "Configura un dispositivo"
    'clients' => [
        'iphone' => ['label' => 'iPhone o iPad', 'device' => 'iPhone', 'profile' => true],
        'mac' => ['label' => 'Mac (app Mail)', 'device' => 'Mac', 'profile' => true],
        'android' => ['label' => 'Android (Gmail)', 'device' => 'Android', 'profile' => false],
        'outlook' => ['label' => 'Outlook', 'device' => 'Outlook', 'profile' => false],
        'thunderbird' => ['label' => 'Thunderbird', 'device' => 'Thunderbird', 'profile' => false],
        'otra' => ['label' => 'Otra app', 'device' => 'App de correo', 'profile' => false],
    ],

];
