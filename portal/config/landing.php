<?php

/*
 * Textos de la landing que no salen de la BD. Para cambiar un texto basta con editar esto
 * y desplegar. Los planes salen de la BD (admin → Planes).
 *
 * PROVISIONAL: los bloques y la FAQ son de relleno hasta que el usuario ponga los definitivos.
 */

return [

    'hero' => [
        'eyebrow' => 'Correo hecho en España',
        'title' => 'Consigue tu cuenta de correo',
        'subtitle' => 'Una dirección propia, privada y sin publicidad. Elige tu nombre:',
        'cta' => 'Consigue tu cuenta',
    ],

    'plans' => [
        'title' => 'Elige tu plan',
        'subtitle' => 'Precios con IVA incluido. Cambia de plan o cancela cuando quieras.',
    ],

    // Bloques informativos (icon: componente resources/views/components/icon/<icon>.blade.php)
    'blocks' => [
        ['icon' => 'flag', 'title' => 'Producto nacional', 'text' => 'Un servicio español, con los servidores en la Unión Europea y sujeto a la ley europea de protección de datos.'],
        ['icon' => 'shield', 'title' => 'Privado de verdad', 'text' => 'No leemos tu correo ni lo usamos para anuncios. Antispam y cifrado en todas las conexiones.'],
        ['icon' => 'phone', 'title' => 'En todos tus dispositivos', 'text' => 'Webmail y compatible con las apps de correo del móvil y del ordenador: iPhone, Android, Outlook, Thunderbird.'],
    ],

    'faq' => [
        ['q' => '¿Puedo usar mi cuenta en el móvil?', 'a' => 'Sí. Funciona con la app de correo del iPhone, Gmail para Android, Outlook, Thunderbird y cualquier app que use IMAP y SMTP.'],
        ['q' => '¿Qué pasa si dejo de pagar?', 'a' => 'Te avisamos antes. Si no se renueva el pago, la cuenta se suspende y, pasado un tiempo, se borra. Siempre podrás descargar tu correo antes.'],
        ['q' => '¿Puedo cambiar de plan?', 'a' => 'Cuando quieras, desde tu área de cliente. El cambio se aplica al momento.'],
        ['q' => '¿Por qué algunos nombres cortos cuestan más?', 'a' => 'Los nombres de 1 a 4 caracteres son escasos. Se pueden coger con cualquier plan de pago con un pequeño suplemento mensual.'],
    ],

    'legal' => [
        'aviso-legal' => 'Aviso legal',
        'privacidad' => 'Política de privacidad',
        'cookies' => 'Política de cookies',
        'condiciones' => 'Condiciones del servicio',
        'uso-aceptable' => 'Política de uso aceptable',
    ],

];
