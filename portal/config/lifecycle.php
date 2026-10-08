<?php

// Ciclo de vida de los buzones (D-009). Días desde que empieza el impago (o desde que acaba un plan que no
// puede pasar a gratis), salvo donde se diga otra cosa. Lo aplica App\Services\MailboxLifecycle cada día.
return [
    'unpaid' => [
        'suspend_after_days' => 10,      // sin acceso ni envío; sigue recibiendo
        'delete_after_days' => 30,       // se borra el contenido (con aviso una semana antes)
        'warning_days_before_delete' => 7,
        'release_after_days' => 90,      // el nombre queda libre
    ],
    'inactive_free' => [
        'after_months' => 6,             // sin entrar → aviso
        'grace_days' => 30,              // tras el aviso → suspensión y borrado
        'release_after_days' => 60,      // tras el borrado → nombre libre
    ],
    'voluntary_release_days' => 90,      // borrar la cuenta → nombre libre
];
