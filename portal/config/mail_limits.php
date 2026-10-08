<?php

// Límites de envío por plan en Rspamd (ratelimit). Rspamd lee de la web, por cada `tier` de pago, la lista
// de buzones activos (GET /internal/rspamd/{tier}.map, solo desde el propio servidor) y les aplica su cubo
// de /etc/rspamd/local.d/ratelimit.conf. Los demás (gratis o un tier sin cubo) van al cubo del plan gratis.
// Si se crea un tier nuevo: añadirlo aquí y su cubo en ratelimit.conf (server/config/etc/rspamd/local.d/).
return [
    'paid_tiers' => ['basic', 'pro'],
    'allowed_ips' => array_filter(explode(',', (string) env('INTERNAL_IPS', '127.0.0.1,::1'))),
];
