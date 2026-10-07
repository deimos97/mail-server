<?php

/*
 * Proveedor OAuth2 propio (login único con el webmail, D-010). Un único cliente: nuestro Roundcube.
 * El secreto vive solo en el .env del servidor y en /etc/roundcube/config.inc.php.
 */
return [

    'client' => [
        'id' => env('OAUTH_WEBMAIL_CLIENT_ID', 'webmail'),
        'secret' => env('OAUTH_WEBMAIL_CLIENT_SECRET'),
        // Roundcube 1.6 siempre vuelve a <url del webmail>/index.php/login/oauth
        'redirect_uri' => env('OAUTH_WEBMAIL_REDIRECT_URI', 'https://webmail.unagrandeylibre.es/index.php/login/oauth'),
    ],

    // Abrir el webmail con login único: Roundcube arranca el flujo al entrar aquí sin ?code
    'webmail_start_url' => env('OAUTH_WEBMAIL_START_URL', 'https://webmail.unagrandeylibre.es/index.php/login/oauth'),

    'code_ttl' => 60,                       // segundos
    'access_ttl' => 3600,                   // 1 h (Roundcube lo refresca solo)
    'refresh_ttl' => 60 * 60 * 24 * 30,     // 30 días, rotativo

    // Quién puede preguntar si un token es válido (Dovecot, desde el propio servidor)
    'introspection_ips' => array_filter(explode(',', env('OAUTH_INTROSPECTION_IPS', '127.0.0.1,::1'))),

];
