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

    // Fin del plan gratis para las altas nuevas (D-032, en UTC). Solo los buzones creados antes pueden pasar a
    // gratis al acabar su plan de pago; los nuevos, como un impago. Vacío (tests): sin fecha, todos pueden.
    'free_closed_at' => env('FREE_PLAN_CLOSED_AT', '2026-10-20 20:00:00'),
];
