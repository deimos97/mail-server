<?php

// Script privilegiado del servidor (server/bin/mail-provision), que la web llama con sudo.
// Vacío en local y en tests: los perfiles salen sin firmar y no se cierran conexiones.
return [
    'command' => env('MAIL_PROVISION_COMMAND'),   // producción: "/usr/bin/sudo -n /usr/local/sbin/mail-provision"
    'timeout' => 20,
];
