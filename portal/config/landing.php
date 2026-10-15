<?php

/*
 * Textos de la landing que no salen de la BD. Para cambiar un texto basta con editar esto
 * y desplegar. Los planes salen de la BD (admin → Planes).
 *
 * Tono neutro (2026-10-15): correo español, privado y sin anuncios; la marca y la bandera hacen el resto.
 */

return [

    'hero' => [
        'eyebrow' => 'Correo hecho en España',
        'title' => 'Consigue tu cuenta de correo',
        'subtitle' => 'Una dirección propia, privada y sin publicidad. Elige tu nombre:',
        'cta' => 'Consigue tu cuenta',
    ],

    // "Ya somos N": solo se enseña a partir de `min` buzones activos de clientes (sin los internos)
    'counter' => [
        'min' => 100,
        'text' => 'Ya somos :count con dirección @:domain',
    ],

    // "Así te verán": una bandeja de entrada con la dirección que haya elegido el visitante, y el alta en 3 pasos
    'preview' => [
        'title' => 'Así te verán',
        'subtitle' => 'Una dirección corta, fácil de dictar y que se recuerda. Tu nombre, sin números ni puntos de relleno.',
        'example' => 'tunombre',
        'inbox' => [
            ['subject' => '¿Quedamos el sábado?', 'snippet' => 'Te paso la dirección del sitio y la hora…'],
            ['from' => 'Banco', 'subject' => 'Tu extracto de octubre', 'snippet' => 'Ya puedes consultar los movimientos del mes.'],
            ['from' => 'Tienda online', 'subject' => 'Tu pedido está en camino', 'snippet' => 'Llegará entre el martes y el miércoles.'],
        ],
        'steps_title' => 'Listo en un minuto',
        'steps' => [
            ['title' => 'Elige tu nombre', 'text' => 'Escríbelo arriba y te decimos al momento si está libre. Si no, te proponemos parecidos.'],
            ['title' => 'Elige tu plan', 'text' => 'Empieza gratis o elige uno de pago. Pagas con tarjeta en Stripe: nunca vemos tus datos.'],
            ['title' => 'Úsalo donde quieras', 'text' => 'En el navegador, con el webmail, o en las apps del móvil y del ordenador. Te guiamos paso a paso.'],
        ],
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

    // "Pasarte es fácil": cómo dejar el correo de siempre sin perder nada
    'switch' => [
        'title' => 'Pasarte es fácil',
        'subtitle' => 'No hace falta cambiarlo todo de golpe. Sigue recibiendo lo de tu correo de siempre mientras te mudas.',
        'items' => [
            [
                'title' => 'Reenvía tu correo de siempre',
                'text' => 'Configura tu correo antiguo para que reenvíe todo a tu nueva dirección. Así lo lees todo en un solo sitio.',
                'guides' => [
                    ['name' => 'Desde Gmail', 'steps' => [
                        'En gmail.com, abre ⚙️ → «Ver todos los ajustes».',
                        'Pestaña «Reenvío y correo POP/IMAP» → «Añadir una dirección de reenvío» y escribe tu nueva dirección.',
                        'Te llegará un código a tu nuevo buzón: confírmalo.',
                        'Marca «Reenviar una copia del correo entrante» y guarda los cambios.',
                    ]],
                    ['name' => 'Desde Outlook o Hotmail', 'steps' => [
                        'En outlook.com, abre ⚙️ → «Correo» → «Reenvío».',
                        'Activa el reenvío y escribe tu nueva dirección.',
                        'Si quieres, marca «Conservar una copia de los mensajes reenviados» y guarda.',
                    ]],
                ],
            ],
            ['title' => 'Cambia tu correo poco a poco', 'text' => 'Empieza por lo importante: el banco, las compras y las redes. Lo que se te olvide seguirá llegando por el reenvío.'],
            ['title' => 'Avisa a tus contactos', 'text' => 'Escríbeles desde tu nueva dirección: la verán y la tendrán guardada al responderte.'],
        ],
    ],

    'faq' => [
        ['q' => '¿Puedo usar mi cuenta en el móvil?', 'a' => 'Sí. Funciona con la app de correo del iPhone, Gmail para Android, Outlook, Thunderbird y cualquier app que use IMAP y SMTP.'],
        ['q' => '¿Puedo leerlo dentro de gmail.com?', 'a' => 'En el móvil sí: añade tu cuenta en la app de Gmail (te explicamos cómo paso a paso). En gmail.com desde el ordenador no, porque Google ya no permite añadir cuentas de otros proveedores; ahí usa nuestro webmail o un programa como Outlook o Thunderbird.'],
        ['q' => '¿Qué pasa si dejo de pagar?', 'a' => 'Te avisamos antes. Si no se renueva el pago, la cuenta se suspende y, pasado un tiempo, se borra. Siempre podrás descargar tu correo antes.'],
        ['q' => '¿Qué incluye el plan gratis?', 'a' => 'Un buzón con 500 MB, hasta 20 correos enviados al día, webmail y apps de correo. Cada cuenta incluye un buzón gratis; si necesitas más espacio o más envíos, pasa a un plan de pago cuando quieras.'],
        ['q' => '¿Puedo cambiar de plan?', 'a' => 'Cuando quieras, desde tu cuenta. Si subes, el cambio es inmediato y solo pagas la diferencia. Si pasas a gratis, sigues con tu plan hasta el final del periodo que ya has pagado.'],
        ['q' => '¿Puedo pagar al año?', 'a' => 'Sí. El pago anual sale a dos meses gratis frente al mensual.'],
        ['q' => '¿Qué es un alias?', 'a' => 'Otra dirección del dominio que llega a tu mismo buzón, por ejemplo para compras o para tu trabajo. Puedes enviar desde ella y borrarla cuando quieras. Van con los planes de pago: 3 en el Básico y 10 en el Pro.'],
        ['q' => '¿Y si no quiero pagar por un alias?', 'a' => 'Usa el truco del «+»: si eres ana@unagrandeylibre.es, lo que se envíe a ana+compras@unagrandeylibre.es te llega igual. Sirve en todos los planes y te ayuda a saber quién ha pasado tu dirección.'],
        ['q' => '¿Por qué algunos nombres cortos cuestan más?', 'a' => 'Los nombres de pocos caracteres son escasos. Se pueden coger con cualquier plan de pago con un pequeño suplemento mensual.'],
        ['q' => '¿Puedo arrepentirme después de pagar?', 'a' => 'Sí: tienes 14 días para desistir. Si pediste empezar a usarlo al momento, solo pagas la parte que hayas usado.'],
    ],

    'legal' => [
        'aviso-legal' => 'Aviso legal',
        'privacidad' => 'Política de privacidad',
        'cookies' => 'Política de cookies',
        'condiciones' => 'Condiciones del servicio',
        'uso-aceptable' => 'Política de uso aceptable',
    ],

];
