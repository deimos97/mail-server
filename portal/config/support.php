<?php

// Soporte por tickets (Fase 7, D-018 a D-021). Ver App\Services\Support.
return [
    // Remitente de respuestas y autoconfirmaciones (D-019). Hasta que exista el buzón soporte@,
    // sale del remitente general (noreply@): Postfix no deja enviar como otra dirección.
    'from' => [
        'address' => env('SUPPORT_FROM_ADDRESS') ?: env('MAIL_FROM_ADDRESS', 'noreply@unagrandeylibre.es'),
        'name' => env('SUPPORT_FROM_NAME', 'Javier · Una Grande y Libre'),
    ],

    // Mailer con las credenciales de soporte@ (config/mail.php → mailers.support). null = el de siempre.
    'mailer' => env('SUPPORT_MAILER'),

    // A quién avisamos de tickets nuevos y respuestas
    'notify' => env('SUPPORT_NOTIFY', 'javier@unagrandeylibre.es'),

    // D-020: solo imágenes, hasta 3 y 8 MB en total (nginx y PHP admiten 12 MB por petición)
    'attachments' => [
        'max_files' => 3,
        'max_total_kb' => 8 * 1024,
        'mimes' => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'heic', 'heif'],
    ],
    'transfer_url' => 'https://transfer.tibletech.com/',

    // D-021: tickets sin sesión. Se cambia desde el admin (ajuste support.guests); esto es el valor de partida.
    'guests_default' => true,

    // Parte C: buzón que lee `support:fetch` cada minuto. Sin usuario, no se lee nada.
    'imap' => [
        'host' => env('SUPPORT_IMAP_HOST', '127.0.0.1'),
        'port' => (int) env('SUPPORT_IMAP_PORT', 993),
        'encryption' => env('SUPPORT_IMAP_ENCRYPTION', 'ssl'),
        'validate_cert' => (bool) env('SUPPORT_IMAP_VALIDATE_CERT', false),   // 127.0.0.1 no coincide con el certificado
        'username' => env('SUPPORT_IMAP_USERNAME'),
        'password' => env('SUPPORT_IMAP_PASSWORD'),
        'processed_folder' => 'Tickets',
    ],
];
